<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\CancelPaymentTransaction;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasAuthorization;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;

class UnzerKlarna extends HeidelpayPaymentMethod implements
    RedirectPaymentInterface,
    HandleStepAdditionalInterface,
    CancelableInterface
{
    use HasAuthorization;
    use HasMetadata;
    use HasCustomer;
    use HasBasket;
    use SupportsB2B;
    use CancelPaymentTransaction;

    protected function getAllowedCountryCurrencyCombination()
    {
        return [
            'AU' => 'AUD',
            'AT' => 'EUR',
            'BE' => 'EUR',
            'CA' => 'CAD',
            'CZ' => 'CZK',
            'DK' => 'DKK',
            'FI' => 'EUR',
            'FR' => 'EUR',
            'DE' => 'EUR',
            'GR' => 'EUR',
            'HU' => 'HUF',
            'IE' => 'EUR',
            'IT' => 'EUR',
            'MX' => 'MXN',
            'NL' => 'EUR',
            'NZ' => 'NZD',
            'NO' => 'NOK',
            'PL' => 'PLN',
            'PT' => 'EUR',
            'RO' => 'RON',
            'SK' => 'EUR',
            'ES' => 'EUR',
            'SE' => 'SEK',
            'CH' => 'CHF',
            'GB' => 'GBP',
            'US' => 'USD',
        ];
    }

    protected function getAllowedCountries(): array
    {
        return array_keys($this->getAllowedCountryCurrencyCombination());
    }

    protected function getAllowedCurrencies(): array
    {
        return array_values($this->getAllowedCountryCurrencyCombination());
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

        $view->assign('hpPayment', $data);
    }

    /**
     * Check country+currency combination
     *
     * @return bool
     */
    public function isSelectable(): bool
    {
        if (!parent::isSelectable()) {
            return false;
        }

        // Check Country + Currency combination
        $country = $this->sessionHelper->getFrontendSession()->getCustomer()->cLand;
        $currency = $this->sessionHelper->getFrontendSession()->getCurrency()->getCode();

        if ($this->getAllowedCountryCurrencyCombination()[$country] !== $currency) {
            return false;
        }

        return true;
    }

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

        if (empty($customer->getLanguage())) {
            $customer->setLanguage(strtolower($this->sessionHelper->getFrontendSession()->getCustomer()->cLand ?? 'de'));
        }

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

        return $this->adapter->getCurrentConnection()->performAuthorization(
            $this->createAuthorization($shopCustomer, $order, false),
            $payment->getId(),
            $customer,
            $this->createMetadata(),
            $basket
        );
    }
}
