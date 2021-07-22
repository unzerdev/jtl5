<?php
declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use Exception;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Checkout\Bestellung;
use JTL\Checkout\ZahlungsInfo;
use JTL\Helpers\Text;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * HeidelpayCreditCard Payment Method.
 *
 * Card payment is one of the most common and popular payment methods for e-commerce.
 * Heidelpay supports nearly every available card brand worldwide:
 * * Visa
 * * Mastercard
 * * Amex
 * * Diners
 * * Carte Blue
 * * JCB
 * * CUP
 * * ...
 *
 * @see https://docs.heidelpay.com/docs/card-payment
 */
class HeidelpayCreditCard extends HeidelpayPaymentMethod implements RedirectPaymentInterface, HandleStepAdditionalInterface
{
    use HasMetadata;

    // Order Attributes
    public const ATTR_CARD_HOLDER = 'unzer_card_holder';
    public const ATTR_CARD_NUMBER = 'unzer_card_number';
    public const ATTR_CARD_EXPIRY_DATE = 'unzer_card_expiry_date';
    public const ATTR_CARD_CVC = 'unzer_card_cvc';
    public const ATTR_CARD_TYPE = 'unzer_card_type';

    /**
     * Save the credit card information.
     *
     * @param Bestellung $order
     * @param Charge $transaction
     * @return array
     */
    public function getOrderAttributes(Bestellung $order, AbstractTransactionType $transaction): array
    {
        try {
            /** @var Card $type */
            $type = $transaction->getPayment()->getPaymentType();

            // Save Payment Info
            $oPaymentInfo = new ZahlungsInfo(0, $order->kBestellung);
            $oPaymentInfo->kKunde       = $order->kKunde;
            $oPaymentInfo->kBestellung  = $order->kBestellung;
            $oPaymentInfo->cInhaber     = Text::convertUTF8($type->getCardHolder() ?? '');
            $oPaymentInfo->cKartenNr    = Text::convertUTF8($type->getNumber() ?? '');
            $oPaymentInfo->cGueltigkeit = Text::convertUTF8($type->getExpiryDate() ?? '');
            $oPaymentInfo->cCVV         = Text::convertUTF8($type->getCvc() ?? '');
            $oPaymentInfo->cKartenTyp   = Text::convertUTF8($type->getBrand() ?? '');

            isset($oPaymentInfo->kZahlungsInfo) ? $oPaymentInfo->updateInDB() : $oPaymentInfo->insertInDB();

            // Order Attributes
            return [
                self::ATTR_CARD_HOLDER      => $oPaymentInfo->cInhaber,
                self::ATTR_CARD_NUMBER      => $oPaymentInfo->cKartenNr,
                self::ATTR_CARD_EXPIRY_DATE => $oPaymentInfo->cGueltigkeit,
                self::ATTR_CARD_CVC         => $oPaymentInfo->cCVV,
                self::ATTR_CARD_TYPE        => $oPaymentInfo->cKartenTyp
            ];
        } catch (Exception $exc) {
            $this->errorLog(
                'An exception was thrown while trying to get the order attributes '
                . Text::convertUTF8($exc->getMessage()),
                static::class
            );
        }

        return [];
    }
    /**
     * Pass Styling Options to template
     *
     * @param JTLSmarty $view
     * @return void
     */
    public function handleStepAdditional(JTLSmarty $view): void
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);
        $data = $view->getTemplateVars('hpPayment') ?: [];

        $data['styling']             = [
            Config::FONT_COLOR  => $config->get(Config::FONT_COLOR),
            Config::FONT_FAMILY => $config->get(Config::FONT_FAMILY),
            Config::FONT_SIZE   => $config->get(Config::FONT_SIZE),
        ];
        $view->assign('hpPayment', $data);
    }

    /**
     * Although Cards support both auth as well as charge calls, we only support Direct Charge.
     *
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
