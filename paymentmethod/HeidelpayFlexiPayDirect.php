<?php
declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
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
 * @see https://docs.heidelpay.com/docs/flexipay-direct
 */
class HeidelpayFlexiPayDirect extends HeidelpayPaymentMethod implements RedirectPaymentInterface
{
    use HasMetadata;

    /**
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType
    {
        return $this->adapter->getApi()->charge(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->cISO,
            $payment->getId(),
            $this->getReturnURL($order),
            null,
            $order->cBestellNr ?? null,
            $this->createMetadata()
        );
    }
}
