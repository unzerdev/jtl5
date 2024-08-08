<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Cart\Cart;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;

/**
 * Heidelpay FlexiPayDirect Payment Method.
 *
 * FlexiPay Direct (Payment Initiation Service = PIS) is a service allowing Merchants
 * to initiate a payment transfer directly through the online banking account of the payer.
 * The service grants access to the online banking account of the payer and performs
 * any task necessary to initiate the payment transfer.
 *
 * The payer himself only needs to provide credentials for logging into his online banking account
 * and authorize the payment transfer by his designated OTP-device - most likely via sms TAN.
 *
 * @deprecated
 * @see https://docs.heidelpay.com/docs/flexipay-direct
 */
class HeidelpayFlexiPayDirect extends HeidelpayPaymentMethod implements RedirectPaymentInterface
{
    use HasMetadata;
    use HasCustomer;

    /**
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType
    {
        // Create / Update existing customer resource if needed
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, false);

        if ($customer->getId()) {
            $customer = $this->adapter->getApi()->updateCustomer($customer);
        }

        $charge = new Charge(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->getCode(),
            $this->getReturnURL($order)
        );
        $charge->setOrderId($order->cBestellNr ?? null);

        return $this->adapter->getApi()->performCharge(
            $charge,
            $payment->getId(),
            $customer,
            $this->createMetadata()
        );
    }

    /**
     * @inheritDoc
     */
    public function isValid(object $customer, Cart $cart): bool
    {
        //! Note: Payment Method is deprecated -> should not be used anymore
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isSelectable(): bool
    {
        //! Note: Payment Method is deprecated -> should not be used anymore
        return false;
    }
}
