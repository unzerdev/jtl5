<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use JTL\Shop;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Cancellation;

/**
 * With invoice payments an invoice is sent to the customer - the customer pays upon receipt of the invoice and
 * after the process is finished you (the merchant) receive your money.
 *
 * With a guaranteed invoice you will be guaranteed receiving money, even if the customer does not pay.
 * The guaranteed invoice works similarly to the invoice. The difference is that an insurance company
 * will be involved into the process. The insurance company guarantees payment because
 * it carries out a risk check in advance.
 *
 * @see https://docs.heidelpay.com/docs/invoice-payment
 * @deprecated
 */
class HeidelpayInvoiceGuaranteed extends HeidelpayInvoice implements HandleStepAdditionalInterface, CancelableInterface
{
    use HasCustomer;
    use HasBasket;
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
        $reference = str_replace(
            ['%ORDER_ID%', '%SHOPNAME%'],
            [$order->cBestellNr, Shop::getSettingValue(CONF_GLOBAL, 'global_shopname')],
            $this->trans(Config::LANG_CANCEL_PAYMENT_REFERENCE)
        );

        return $transaction->cancel(null, CancelReasonCodes::REASON_CODE_CANCEL, $reference);
    }

    /**
     * Add Customer Resource to view.
     *
     * @param JTLSmarty $view
     * @return void
     */
    public function handleStepAdditional(JTLSmarty $view): void
    {
        $this->adapter->getConnectionForSession();
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
     * For Invoice guaranteed and factoring you need to provide a customer resource within the charge call.
     * Only with this customer resource the insurance company can do the risk check.
     *
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        // Create or fetch customer resource
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
            $customer = $this->adapter->getCurrentConnection()->updateCustomer($customer);
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

        return $this->adapter->getCurrentConnection()->performCharge(
            $charge,
            $payment->getId(),
            $customer,
            $this->createMetadata(),
            $basket
        );
    }
}
