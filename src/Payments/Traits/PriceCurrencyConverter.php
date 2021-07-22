<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use JTL\Cart\CartHelper;
use JTL\Checkout\Bestellung;

/**
 * Little Helper for convering prices between currencies.
 * @package Plugin\s360_unzer_shop5\src\Payments\Traits
 */
trait PriceCurrencyConverter
{
    /**
     * Helper to get total price in customer currency.
     *
     * Needed as `$order->fGesamtsummeKundenwaehrung` might be 0 on certain occasions (don't know why though).
     * When jtl build a `fakeBestellung()` the cart is not yet saved in the db which results in
     * empty order positions and the wrong calculation of the total price in the customer currency...
     *
     * @param Bestellung $order
     * @return float
     */
    public function getTotalPriceCustomerCurrency(Bestellung $order): float
    {
        return round(CartHelper::roundOptional($order->fGesamtsumme * $order->fWaehrungsFaktor), 2);
    }
}
