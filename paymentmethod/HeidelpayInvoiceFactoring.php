<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Checkout\Bestellung;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;

/**
 * With invoice payments an invoice is sent to the customer - the customer pays upon receipt of the invoice and
 * after the process is finished you (the merchant) receive your money.
 *
 * In essence, Invoice factoring is the same as Invoice guaranteed with the only difference being the insurance company.
 * Instead of an insurance company in the background a third party business takes care of the invoice,
 * thus guaranteeing your payment.
 *
 * @see https://docs.heidelpay.com/docs/invoice-payment
 * @deprecated
 */
class HeidelpayInvoiceFactoring extends HeidelpayInvoice implements CancelableInterface, HandleStepAdditionalInterface
{
    use HasBasket;
    use HasCustomer;
    use HasMetadata;
    use SupportsB2B;

    /**
     * Cancel the Charge.
     *
     * Invoice factoring has an additional mandatory field (reason code) in case of a cancel.
     *
     * @param Payment $payment
     * @param Charge $transaction
     * @param Bestellung $order
     * @return Cancellation
     */
    public function cancelPaymentTransaction(
        Payment $payment,
        AbstractTransactionType $transaction,
        Bestellung $order
    ): Cancellation {
        return $transaction->cancel(null, CancelReasonCodes::REASON_CODE_CANCEL);
    }

    /**
     * Add Customer Resource to view.
     *
     * @param JTLSmarty $view
     * @return void
     */
    public function handleStepAdditional(JTLSmarty $view): void
    {
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer(
            $this->adapter,
            $this->sessionHelper,
            $this->isB2BCustomer($shopCustomer)
        );
        $customer->setShippingAddress(
            $this->createHeidelpayAddress(
                $this->sessionHelper->getFrontendSession()->get('Lieferadresse')
            )
        );

        $data = $view->getTemplateVars('hpPayment') ?: [];
        $data['customer'] = $customer;
        $data['isB2B'] = $this->isB2BCustomer($shopCustomer);

        $view->assign('hpPayment', $data);
    }

    /**
     * Save customer resource id in the session.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return bool
     */
    public function validateAdditional(): bool
    {
        $postPaymentData = $_POST['paymentData'] ?? [];

        // Save Customer ID if it exists
        if (isset($postPaymentData['customerId'])) {
            $this->sessionHelper->set(SessionHelper::KEY_CUSTOMER_ID, $postPaymentData['customerId']);

            return true && parent::validateAdditional();
        }

        return parent::validateAdditional();
    }

    /**
     * Basket & Customer reference
     *
     * For Invoice factoring we need to provide a basket & customer resource within the charge call.
     * Only with these resources the insurance company can do the risk check.
     *
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType
    {
        // Fetch customer resource
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer(
            $this->adapter,
            $this->sessionHelper,
            $this->isB2BCustomer($shopCustomer)
        );
        $customer->setShippingAddress($this->createHeidelpayAddress($order->Lieferadresse));
        $customer->setBillingAddress($this->createHeidelpayAddress($order->oRechnungsadresse));
        $this->debugLog('Customer Resource: ' . $customer->jsonSerialize(), static::class);

        // Update existing customer resource if needed
        if ($customer->getId()) {
            $customer = $this->adapter->getApi()->updateCustomer($customer);
            $this->debugLog('Updated Customer Resource: ' . $customer->jsonSerialize(), static::class);
        }

        // Create Basket
        $session = $this->sessionHelper->getFrontendSession();
        $basket = $this->createHeidelpayBasket(
            $session->getCart(),
            $order->Waehrung,
            $session->getLanguage(),
            $payment->getId()
        );
        $this->debugLog('Basket Resource: ' . $basket->jsonSerialize(), static::class);

        $charge = new Charge(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->getCode(),
            $this->getReturnURL($order)
        );
        $charge->setOrderId($order->cBestellNr ?? null);

        return $this->adapter->getApi()->performCharge(
            $charge,
            $payment->getId(),
            $customer,
            $this->createMetadata(),
            $basket
        );
    }
}
