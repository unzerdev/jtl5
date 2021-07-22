<?php
declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\TranslatorTrait;

/**
 * HeidelpaySEPADirectDebit Payment Method.
 *
 * SEPA stands for "Single Euro Payments Area", and is a European Union initiative.
 * It is driven by the EU institutions, in particular the European Commission
 * and the European Central Bank.
 *
 * SEPA Direct Debit is an Europe-wide Direct Debit system that allows merchants
 * to collect Euro-denominated payments from accounts in the 34 SEPA countries
 * and associated territories in a safe and efficient way.
 *
 * @see https://docs.heidelpay.com/docs/sepa-direct-debit-payment
 */
class HeidelpaySEPADirectDebit extends HeidelpayPaymentMethod implements RedirectPaymentInterface, HandleStepAdditionalInterface
{
    use HasMetadata;
    use TranslatorTrait;

    /**
     * Add SEPA Mandate text to view.
     *
     * @param JTLSmarty $view
     * @return void
     */
    public function handleStepAdditional(JTLSmarty $view): void
    {
        $data = $view->getTemplateVars('hpPayment') ?: [];
        $data['mandate'] = str_replace(
            '%MERCHANT_NAME%',
            Shop::getSettingValue(CONF_GLOBAL, 'global_shopname'),
            $this->trans(Config::LANG_SEPA_MANDATE)
        );

        $view->assign('hpPayment', $data);
    }

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
