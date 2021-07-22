<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Payments\Interfaces;

use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Checkout\Bestellung;

/**
 * Interface for cancel auth/charge calls.
 *
 * @see https://docs.heidelpay.com/docs/payment-cancels
 * @see https://docs.heidelpay.com/docs/performing-transactions#cancel-on-an-authorization-aka-reversal
 * @see https://docs.heidelpay.com/docs/cancel-charges
 * @see https://docs.heidelpay.com/docs/performing-transactions#cancel-on-a-charge-aka-refund
 * @package Plugin\s360_unzer_shop5\src\Payments\Interfaces
 */
interface CancelableInterface
{
    /**
     * Cancel Authorization or Charge
     *
     * @param Payment $payment
     * @param AbstractTransactionType|Charge|Authorization $transaction
     * @param Bestellung $order
     * @return Cancellation
     */
    public function cancelPaymentTransaction(
        Payment $payment,
        AbstractTransactionType $transaction,
        Bestellung $order
    ): Cancellation;
}
