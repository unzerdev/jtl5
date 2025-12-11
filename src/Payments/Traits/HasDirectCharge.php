<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use JTL\Checkout\Bestellung;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * @mixin HeidelpayPaymentMethod
 */
trait HasDirectCharge
{
    protected function createCharge(Bestellung $order): Charge
    {
        $charge = new Charge(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->getCode(),
            $this->getReturnURL($order)
        );
        $charge->setOrderId($order->cBestellNr ?? null);

        return $charge;
    }
}