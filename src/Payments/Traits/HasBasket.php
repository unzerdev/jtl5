<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use UnzerSDK\Constants\BasketItemTypes;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use JTL\Cart\Cart;
use JTL\Cart\CartItem;
use JTL\Catalog\Currency;
use JTL\Helpers\Tax;
use JTL\Helpers\Text;
use JTL\Language\LanguageHelper;

/**
 * Payment Methods which require a Basket object.
 *
 * @see https://docs.heidelpay.com/docs/additional-resources#basket-resource
 * @package Plugin\s360_unzer_shop5\src\Payments\Traits
 */
trait HasBasket
{
    /**
     * Create apple pay line items from cart
     *
     * @param Cart $cart
     * @param object $currency
     * @param LanguageHelper $lang
     * @param string $orderId
     * @return array
     */
    protected function createApplePayLineItems(Cart $cart, $currency, LanguageHelper $lang, string $orderId = ''): array
    {
        $basket = $this->createHeidelpayBasket($cart, $currency, $lang, $orderId);
        $lineItems = [];

        foreach ($basket->getBasketItems() as $lineItem) {
            /** @var BasketItem $lineItem */
            $lineItems[] = [
                'label'  => $lineItem->getTitle(),
                'amount' => round($lineItem->getAmountPerUnitGross() * $lineItem->getQuantity(), 2), // Total Amount
                'type'   => 'final'
            ];
        }

        return $lineItems;
    }

    /**
     * Create a Heidelpay Basket instance.
     *
     * @param Cart $cart
     * @param Currency|object $currency
     * @param LanguageHelper $lang
     * @param string $orderId
     * @return Basket
     */
    protected function createHeidelpayBasket(Cart $cart, $currency, LanguageHelper $lang, string $orderId = ''): Basket
    {
        $basket = (new Basket())
            ->setOrderId($orderId)
            ->setTotalValueGross(round($cart->gibGesamtsummeWaren(true, false), 2))
            ->setCurrencyCode(($currency instanceof Currency ? $currency->getCode() : $currency->cISO) ?? '');

        $cumulatedDelta = 0;
        foreach ($cart->PositionenArr as $position) {
            $basketItem = $this->createHeidelpayBasketItem(
                $position,
                $lang,
                $cumulatedDelta
            );

            // Skip free products which are not a discount because the unzer api does not like them!
            if ($basketItem->getAmountPerUnitGross() == 0 && $basketItem->getAmountDiscountPerUnitGross() == 0) {
                continue;
            }

            $basket->addBasketItem($basketItem);
        }

        // Check if there is a mismatch between total value gross and sum of all line items, and if so add
        // an error correction line item (otherwise the unzer api would throw an error due to the mismatch)
        $totalValueGross = array_reduce(
            $basket->getBasketItems(),
            static function (float $sum, BasketItem $item) {
                $sum += ($item->getAmountPerUnitGross() - $item->getAmountDiscountPerUnitGross()) * $item->getQuantity();
                return $sum;
            },
            0
        );

        $difference = round($totalValueGross - $basket->getTotalValueGross(), 2);
        if ($difference < 0) {
            // Add missing amount as error correction line item
            $basket->addBasketItem(
                (new BasketItem())
                    ->setTitle('ROUNDING ERROR CORRECTION')
                    ->setQuantity(1)
                    ->setAmountPerUnitGross($difference * -1)
            );
        } elseif ($difference > 0) {
            // Add "overcharged" amount as discount error correction line item
            $basket->addBasketItem(
                (new BasketItem())
                    ->setTitle('ROUNDING ERROR CORRECTION')
                    ->setQuantity(1)
                    ->setAmountPerUnitGross(0)
                    ->setAmountDiscountPerUnitGross($difference)
            );
        }

        return $basket;
    }

    /**
     * Create a Heidelpay BasketItem instance.
     *
     * @param CartItem $position
     * @param LanguageHelper $lang
     * @param float $cumulatedDelta    Rounding Error Delta, @see Cart::useSummationRounding
     * @return BasketItem
     */
    protected function createHeidelpayBasketItem(
        CartItem $position,
        LanguageHelper $lang,
        float &$cumulatedDelta
    ): BasketItem {
        $title = $position->cName;
        if (\is_array($title)) {
            $title = $title[$lang->getIso()];
        }

        // !NOTE: JTL distributes its rounding errors of the total basket sum to the cart positions,
        // ! so we have to do the same (kinda, as we just need the gross amount per unit and not total) ...
        $grossAmount        = Tax::getGross(
            $position->fPreis,
            Tax::getSalesTax($position->kSteuerklasse),
            12
        );
        $roundedGrossAmount = Tax::getGross(
            $position->fPreis + $cumulatedDelta,
            Tax::getSalesTax($position->kSteuerklasse),
            2
        );

        $cumulatedDelta += ($grossAmount - $roundedGrossAmount);

        // Unzer API thinks that -0.0 is a negative amount and therefore not allowed (seen for SEPA secured and B2B)
        if ($grossAmount === -0.0 || $grossAmount === 0.0) {
            $roundedGrossAmount = 0;
        }

        // Set Basket Item
        $basketItem = (new BasketItem())
            ->setTitle(Text::convertUTF8($title))
            ->setAmountPerUnitGross($roundedGrossAmount)
            ->setQuantity((int) $position->nAnzahl);

        if ($this->isPromotionLineItemType((string) $position->nPosTyp)) {
            $basketItem->setAmountPerUnitGross(0);
            $basketItem->setAmountDiscountPerUnitGross($roundedGrossAmount * -1);
        }

        $basketItem->setVat((float) Tax::getSalesTax($position->kSteuerklasse));
        $basketItem->setType($this->getBasketLineItemType((string) $position->nPosTyp));
        $basketItem->setBasketItemReferenceId($this->generateBasketItemReferenceId($position->cArtNr, $title));

        return $basketItem;
    }

    /**
     * Get the basket item type for a line item.
     *
     * @param string $type
     * @return string|null
     */
    private function getBasketLineItemType(string $type): ?string
    {
        switch ($type) {
            // Goods (includes digital, as jtl does not differ between those)
            case C_WARENKORBPOS_TYP_ARTIKEL:
            case C_WARENKORBPOS_TYP_GRATISGESCHENK:
                return BasketItemTypes::GOODS;

            // Vouchers and coupons
            case C_WARENKORBPOS_TYP_GUTSCHEIN:
            case C_WARENKORBPOS_TYP_KUPON:
            case C_WARENKORBPOS_TYP_NEUKUNDENKUPON:
                return BasketItemTypes::VOUCHER;

            // different type of shipping fees
            case C_WARENKORBPOS_TYP_VERPACKUNG:
            case C_WARENKORBPOS_TYP_VERSANDPOS:
            case C_WARENKORBPOS_TYP_VERSANDZUSCHLAG:
            case C_WARENKORBPOS_TYP_VERSAND_ARTIKELABHAENGIG:
                return BasketItemTypes::SHIPMENT;
        }

        return null;
    }


    /**
     * Checks if the type is a voucher basket item type.
     *
     * @param string $type
     * @return boolean
     */
    private function isPromotionLineItemType(string $type): bool
    {
        return $this->getBasketLineItemType($type) === BasketItemTypes::VOUCHER;
    }

    /**
     * Generate basket item refernce id
     *
     * @param string|null $productNumber
     * @param string $title
     * @return string
     */
    private function generateBasketItemReferenceId(?string $productNumber, string $title): string
    {
        $productNumber = Text::convertUTF8($productNumber);

        if (empty($productNumber)) {
            $productNumber = $title . '-' . time();
        }

        // Unzer API does not like spaces or other special chars in the ref id
        return preg_replace('/[^a-z0-9\-]/im', '', str_replace(' ', '-', $productNumber));
    }
}
