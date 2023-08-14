<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Charges;

use InvalidArgumentException;
use JTL\Cart\CartItem;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Checkout\Bestellung;
use JTL\Helpers\Tax;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingEntity;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\JtlLoggerTrait;
use UnzerSDK\Resources\EmbeddedResources\ShippingData;
use UnzerSDK\Resources\Payment;

/**
 * Charge Handler
 * @package Plugin\s360_unzer_shop5\src\Charges
 */
class ChargeHandler
{
    use JtlLoggerTrait;

    /**
     * @var ChargeMappingModel
     */
    private $model;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var HeidelpayApiAdapter
     */
    private $adapter;

    /**
     * @param ChargeMappingModel $model
     * @param Config $config
     * @param HeidelpayApiAdapter $adapter
     */
    public function __construct(ChargeMappingModel $model, Config $config, HeidelpayApiAdapter $adapter)
    {
        $this->model = $model;
        $this->config = $config;
        $this->adapter = $adapter;
    }

    /**
     * Make charge calls for shipment
     *
     * @param Bestellung $order
     * @param HeidelpayPaymentMethod $method
     * @param Payment $payment
     * @param OrderMappingEntity $entity
     * @return void
     */
    public function chargeOnShipping(
        Bestellung $order,
        HeidelpayPaymentMethod $method,
        Payment $payment,
        OrderMappingEntity $entity
    ): void {
        $amount = 0;
        $processedDeliveries = array_map(static function (ChargeMappingEntity $entity) {
            return $entity->getDeliveryId();
        }, $this->model->getProcessedDeliveries($order->kBestellung));

        $types = [
            C_WARENKORBPOS_TYP_GUTSCHEIN,
            C_WARENKORBPOS_TYP_KUPON,
            C_WARENKORBPOS_TYP_NEUKUNDENKUPON,
            C_WARENKORBPOS_TYP_VERPACKUNG,
            C_WARENKORBPOS_TYP_VERSANDPOS,
            C_WARENKORBPOS_TYP_VERSANDZUSCHLAG,
            C_WARENKORBPOS_TYP_VERSAND_ARTIKELABHAENGIG,
        ];

        // Partial Shipment
        if ($order->cStatus == \BESTELLUNG_STATUS_TEILVERSANDT) {
            $this->debugLog('Handle partial delivery for order ' . $order->cBestellNr, self::class);

            // Extras (shipping, coupons if no deliveries where processed yet)
            if (empty($processedDeliveries)) {
                $this->debugLog(
                    'Charge extra positions (shipping, coupons, etc.) if existing for order ' . $order->cBestellNr,
                    self::class
                );

                foreach ($order->Positionen as $pos) {
                    if (in_array($pos->nPosTyp, $types, true)) {
                        $amount += Tax::getGross(
                            $pos->fPreis * $pos->nAnzahl,
                            $this->getTaxRate($pos)
                        );
                    }
                }
            }

            /** @var \JTL\Checkout\Lieferschein $delivery */
            foreach ($order->oLieferschein_arr as $delivery) {
                // Skip already processed delivery
                if (in_array($delivery->getLieferschein(), $processedDeliveries)) {
                    $this->debugLog(
                        'Skip delivery `' . $delivery->getLieferschein() . '` as it was already processed',
                        static::class
                    );

                    continue;
                }

                // accumulate delivery positions
                foreach ($delivery->oPosition_arr as $pos) {
                    if (!isset($pos->nAusgeliefert)) {
                        throw new InvalidArgumentException(
                            'Missing property of nAusgeliefert for order: ' . $order->cBestellNr
                        );
                    }

                    if ($pos->nAusgeliefert < 1) {
                        continue;
                    }

                    $amount += Tax::getGross(
                        $pos->fPreis * (int) $pos->nAusgeliefert,
                        $this->getTaxRate($pos)
                    );
                }

                $chargeInstance = new Charge($amount);

                // Shipping Info
                if (!empty($delivery->oVersand_arr)) {
                    /** @var \JTL\Checkout\Versand $shipping */
                    $shipping = current($delivery->oVersand_arr);
                    $shippingData = (new ShippingData())
                        ->setDeliveryTrackingId($shipping->getIdentCode())
                        ->setDeliveryService($shipping->getLogistik());
                    $chargeInstance->setShipping($shippingData);
                }

                // Add charge (mark delivery as processed)
                $amount = 0;
                $this->addCharge(
                    $this->adapter->getApi()->performChargeOnPayment(
                        $payment,
                        $chargeInstance->setInvoiceId($entity->getInvoiceId())
                    ),
                    $method,
                    $order,
                    $delivery->getLieferschein()
                );
            }
        }

        // Handle Full Delivery (could also be the last part of a partial order)
        if ($order->cStatus == \BESTELLUNG_STATUS_VERSANDT) {
            // If there are already processed deliveries this only captures the remaining amount
            $chargeInstance = new Charge();
            $this->debugLog(
                'Handle full delivery for order (could also be the last partial order) ' . $order->cBestellNr,
                self::class
            );

            // Add charge (mark delivery as processed)
            $this->addCharge(
                $this->adapter->getApi()->performChargeOnPayment(
                    $payment,
                    $chargeInstance->setInvoiceId($entity->getInvoiceId())
                ),
                $method,
                $order
            );
        }
    }

    /**
     * Add an incoming charge to an order.
     *
     * @param Charge $charge
     * @param HeidelpayPaymentMethod $paymentMethod
     * @param Bestellung $order
     * @param int|null $deliveryId
     * @return void
     */
    public function addCharge(
        Charge $charge,
        HeidelpayPaymentMethod $paymentMethod,
        Bestellung $order,
        ?int $deliveryId = null
    ): void {
        // Charge is already marked as incoming payment -> skip!
        if ($this->model->getChargeForOrder((int) $order->kBestellung, $charge->getId())) {
            $paymentMethod->doLog(
                'Skipping charge ' . $charge->getId() . ' as it is already processed as incoming payment',
                \LOGLEVEL_NOTICE
            );

            return;
        }

        if ($charge->isSuccess()) {
            // Add Incoming Payment
            if ($this->config->get(Config::ADD_INCOMING_PAYMENTS, true)) {
                $paymentMethod->addIncomingPayment($order, (object) [
                    'fBetrag'  => $charge->getAmount(),
                    'cISO'     => $charge->getCurrency(),
                    'cHinweis' => $charge->getShortId() ?? $charge->getUniqueId()
                                    ?? $charge->getPaymentId() ?? $charge->getId()
                ]);
            }

            // Save Charge in mapping table
            $entity = new ChargeMappingEntity();
            $entity->setOrderId((int) $order->kBestellung);
            $entity->setPaymentId($charge->getPaymentId());
            $entity->setChargeId($charge->getId());
            $entity->setDeliveryId($deliveryId);

            $this->model->save($entity);

            $this->debugLog(
                'Charge was successfull: ' . print_r($charge->jsonSerialize(), true),
                static::class
            );
            return;
        }

        // Could not capture charge now (either because it is still pending or there was an error)
        if ($charge->isPending()) {
            $paymentMethod->doLog(
                'Cannot capture charge ' . $charge->getId() . ' for order ' . $order->cBestellNr .
                ' (still pending): ' . $charge->getMessage()->getMerchant(),
                $charge->isError() ? \LOGLEVEL_ERROR : \LOGLEVEL_NOTICE
            );

            return;
        }

        $paymentMethod->doLog(
            'Cannot capture charge ' . $charge->getId() . ' for order ' . $order->cBestellNr .
            ': ' . $charge->getMessage()->getMerchant(),
            $charge->isError() ? \LOGLEVEL_ERROR : \LOGLEVEL_NOTICE
        );
    }

    /**
     * Mark order as paid.
     *
     * @param HeidelpayPaymentMethod $paymentMethod
     * @param Bestellung $order
     * @return void
     */
    public function markAsPaid(HeidelpayPaymentMethod $paymentMethod, Bestellung $order): void
    {
        if ($this->config->get(Config::ADD_INCOMING_PAYMENTS, true)) {
            $this->debugLog(
                'No remaining amount to capture. Mark order ' . $order->cBestellNr . ' as paid.',
                static::class
            );
            $paymentMethod->setOrderStatusToPaid($order);
            $paymentMethod->sendConfirmationMail($order);
        }
    }

    /**
     * Helper for CartItem::getTaxRate() as this does not exist prior to JTL 5.1.0.
     *
     * @param CartItem|stdClass $cartItem
     * @return float
     */
    private function getTaxRate($cartItem): float
    {
        if (version_compare(APPLICATION_VERSION, '5.1.0', '>=')) {
            // Try to get accurate taxrate
            return CartItem::getTaxRate($cartItem);
        }

        // JTL 5.0.x
        $taxRate = Tax::getSalesTax(0);
        if (($cartItem->kSteuerklasse ?? 0) === 0) {
            if (isset($cartItem->fMwSt)) {
                $taxRate = $cartItem->fMwSt;
            } elseif (isset($cartItem->Artikel)) {
                $taxRate = ($cartItem->Artikel->kSteuerklasse ?? 0) > 0
                    ? Tax::getSalesTax((int) $cartItem->Artikel->kSteuerklasse)
                    : $cartItem->Artikel->fMwSt;
            }
        } else {
            $taxRate = Tax::getSalesTax((int) $cartItem->kSteuerklasse);
        }

        return (float) $taxRate;
    }
}
