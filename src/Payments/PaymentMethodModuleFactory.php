<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments;

use InvalidArgumentException;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayAlipay;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayCreditCard;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayEPS;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayFlexiPayDirect;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayGiropay;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayHirePurchaseDirectDebit;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayiDEAL;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayInvoice;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayInvoiceFactoring;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayInvoiceGuaranteed;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayPayPal;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayPrepayment;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayPrzelewy24;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpaySEPADirectDebit;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpaySEPADirectDebitGuaranteed;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpaySofort;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayWeChatPay;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerApplePay;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerApplePayV2;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerBancontact;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerDirectBankTransfer;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerGooglePay;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerKlarna;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerPaylaterDirectDebit;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerPaylaterInstallment;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerPaylaterInvoice;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerTwint;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerWero;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use UnzerSDK\Resources\PaymentTypes\Alipay;
use UnzerSDK\Resources\PaymentTypes\Applepay;
use UnzerSDK\Resources\PaymentTypes\Bancontact;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Clicktopay;
use UnzerSDK\Resources\PaymentTypes\EPS;
use UnzerSDK\Resources\PaymentTypes\Giropay;
use UnzerSDK\Resources\PaymentTypes\Googlepay;
use UnzerSDK\Resources\PaymentTypes\Ideal;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\PaymentTypes\Invoice;
use UnzerSDK\Resources\PaymentTypes\InvoiceSecured;
use UnzerSDK\Resources\PaymentTypes\OpenbankingPis;
use UnzerSDK\Resources\PaymentTypes\Klarna;
use UnzerSDK\Resources\PaymentTypes\PaylaterDirectDebit;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\PaymentTypes\PIS;
use UnzerSDK\Resources\PaymentTypes\Prepayment;
use UnzerSDK\Resources\PaymentTypes\Przelewy24;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebitSecured;
use UnzerSDK\Resources\PaymentTypes\Sofort;
use UnzerSDK\Resources\PaymentTypes\Twint;
use UnzerSDK\Resources\PaymentTypes\Wechatpay;
use UnzerSDK\Resources\PaymentTypes\Wero;

/**
 * Factory to create payment method modules.
 *
 * @package Plugin\s360_unzer_shop5\src\Payments
 */
class PaymentMethodModuleFactory
{
    /**
     * @var int
     */
    private $kPlugin;

    public const MODULE = [
        HeidelpayAlipay::class                    => 'unzeralipay',
        HeidelpayCreditCard::class                => 'unzerkreditkarte',
        HeidelpayEPS::class                       => 'unzereps',
        HeidelpayFlexiPayDirect::class            => ['unzerflexipaydirect', 'unzerdirektüberweisung'],
        HeidelpayGiropay::class                   => 'unzergiropay',
        HeidelpayHirePurchaseDirectDebit::class   => ['unzerflexipayinstallment(hirepurchase)', 'unzerinstalment', 'unzerratenkauf'],
        HeidelpayInvoice::class                   => 'unzerrechnung',
        HeidelpayiDEAL::class                     => 'unzerideal',
        HeidelpayInvoiceFactoring::class          => ['unzerfakturierungvonrechnungen', 'unzerrechnungskauf'],
        HeidelpayInvoiceGuaranteed::class         => ['unzerrechnung(guaranteed)', 'unzerrechnung(secured)'],
        HeidelpayPayPal::class                    => 'unzerpaypal',
        HeidelpayPrepayment::class                => ['unzerprepayment', 'unzervorkasse'],
        HeidelpayPrzelewy24::class                => 'unzerprzelewy24',
        HeidelpaySofort::class                    => 'unzersofort',
        HeidelpaySEPADirectDebit::class           => ['unzersepalastschrift', 'unzerlastschrift'],
        HeidelpaySEPADirectDebitGuaranteed::class => ['unzersepalastschrift(guaranteed)', 'unzerlastschrift(secured)'],
        HeidelpayWeChatPay::class                 => 'unzerwechatpay',
        UnzerApplePay::class                      => 'unzerapplepay',
        UnzerApplePayV2::class                    => 'unzerapplepayv2',
        UnzerBancontact::class                    => 'unzerbancontact',
        UnzerDirectBankTransfer::class            => 'unzerdirectbanktransfer',
        UnzerGooglePay::class                     => 'unzergooglepay',
        UnzerPaylaterDirectDebit::class           => 'unzerlastschrift(paylater)',
        UnzerPaylaterInstallment::class           => 'unzerratenzahlung(paylater)',
        UnzerPaylaterInvoice::class               => 'unzerrechnung(jetztkaufen,späterbezahlen)',
        UnzerTwint::class                         => 'unzertwint',
        UnzerKlarna::class                        => 'unzerklarna',
        UnzerWero::class                          => 'unzerwero',
    ];

    private const MAPPING = [
        Applepay::class                  => UnzerApplePay::class,
        Alipay::class                    => HeidelpayAlipay::class,
        Card::class                      => HeidelpayCreditCard::class,
        EPS::class                       => HeidelpayEPS::class,
        Giropay::class                   => HeidelpayGiropay::class,
        InstallmentSecured::class        => HeidelpayHirePurchaseDirectDebit::class,
        Ideal::class                     => HeidelpayiDEAL::class,
        Invoice::class                   => HeidelpayInvoice::class,
        InvoiceSecured::class            => HeidelpayInvoiceGuaranteed::class,
        Paypal::class                    => HeidelpayPayPal::class,
        PIS::class                       => HeidelpayFlexiPayDirect::class,
        Prepayment::class                => HeidelpayPrepayment::class,
        Przelewy24::class                => HeidelpayPrzelewy24::class,
        SepaDirectDebit::class           => HeidelpaySEPADirectDebit::class,
        SepaDirectDebitSecured::class    => HeidelpaySEPADirectDebitGuaranteed::class,
        Sofort::class                    => HeidelpaySofort::class,
        Wechatpay::class                 => HeidelpayWeChatPay::class,
        Applepay::class                  => UnzerApplePayV2::class,
        Bancontact::class                => UnzerBancontact::class,
        OpenbankingPis::class            => UnzerDirectBankTransfer::class,
        Googlepay::class                 => UnzerGooglePay::class,
        PaylaterDirectDebit::class       => UnzerPaylaterDirectDebit::class,
        PaylaterInstallment::class       => UnzerPaylaterInstallment::class,
        PaylaterInvoice::class           => UnzerPaylaterInvoice::class,
        Twint::class                     => UnzerTwint::class,
        Clicktopay::class                => HeidelpayCreditCard::class,
        Klarna::class                    => UnzerKlarna::class,
        Wero::class                    => UnzerWero::class,
    ];

    public function __construct()
    {
        $plugin = Shop::Container()->get(Config::PLUGIN_ID);
        $this->kPlugin = $plugin ? $plugin->getID() : -1;
    }

    /**
     * Create a payment method module for a payment type.
     *
     * @param BasePaymentType $type
     * @param array $options - Additional options
     * @return HeidelpayPaymentMethod
     * @throws InvalidArgumentException if no factory method for the provided type exists.
     */
    public function createForType(BasePaymentType $type, array $options = []): HeidelpayPaymentMethod
    {
        $class = get_class($type);

        if (array_key_exists($class, self::MAPPING)) {
            return $this->create(self::MAPPING[$class]);
        }

        throw new InvalidArgumentException('Cannot find a factory for type ' . $class);
    }

    /**
     * Create paymetho method.
     *
     * @param string $moduleKey
     * @return HeidelpayPaymentMethod
     */
    private function create(string $moduleKey): HeidelpayPaymentMethod
    {
        $modules = self::MODULE[$moduleKey];

        if (!is_array($modules)) {
            $modules = [$modules];
        }

        foreach ($modules as $module) {
            $method = Method::create(
                'kPlugin_' . $this->kPlugin . '_' . $module
            );

            if ($method) {
                return $method;
            }
        }
    }
}
