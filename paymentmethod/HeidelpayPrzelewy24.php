<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;

/**
 * Heidelpay Przelewy24 Payment Method.
 *
 * Przelewy24 is a secure and fast online bank transfer service linked to all the major banks in Poland.
 * To start using Przelewy24 you must have access to online banking.
 * Przelewy24 Service is an Internet service facilitating transfer of payments between the Customer and the Merchant.
 *
 * The Customer selects his bank from a list of banks displayed.
 *
 * @see https://docs.heidelpay.com/docs/przelewy24-payment
 */
class HeidelpayPrzelewy24 extends HeidelpayPaymentMethod implements RedirectPaymentInterface
{
    use HasBasket;
    use HasMetadata;
    use HasCustomer;

    protected function getAllowedCountries(): array
    {
        return ['PL'];
    }

    protected function getAllowedCurrencies(): array
    {
        return ['EUR', 'PLN'];
    }

    /**
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        // Create / Update existing customer resource if needed
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, false);
        $customer->setShippingAddress($this->createHeidelpayAddress($order->Lieferadresse));
        $customer->setBillingAddress($this->createHeidelpayAddress($order->oRechnungsadresse));
        $customer->setCompanyInfo(null);
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
