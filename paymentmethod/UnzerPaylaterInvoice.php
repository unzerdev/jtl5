<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use DateTime;
use JTL\Checkout\Bestellung;
use JTL\Checkout\ZahlungsInfo;
use JTL\Helpers\Text;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepReviewOrderInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use stdClass;
use UnzerSDK\Constants\ShippingTypes;
use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;

class UnzerPaylaterInvoice extends HeidelpayPaymentMethod implements
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
        // Cancel before charge (reversal)
        if ($transaction instanceof Authorization) {
            return $this->adapter->getApi()->cancelAuthorizedPayment($payment);
        }

        $reference = str_replace(
            ['%ORDER_ID%', '%SHOPNAME%'],
            [$order->cBestellNr, Shop::getSettingValue(CONF_GLOBAL, 'global_shopname')],
            $this->trans(Config::LANG_CANCEL_PAYMENT_REFERENCE)
        );

        // Cancel after charge (refund)
        return $this->adapter->getApi()->cancelChargedPayment(
            $payment,
            (new Cancellation($transaction->getAmount()))
                ->setPaymentReference($reference)
        );
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

        return 'template/partials/_threatMetrix';
    }

    /**
     * Save customer resource id in the session.
     *
     * Save different billing address in session AND in the DB!
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
            $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
            $customer = $this->adapter->getApi()->fetchCustomer($postPaymentData['customerId']);

            // Update Billing Address
            if ($customer->getShippingAddress()->getShippingType() === ShippingTypes::DIFFERENT_ADDRESS) {
                $shopCustomer->cBundesland = $customer->getBillingAddress()->getState();
                $shopCustomer->cPLZ = $customer->getBillingAddress()->getZip();
                $shopCustomer->cOrt = $customer->getBillingAddress()->getCity();
                $shopCustomer->cLand = $customer->getBillingAddress()->getCountry();

                // Split name into first and lastname
                $names = mb_split('\s+', $customer->getBillingAddress()->getName() ?? '', 2);
                if (!empty($names) && \count($names) >= 1) {
                    $shopCustomer->cVorname = current($names) ?? '';
                    $shopCustomer->cNachname = end($names) ?? '';
                }

                // split street into street and street number
                $split = mb_split(' ', $customer->getBillingAddress()->getStreet());
                if (\count($split) > 1) {
                    $shopCustomer->cHausnummer = $split[count($split) - 1];
                    unset($split[count($split) - 1]);
                    $shopCustomer->cStrasse = implode(' ', $split);
                } else {
                    $sStreet = implode(' ', $split);
                    if (mb_strlen($sStreet) > 1) {
                        $shopCustomer->cHausnummer = mb_substr($sStreet, -1);
                        $shopCustomer->cStrasse = mb_substr($sStreet, 0, -1);
                    } else {
                        $shopCustomer->cHausnummer = '';
                    }
                }

                // Set kLieferadresse to -1 in the order so that JTL knows to use the delivery address from the session
                // and not use the billing adress as the delivery address
                if (!isset($_SESSION['Bestellung'])) {
                    $_SESSION['Bestellung'] = new stdClass();
                }

                $_SESSION['Bestellung']->kLieferadresse = -1;
                // $this->sessionHelper->getFrontendSession()->setCustomer($shopCustomer);
            }

            return true && parent::validateAdditional();
        }

        return parent::validateAdditional();
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
    protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType
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

        return $this->adapter->getApi()->performAuthorization(
            $authorization,
            $payment->getId(),
            $customer,
            $this->createMetadata(),
            $basket
        );
    }
}