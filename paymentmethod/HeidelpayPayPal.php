<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;

/**
 * Heidelpay Paypal Payment Method.
 *
 * PayPal Holdings, Inc. is an American company operating a worldwide online payments system
 * that supports online money transfers and serves as an electronic alternative to
 * traditional paper methods like cheques and money orders.
 *
 * The customer has to sign up for a PayPal account.
 * Afterwards there is no need to enter the payment details again during the payment process.
 *
 * The Plugin does not support Paypal Express!
 *
 * @see https://docs.heidelpay.com/docs/paypal-payment
 */
class HeidelpayPayPal extends HeidelpayPaymentMethod implements RedirectPaymentInterface
{
    use HasCustomer;
    use HasMetadata;

    /**
     * Although Paypal support both auth as well as charge calls, we only support Direct Charge.
     *
     * We assign a customer with a shipping address to the charge to allow for "PayPal buyer protection"
     *
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        // Create a customer with shipping address for Paypal's Buyer Protection
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, false);
        $customer->setShippingAddress($this->createHeidelpayAddress($order->Lieferadresse));
        $customer->setBillingAddress($this->createHeidelpayAddress($order->oRechnungsadresse));

        // Update existing customer resource if needed
        if ($customer->getId()) {
            $customer = $this->adapter->getCurrentConnection()->updateCustomer($customer);
        }

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
            $this->createMetadata()
        );
    }
}
