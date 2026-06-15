<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HasPayButton;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\NotificationInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\CancelPaymentTransaction;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasAuthorization;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;

class UnzerApplePayV2 extends HeidelpayPaymentMethod implements HasPayButton, NotificationInterface, CancelableInterface, RedirectPaymentInterface
{
    use HasAuthorization;
    use HasCustomer;
    use HasMetadata;
    use HasBasket;
    use CancelPaymentTransaction;
    use SupportsB2B;

    public function addPayButton(JTLSmarty $view): ?string
    {
        $this->handler->prepareView();

        $data = $view->getTemplateVars('hpPayment') ?: [];
        $session = $this->sessionHelper->getFrontendSession();
        $countryCode = end(explode('-', $data['locale']));

        $data['snippets'] = [
            'NOT_SUPPORTED' => $this->plugin->getLocalization()->getTranslation(Config::LANG_APPLE_PAY_NOT_SUPPORTED)
                                ?? Config::LANG_APPLE_PAY_NOT_SUPPORTED,
            'CANCEL_BY_USER' => $this->plugin->getLocalization()->getTranslation(Config::LANG_APPLE_PAY_CANCEL_BY_USER)
                                ?? Config::LANG_APPLE_PAY_CANCEL_BY_USER
        ];

        $data['paymentRequest'] = [
            'countryCode'          => $countryCode,
            'currencyCode'         => $session->getCurrency()->getCode(),
            'supportedNetworks'    => ['visa', 'masterCard'],
            'merchantCapabilities' => ['supports3DS'],
            'total'                => [
                'label'  =>  Shop::getSettingValue(\CONF_GLOBAL, 'global_shopname'),
                'amount' => round($session->getCart()->gibGesamtsummeWaren(true), 2),
            ],
            'lineItems' => $this->createApplePayLineItems(
                $session->getCart(),
                $session->getCurrency(),
                $session->getLanguage()
            ),
        ];

        $view->assign('hpPayment', $data);

        return 'template/apple_pay_button';
    }

    /**
     * Checks if apple pay method is fully configured
     *
     * @param array $args
     * @return boolean
     */
    public function isValidIntern($args = []): bool
    {
        if (is_readable(PFAD_ROOT . '/.well-known/apple-developer-merchantid-domain-association')) {
            return true;
        }

        return parent::isValidIntern($args);
    }

    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);

        // Create or fetch customer resource
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, $this->isB2BCustomer($shopCustomer));
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

        // Authorize payment
        if ($config->getPaymentSetting(Config::APPLEPAY_BOOKING_MODE, $this->moduleID) === 'authorize') {
            return $this->adapter->getCurrentConnection()->performAuthorization(
                $this->createAuthorization($session->getCustomer(), $order),
                $payment->getId(),
                $customer,
                $this->createMetadata(),
                $basket
            );
        }

        // Charge Payment
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
