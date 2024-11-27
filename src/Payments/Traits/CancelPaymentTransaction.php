<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use JTL\Checkout\Bestellung;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\TranslatorTrait;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;

/**
 * Implements a default for CancelableInterface
 *
 * @property HeidelpayApiAdapter $adapter
 * @see Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface
 */
trait CancelPaymentTransaction
{
    use TranslatorTrait;

    /**
     * Cancel the Charge or authorization
     *
     * @param Payment $payment
     * @param Charge|Authorization $transaction
     * @param Bestellung $order
     * @return Cancellation
     */
    public function cancelPaymentTransaction(
        Payment $payment,
        AbstractTransactionType $transaction,
        Bestellung $order
    ): Cancellation {
        $api = $this->adapter->getConnectionForOrder($order);

        $reference = str_replace(
            ['%ORDER_ID%', '%SHOPNAME%'],
            [$order->cBestellNr, Shop::getSettingValue(CONF_GLOBAL, 'global_shopname')],
            $this->trans(Config::LANG_CANCEL_PAYMENT_REFERENCE)
        );

        $cancel = (new Cancellation($transaction->getAmount()))->setPaymentReference($reference);

        // Cancel before charge (reversal)
        if ($transaction instanceof Authorization) {
            return $api->cancelAuthorizedPayment($payment, $cancel);
        }

        // Cancel after charge (refund)
        return $api->cancelChargedPayment($payment, $cancel);
    }
}
