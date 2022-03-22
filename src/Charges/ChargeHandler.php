<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Charges;

use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Checkout\Bestellung;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Utils\JtlLoggerTrait;

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
     * @param ChargeMappingModel $model
     */
    public function __construct(ChargeMappingModel $model)
    {
        $this->model = $model;
    }

    /**
     * Add an incoming charge to an order.
     *
     * @param Charge $charge
     * @param HeidelpayPaymentMethod $paymentMethod
     * @param Bestellung $order
     * @return void
     */
    public function addCharge(Charge $charge, HeidelpayPaymentMethod $paymentMethod, Bestellung $order): void
    {
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
            $paymentMethod->addIncomingPayment($order, (object) [
                'fBetrag'  => $charge->getAmount(),
                'cISO'     => $charge->getCurrency(),
                'cHinweis' => $charge->getShortId() ?? $charge->getUniqueId()
                                ?? $charge->getPaymentId() ?? $charge->getId()
            ]);

            // Save Charge in mapping table
            $entity = new ChargeMappingEntity;
            $entity->setOrderId((int) $order->kBestellung);
            $entity->setPaymentId($charge->getPaymentId());
            $entity->setChargeId($charge->getId());

            $this->model->save($entity);
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
        $this->debugLog(
            'No remaining amount to capture. Mark order ' . $order->cBestellNr . ' as paid.',
            static::class
        );
        $paymentMethod->setOrderStatusToPaid($order);
        $paymentMethod->sendConfirmationMail($order);
    }
}
