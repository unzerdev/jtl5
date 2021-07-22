<?php
declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Cart\Cart;
use JTL\Checkout\Bestellung;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use Plugin\s360_unzer_shop5\src\Utils\TranslatorTrait;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Cancellation;

/**
 * HeidelpaySEPADirectDebitGuaranteed Payment Method.
 *
 * SEPA stands for "Single Euro Payments Area", and is a European Union initiative.
 * It is driven by the EU institutions, in particular the European Commission
 * and the European Central Bank.
 *
 * SEPA direct debit guaranteed works very similar to SEPA direct debit.
 * The difference is that there is also an insurance company involved in the process.
 * The insurance company guarantees the payment, but only if the risk checks are successful.
 *
 * @see https://docs.heidelpay.com/docs/sepa-direct-debit-payment
 */
class HeidelpaySEPADirectDebitGuaranteed extends HeidelpayPaymentMethod implements RedirectPaymentInterface, HandleStepAdditionalInterface, CancelableInterface
{
    use HasBasket;
    use HasCustomer;
    use HasMetadata;
    use SupportsB2B;
    use TranslatorTrait;

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
        $customer->setFirstname($shopCustomer->cVorname);
        $customer->setLastname($shopCustomer->cNachname);
        $customer->setShippingAddress(
            $this->createHeidelpayAddress(
                $this->sessionHelper->getFrontendSession()->get('Lieferadresse')
            )
        );

        $data = $view->getTemplateVars('hpPayment') ?: [];
        $data['customer'] = $customer;
        $data['isB2B'] = $this->isB2BCustomer($shopCustomer);
        $data['mandate'] = str_replace(
            '%MERCHANT_NAME%',
            Shop::getSettingValue(CONF_GLOBAL, 'global_shopname'),
            $this->trans(Config::LANG_SEPA_MANDATE)
        );

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

        return false;
    }

    /**
     * Make sure, we only display it if shipping and invoice address are the same.
     *
     * @param object|Kunde $customer
     * @param Cart $cart
     * @return boolean
     */
    public function isValid($customer, $cart): bool
    {
        $shippingAddress = $this->sessionHelper->getFrontendSession()->get('Lieferadresse');

        return $this->shippingEqualsInvoiceAddress($shippingAddress, $customer) && parent::isValid($customer, $cart);
    }

    /**
     * To execute risk checks we also have to provide a customer reference to the /payments/charges call.
     *
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType
    {
        // Create or fetch customer resource
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, false);
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

        return $this->adapter->getApi()->charge(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->cISO,
            $payment->getId(),
            $this->getReturnURL($order),
            $customer,
            $order->cBestellNr ?? null,
            $this->createMetadata(),
            $basket
        );
    }
}
