<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasSavedPaymentData;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use Plugin\s360_unzer_shop5\src\Utils\TranslatorTrait;

/**
 * HeidelpaySEPADirectDebit Payment Method.
 *
 * SEPA stands for "Single Euro Payments Area", and is a European Union initiative.
 * It is driven by the EU institutions, in particular the European Commission
 * and the European Central Bank.
 *
 * SEPA Direct Debit is an Europe-wide Direct Debit system that allows merchants
 * to collect Euro-denominated payments from accounts in the 34 SEPA countries
 * and associated territories in a safe and efficient way.
 *
 * @see https://docs.heidelpay.com/docs/sepa-direct-debit-payment
 */
class HeidelpaySEPADirectDebit extends HeidelpayPaymentMethod implements
    RedirectPaymentInterface,
    HandleStepAdditionalInterface
{
    use HasSavedPaymentData;
    use HasBasket;
    use HasMetadata;
    use HasCustomer;
    use TranslatorTrait;
    use SupportsB2B;

    protected function getAllowedCountries(): array
    {
        return ['AT', 'BE', 'CY', 'FI', 'FR', 'DE', 'GR', 'IE', 'IT', 'LI', 'LV', 'LT', 'LU', 'MT', 'NL', 'PT', 'SI', 'SK', 'ES'];
    }

    protected function getAllowedCurrencies(): array
    {
        return ['EUR'];
    }

    /**
     * Add SEPA Mandate text to view.
     *
     * @param JTLSmarty $view
     * @return void
     */
    public function handleStepAdditional(JTLSmarty $view): void
    {
        $data = $view->getTemplateVars('hpPayment') ?: [];
        $data['mandate'] = str_replace(
            '%MERCHANT_NAME%',
            Shop::getSettingValue(CONF_GLOBAL, 'global_shopname'),
            $this->trans(Config::LANG_SEPA_MANDATE)
        );

        $view->assign('hpPayment', $data);
    }

    /**
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);
        if ($config->getPaymentSetting(Config::ALLOW_SAVE, $this->moduleID) === 'Y') {
            $this->savePaymentData($payment);
        }

        // Create / Update existing customer resource if needed
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, $this->isB2BCustomer($shopCustomer));
        $customer->setShippingAddress($this->createHeidelpayAddress($order->Lieferadresse));
        $customer->setBillingAddress($this->createHeidelpayAddress($order->oRechnungsadresse));
        $this->debugLog('Customer Resource: ' . $customer->jsonSerialize(), static::class);

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
