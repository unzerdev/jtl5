<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use DateTime;
use JTL\Cart\Cart;
use JTL\Checkout\Bestellung;
use JTL\Checkout\ZahlungsInfo;
use JTL\Helpers\Text;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepReviewOrderInterface;
use Plugin\s360_unzer_shop5\src\Payments\PaymentHandler;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;

class UnzerPaylaterInstallment extends HeidelpayPaymentMethod implements
    HandleStepAdditionalInterface,
    HandleStepReviewOrderInterface,
    CancelableInterface
{
    use HasMetadata;
    use HasCustomer;
    use HasBasket;
    use SupportsB2B;

    /**
     * Cancel the Charge or authorization
     *
     * @param Payment $payment
     * @param Charge|Authorization $transaction
     * @param Bestellung $order
     * @return Cancellation
     */
    public function cancelPaymentTransaction(
        Payment $payment,
        AbstractTransactionType $transaction,
        Bestellung $order
    ): Cancellation {
        $api = $this->adapter->getConnectionForOrder($order);

        $reference = str_replace(
            ['%ORDER_ID%', '%SHOPNAME%'],
            [$order->cBestellNr, Shop::getSettingValue(CONF_GLOBAL, 'global_shopname')],
            $this->trans(Config::LANG_CANCEL_PAYMENT_REFERENCE)
        );

        $cancel = (new Cancellation($transaction->getAmount()))->setPaymentReference($reference);

        // Cancel before charge (reversal)
        if ($transaction instanceof Authorization) {
            return $api->cancelAuthorizedPayment($payment, $cancel);
        }

        // Cancel after charge (refund)
        return $api->cancelChargedPayment($payment, $cancel);
    }

    /**
     * Data the merchant needs to put on the Invoice.
     *
     * The information iban, bic, descriptor and holder data must be be stated on the invoice
     * so that the customer can make the bank transfer.
     *
     * The customer should be informed that he should use the descriptor during online banking transfer.
     * This is the identifier that links the payment to the customer.
     *
     * We also save this data as payment info (tzahlungsinfo) to it is easily accessible.
     *
     * @param Bestellung $order
     * @param Authorization $transaction
     * @return array
     */
    public function getOrderAttributes(Bestellung $order, AbstractTransactionType $transaction): array
    {
        // save payment information
        $oPaymentInfo = new ZahlungsInfo(0, $order->kBestellung);
        $oPaymentInfo->kKunde            = $order->kKunde;
        $oPaymentInfo->kBestellung       = $order->kBestellung;
        $oPaymentInfo->cInhaber          = Text::convertUTF8($transaction->getHolder() ?? '');
        $oPaymentInfo->cIBAN             = Text::convertUTF8($transaction->getIban() ?? '');
        $oPaymentInfo->cBIC              = Text::convertUTF8($transaction->getBic() ?? '');
        $oPaymentInfo->cKontoNr          = $oPaymentInfo->cIBAN;
        $oPaymentInfo->cBLZ              = $oPaymentInfo->cBIC;
        $oPaymentInfo->cVerwendungszweck = Text::convertUTF8($transaction->getDescriptor() ?? '');
        $oPaymentInfo->cBankName         = '';
        $oPaymentInfo->cKartenNr         = '';
        $oPaymentInfo->cCVV              = '';

        isset($oPaymentInfo->kZahlungsInfo) ? $oPaymentInfo->updateInDB() : $oPaymentInfo->insertInDB();

        return [
            self::ATTR_IBAN                   => $oPaymentInfo->cIBAN,
            self::ATTR_BIC                    => $oPaymentInfo->cBIC,
            self::ATTR_TRANSACTION_DESCRIPTOR => $oPaymentInfo->cVerwendungszweck,
            self::ATTR_ACCOUNT_HOLDER         => $oPaymentInfo->cInhaber,
        ];
    }

    /**
     * Only allow B2C Customers
     */
    public function isSelectable(): bool
    {
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        return !$this->isB2BCustomer($shopCustomer) && parent::isSelectable();
    }

    /**
     * Load data for installment
     *
     * @inheritDoc
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
        $customer->setBillingAddress(
            $this->createHeidelpayAddress($this->sessionHelper->getFrontendSession()->getCustomer())
        );

        $data = $view->getTemplateVars('hpPayment') ?: [];
        $data['customer'] = $customer;
        $data['amount'] = round(
            $this->sessionHelper->getFrontendSession()->getCart()->gibGesamtsummeWaren(true),
            2
        );
        $data['currency'] = $this->sessionHelper->getFrontendSession()->getCurrency()->getCode();
        $data['country'] = $customer->getBillingAddress()->getCountry() ?? 'DE';

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

            // Save Basket Checksum + currency
            $this->sessionHelper->set(
                SessionHelper::KEY_CART_CHECKSUM,
                Cart::getChecksum($this->sessionHelper->getFrontendSession()->getCart())
            );
            $this->sessionHelper->set(
                SessionHelper::KEY_CART_CURRENCY,
                $this->sessionHelper->getFrontendSession()->getCurrency()->getCode()
            );

            return true && parent::validateAdditional();
        }

        return parent::validateAdditional();
    }

    /**
     * Generate and add threat metrix id (fraud prevention).
     *
     * @param JTLSmarty $view
     * @return null|string
     */
    public function handleStepReviewOrder(JTLSmarty $view): ?string
    {
        $data = $view->getTemplateVars('hpPayment') ?: [];
        $data['threatMetrixId'] = $this->sessionHelper->generateThreatMetrixId();
        $view->assign('hpPayment', $data);

        // Check if basket changed
        $currency = $this->sessionHelper->getFrontendSession()->getCurrency();
        $basket = $this->sessionHelper->getFrontendSession()->getCart();

        if (
            Cart::getChecksum($basket) !== $this->sessionHelper->get(SessionHelper::KEY_CART_CHECKSUM) ||
            $currency->getCode() !== $this->sessionHelper->get(SessionHelper::KEY_CART_CURRENCY)
        ) {
            $this->sessionHelper->clearCheckoutSession();
            $this->sessionHelper->clear(SessionHelper::KEY_CART_CHECKSUM);
            $this->sessionHelper->clear(SessionHelper::KEY_CART_CURRENCY);
            $this->sessionHelper->addErrorAlert(
                'Aborting Checkout. Currency or Basket mismatch. Reauthorization needed!',
                $this->trans(Config::LANG_CONFIRMATION_CHECKSUM),
                'basketMismatch',
                PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL,
                static::class
            );

            return null;
        }

        return 'template/partials/_threatMetrix';
    }

    /**
     * Authorizes the order on unzer side.
     *
     * With a successful authorize transaction, the amount is authorized and a payment resource is created.
     * At this point no money has been transferred.
     *
     * The charge transaction calls are made when the order is shipped.
     * With a successful charge transaction, the amount has been prepared to be insured.
     *
     * @inheritDoc
     * @return AbstractTransactionType|Authorization
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
            $order->cBestellNr ?? $payment->getId()
        );
        $this->debugLog('Basket Resource: ' . $basket->jsonSerialize(), static::class);

        // Authorize Transaction
        $riskData = (new RiskData())
            ->setThreatMetrixId($this->sessionHelper->get(SessionHelper::KEY_THREAT_METRIX_ID))
            ->setRegistrationLevel($shopCustomer->nRegistriert == '1' ? '1' : '0')
            ->setRegistrationDate(
                DateTime::createFromFormat('Y-m-d', $shopCustomer->dErstellt ?? date('Y-m-d'))->format('Ymd')
            );

        $authorization = new Authorization(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->getCode(),
            $this->getReturnURL($order)
        );
        $authorization->setOrderId($order->cBestellNr ?? null);
        $authorization->setRiskData($riskData);

        return $this->adapter->getCurrentConnection()->performAuthorization(
            $authorization,
            $payment->getId(),
            $customer,
            $this->createMetadata(),
            $basket
        );
    }
}
