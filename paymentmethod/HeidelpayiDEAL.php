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
 * Heidelpay iDEAL Payment Method.
 *
 * iDEAL is a standardised payment method for making secure online payments
 * directly between bank accounts in the Netherlands.
 *
 * To offer iDEAL as a payment method in an online store, a direct link is established
 * with the systems of participating banks.
 * In other words, this connection to iDEAL enables each Merchant access to online banking of ABN AMRO,
 * ASN Bank, Friesland Bank, ING, Rabobank, RegioBank, SNS Bank, Triodos Bank or Van Lanschot Bankiers
 * to make payments in this way. No other payment product offers this facility.
 *
 * Dutch customers pay online by using their login data of their bank account.
 *
 * @see https://docs.heidelpay.com/docs/ideal-payment
 */
class HeidelpayiDEAL extends HeidelpayPaymentMethod implements RedirectPaymentInterface
{
    use HasBasket;
    use HasMetadata;
    use HasCustomer;

    protected function getAllowedCountries(): array
    {
        return ['NL'];
    }

    protected function getAllowedCurrencies(): array
    {
        return ['EUR'];
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
