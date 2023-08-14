<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Controllers;

use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Checkout\Bestellung;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Charges\ChargeHandler;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingEntity;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\PaymentMethodModuleFactory;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\TranslatorTrait;
use stdClass;
use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;

/**
 * Handle WaWi Sync Orders, like shipping or canceling payments.
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers
 */
class SyncController extends Controller
{
    use TranslatorTrait;

    public const ACTION_SHIPMENT = 'shipment';
    public const ACTION_CANCEL = 'cancel';

    /**
     * @var string
     */
    protected $action;

    /**
     * @var Bestellung
     */
    protected $order;

    /**
     * @var OrderMappingModel
     */
    protected $model;

    /**
     * @var HeidelpayApiAdapter
     */
    protected $adapter;

    /**
     * @var PaymentMethodModuleFactory
     */
    protected $factory;

    /**
     * @var ChargeHandler
     */
    protected $chargeHandler;

    /**
     * @inheritDoc
     */
    public function __construct(PluginInterface $plugin)
    {
        parent::__construct($plugin);

        $this->adapter = Shop::Container()->get(HeidelpayApiAdapter::class);
        $this->chargeHandler = Shop::Container()->get(ChargeHandler::class);
        $this->model = new OrderMappingModel(Shop::Container()->getDB());
        $this->factory = new PaymentMethodModuleFactory();
    }

    /**
     * Set order.
     *
     * @param stdClass|Bestellung $order
     * @return void
     */
    public function setOrder($order): void
    {
        if ($order instanceof stdClass) {
            $order = new Bestellung($order->kBestellung);
        }

        $this->debugLog('Set order: ' . print_r($order, true), static::class);
        $this->order = $order;
    }

    /**
     * Set action.
     *
     * @param string $action
     * @return void
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * Handle WaWi Sync Orders, like shipping or canceling payments.
     *
     * @return string
     */
    public function handle(): string
    {
        $this->debugLog('WaWi-Sync Order Content: ' . print_r($this->order, true), static::class);

        // Ignore any order that is not paid with heidelpay
        $mappedOrder = $this->model->find((int) $this->order->kBestellung);

        if (empty($mappedOrder)) {
            $this->debugLog('Could not find mapped order. Ignore');
            return 'error';
        }

        // Action routing
        if ($this->action == self::ACTION_SHIPMENT) {
            $this->handleShipment($mappedOrder);
            $this->model->save($mappedOrder);
            return self::ACTION_SHIPMENT;
        }

        if ($this->action == self::ACTION_CANCEL) {
            $this->handleCancel($mappedOrder);
            $this->model->save($mappedOrder);
            return self::ACTION_CANCEL;
        }

        $this->errorLog('Invalid action: ' . $this->action, static::class);
        return 'error';
    }

    /**
     * Handle shipment calls to the api for payment types that support those calls.
     *
     * Shipping is only required for guaranteed payments. The insurance begins after the transport call has been made.
     *
     * @param OrderMappingEntity $entity
     * @return void
     */
    private function handleShipment(OrderMappingEntity $entity): void
    {
        $this->debugLog('Handle Shipment for: ' . print_r($entity->toObject(), true), static::class);

        // Only call shipment if the order is shipped
        if (
            $this->order->cStatus != \BESTELLUNG_STATUS_VERSANDT &&
            $this->order->cStatus != \BESTELLUNG_STATUS_TEILVERSANDT
        ) {
            $this->debugLog(
                'Skip order because it is not in a shipped state: ' . $entity->getJtlOrderNumber(),
                static::class
            );
            return;
        }

        // Check if shipment call is supported for this payment
        $payment = $this->adapter->fetchPayment($entity->getPaymentId());

        // Charge before shipping calls are made
        if ($this->adapter->shouldChargeBeforeShipping($payment->getPaymentType())) {
            $method = $this->factory->createForType(
                $payment->getPaymentType(),
                ['id-string' => $entity->getPaymentTypeId()]
            );

            $this->order->fuelleBestellung();
            $this->chargeHandler->chargeOnShipping($this->order, $method, $payment, $entity);

            $charge = $payment->getChargeByIndex(0);
            if ($charge) {
                $entity->setTransactionUniqueId($charge->getUniqueId());
                $this->model->save($entity);
            }

            $this->debugLog(
                'Charge before shipment call finished: ' . $entity->getJtlOrderNumber(),
                static::class
            );
        }

        // Ship order
        if ($this->adapter->supportsShipment($payment->getPaymentType())) {
            $this->debugLog(
                'Ship payment ' . $payment->getId() . ' with invoiceId ' . $entity->getInvoiceId()
                . ' for order ' . $entity->getJtlOrderNumber(),
                static::class
            );

            // Ship
            $shipment = $this->adapter->getApi()->ship($payment, $entity->getInvoiceId());
            $entity->setPaymentState($payment->getStateName());

            if ($shipment->isSuccess()) {
                $this->debugLog(
                    'Shipment call was successfull: ' . print_r($shipment->jsonSerialize(), true),
                    static::class
                );

                return;
            }

            // Shipment was unsuccessfull
            $msg = $shipment->getMessage();
            $this->errorLog(
                'Shipment call was unsuccessfull: ' . $msg->getMerchant() . ' (Error-Code: ' . $msg->getCode() . ')',
                static::class
            );
        }

        $this->debugLog('Finished handleShipment call for order ' . $entity->getJtlOrderNumber(), static::class);
    }

    /**
     * Handle cancel calls (STORNO) to the api.
     *
     * @see https://docs.heidelpay.com/docs/payment-cancels
     * @see https://docs.heidelpay.com/docs/performing-transactions#cancel-on-an-authorization-aka-reversal
     * @see https://docs.heidelpay.com/docs/cancel-charges
     * @see https://docs.heidelpay.com/docs/performing-transactions#cancel-on-a-charge-aka-refund
     *
     * @param OrderMappingEntity $entity
     * @return void
     */
    private function handleCancel(OrderMappingEntity $entity): void
    {
        $this->debugLog('Handle Cancelation for: ' . print_r($entity->toObject(), true));
        $payment = $this->adapter->fetchPayment($entity->getPaymentId());
        $method = $this->factory->createForType(
            $payment->getPaymentType(),
            ['id-string' => $entity->getPaymentTypeId()]
        );

        // Abort if we already canceled the payment
        if ($payment->getState() == PaymentState::STATE_CANCELED) {
            $this->debugLog('Skipping payment because it is aleady canceled');
            return;
        }

        // Release the reserved money for the customer's payment method.
        $authorization = $payment->getAuthorization();
        $entity->setPaymentState(PaymentState::STATE_NAME_CANCELED);

        if (
            !empty($authorization) &&
            !$payment->getPaymentType() instanceof InstallmentSecured &&
            $authorization instanceof Authorization &&
            empty($payment->getCharges())
        ) {
            $this->cancelTransaction($payment, $authorization, $method);
            return;
        }

        // Transfers money from the merchant to the customer (Refund). Refunds are executed on specific charges.
        // Most payment types probably contain only one charge, but for a full cancelation we have to cancel all.
        foreach ($payment->getCharges() as $charge) {
            /** @var Charge $charge */
            $charge = $this->adapter->getApi()->fetchCharge($charge);

            if (!$charge->isError()) {
                try {
                    $this->cancelTransaction($payment, $charge, $method);
                } catch (\UnzerSDK\Exceptions\UnzerApiException $exc) {
                    // Skip already charged transaction, we have to cancel the next transaction
                    if ($exc->getCode() === ApiResponseCodes::API_ERROR_ALREADY_CHARGED) {
                        $this->noticeLog('Skipping charge because it is already charged');
                        continue;
                    }

                    $this->errorLog(
                        'An API error occured while trying to cancel the transaction: ' .
                        $exc->getMerchantMessage() . ' | ' . $exc->getCode()
                    );
                }
            }
        }

        return;
    }

    /**
     * Cancel a transaction.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @param Payment $payment
     * @param Charge|Authorization $transaction
     * @param HeidelpayPaymentMethod $method
     * @return void
     */
    private function cancelTransaction(Payment $payment, $transaction, HeidelpayPaymentMethod $method): void
    {
        if ($transaction->isError()) {
            $this->errorLog(
                'Could not cancel unsuccessful transaction: ' . $transaction->getId(),
                static::class
            );
            return;
        }

        if ($method instanceof CancelableInterface) {
            $cancellation = $method->cancelPaymentTransaction($payment, $transaction, $this->order);
        } elseif ($transaction instanceof Authorization) {
            $cancellation = $this->adapter->getApi()->cancelAuthorizedPayment($payment);
        } else {
            $reference = str_replace(
                ['%ORDER_ID%', '%SHOPNAME%'],
                [$this->order->cBestellNr, Shop::getSettingValue(CONF_GLOBAL, 'global_shopname')],
                $this->trans(Config::LANG_CANCEL_PAYMENT_REFERENCE)
            );

            $cancellation = $transaction->cancel(null, CancelReasonCodes::REASON_CODE_CANCEL, $reference);
        }

        if ($cancellation->isError() || $cancellation->isPending()) {
            $this->errorLog(
                'Could not cancel payment: ' . $cancellation->getMessage()->getMerchant() .
                ' | Error-Code: ' . $cancellation->getMessage()->getCode(),
                static::class
            );
            return;
        }

        $this->debugLog(
            'Canceled payment: ' . print_r($payment->jsonSerialize(), true),
            static::class
        );
    }
}
