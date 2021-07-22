<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Webhooks;

use UnzerSDK\Constants\WebhookEvents;
use UnzerSDK\Resources\Payment;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Charges\ChargeHandler;
use Plugin\s360_unzer_shop5\src\Foundation\EventPayload;
use Plugin\s360_unzer_shop5\src\Foundation\EventSubscriber;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Payments\PaymentMethodModuleFactory;
use Plugin\s360_unzer_shop5\src\Utils\JtlLoggerTrait;
use RuntimeException;

/**
 * Heidelpay Payment Webhooks Event Subscriber
 * @package Plugin\s360_unzer_shop5\src\Webhooks
 */
class PaymentEventSubscriber extends EventSubscriber
{
    use JtlLoggerTrait;

    /**
     * @var ChargeHandler
     */
    private $charges;

    /**
     * @var OrderMappingModel
     */
    private $model;

    /**
     * @var PaymentMethodModuleFactory
     */
    private $paymentMethodFactory;

    public function __construct()
    {
        $this->charges = Shop::Container()->get(ChargeHandler::class);
        $this->model = new OrderMappingModel(Shop::Container()->getDB());
        $this->paymentMethodFactory = new PaymentMethodModuleFactory();
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WebhookEvents::PAYMENT_COMPLETED => 'onHandleIncomingPayment',
            WebhookEvents::PAYMENT_PARTLY    => 'onHandleIncomingPayment'
        ];
    }

    /**
     * Handle incoming payment events.
     *
     * `payment.completed`
     * This event is usually triggered with the following scenario:
     *  - When calling a Direct Charge, which **does not require** the customer's payment confirmation.
     *  - When an authorization has been **fully charged**:
     *    calling a Charge for Authorization with full amount of authorization.
     *
     * `payment.partly`
     * This event is usually triggered with the following scenario:
     *   - When an authorization has been **partly charged**:
     *     calling a Charge for Authorization with one part of the full authorization amount.
     *
     * @throws RuntimeException if we cannot find a mapped order for the payment
     * @param EventPayload $payload
     * @return void
     */
    public function onHandleIncomingPayment(EventPayload $payload)
    {
        /** @var Payment $payment */
        $payment = $payload->getResource();
        $this->debugLog('Handling incoming event ' . $payload->getEvent() . ' with: ' . $payment->jsonSerialize());

        $orderMapping = $this->model->findByPayment($payment->getId());
        $paymentMethod = $this->paymentMethodFactory->createForType(
            $payment->getPaymentType(),
            ['id-string' => $orderMapping->getPaymentTypeId()]
        );

        if (empty($orderMapping)) {
            throw new RuntimeException(
                'Cannot find order for payment ' . $payment->getId() . '. Maybe it is not mapped yet.'
            );
        }

        // The payment is completed, which means that a pending order can now be released.
        if ($payment->isCompleted()) {
            $this->debugLog(
                'Payment with id: ' . $payment->getId() . ' is completed. If the order was in a pending state,
                it is now being released for collection by the ERP.'
            );
            $this->model->releaseOrder($orderMapping->getId());
        }

        // Add incoming (successfull) payments
        /** @var HeidelpayApiAdapter $api */
        $api = Shop::Container()->get(HeidelpayApiAdapter::class);
        foreach ($payment->getCharges() as $charge) {
            // we need to fetch the charge because the charge in the payment object might not contain all information.
            // Especially the isError, isPending, isSuccess flags
            $charge = $api->getApi()->fetchCharge($charge);
            $this->charges->addCharge($charge, $paymentMethod, $orderMapping->getOrder());
        }

        // Mark order as paid if there is no remaining amount on the payment.
        if ($payment->getAmount()->getRemaining() <= 0) {
            $this->charges->markAsPaid($paymentMethod, $orderMapping->getOrder());
        }

        // Update Mapped Status
        $orderMapping->setPaymentState($payment->getStateName());
        $this->model->save($orderMapping);
    }
}
