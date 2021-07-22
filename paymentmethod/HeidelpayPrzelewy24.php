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
