<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\CancelPaymentTransaction;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasAuthorization;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasDirectCharge;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;

class UnzerWero extends HeidelpayPaymentMethod implements RedirectPaymentInterface, CancelableInterface
{
    use CancelPaymentTransaction;
    use HasAuthorization;
    use HasDirectCharge;
    use HasMetadata;
    use HasCustomer;
    use HasBasket;
    use SupportsB2B;


    protected function getAllowedCurrencies(): array
    {
        return ['EUR'];
    }

    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);

        // Create or fetch customer resource
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer(
            $this->adapter,
            $this->sessionHelper,
            $this->isB2BCustomer($shopCustomer)
        );
        $customer->setShippingAddress($this->createHeidelpayAddress($order->Lieferadresse));
        $customer->setBillingAddress($this->createHeidelpayAddress($order->oRechnungsadresse));
        $customer->setLanguage(strtolower($this->sessionHelper->getFrontendSession()->getCustomer()->cLand ?? 'de'));

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
        // if ($config->getPaymentSetting(Config::PAYMENT_BOOKING_MODE, $this->moduleID) === 'authorize') {
        //     return $this->adapter->getCurrentConnection()->performAuthorization(
        //         $this->createAuthorization($shopCustomer, $order, false),
        //         $payment->getId(),
        //         $customer,
        //         $this->createMetadata(),
        //         $basket
        //     );
        // }

        // Charge Payment
        return $this->adapter->getCurrentConnection()->performCharge(
            $this->createCharge($order),
            $payment->getId(),
            $customer,
            $this->createMetadata(),
            $basket
        );
    }
}