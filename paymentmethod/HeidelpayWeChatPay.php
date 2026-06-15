<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;

/**
 * Heidelpay WeChatPay Payment Method.
 *
 * With over 600 million active monthly users,
 * WeChat Pay is one of Chinas biggest and fastest growing mobile payment solutions to date.
 *
 * It provides an easy, safe and secure way for individuals and businesses to make and receive payments on the internet.
 *
 * @see https://docs.heidelpay.com/docs/wechatpay
 */
class HeidelpayWeChatPay extends HeidelpayPaymentMethod implements RedirectPaymentInterface
{
    use HasBasket;
    use HasMetadata;
    use HasCustomer;
    use SupportsB2B;

    protected function getAllowedCountries(): array
    {
        return ['AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'ES', 'GB', 'GR', 'HU', 'IE', 'IS', 'IT', 'LI', 'LU', 'MT', 'NL', 'NO', 'PT', 'SE'];
    }

    protected function getAllowedCurrencies(): array
    {
        return ['CHF', 'CNY', 'EUR', 'GBP', 'USD'];
    }

    /**
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
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
