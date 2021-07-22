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
 * Heidelpay EPS Payment Method.
 *
 * EPS is the official online banking implementation of Austrian banks.
 *
 * EPS (short for Electronic Payment Standard) is an online payment system in Austria, based on online banking.
 * By either specifying a BIC or choosing a bank from the bank chooser list of EPS,
 * the customer gets redirected to the online banking interface of his bank.
 * After entering his credentials, the payment is treated like a normal, secure direct bank transfer.
 * That way, no sensible data is exchanged between merchant and customer.
 *
 * @see https://docs.heidelpay.com/docs/eps-payment
 */
class HeidelpayEPS extends HeidelpayPaymentMethod implements RedirectPaymentInterface
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
