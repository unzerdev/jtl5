<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments;

use HeidelpayAlipay;
use HeidelpayCreditCard;
use HeidelpayEPS;
use HeidelpayFlexiPayDirect;
use HeidelpayGiropay;
use HeidelpayHirePurchaseDirectDebit;
use HeidelpayiDEAL;
use HeidelpayInvoice;
use HeidelpayInvoiceFactoring;
use HeidelpayInvoiceGuaranteed;
use HeidelpayPayPal;
use HeidelpayPrepayment;
use HeidelpayPrzelewy24;
use HeidelpaySEPADirectDebit;
use HeidelpaySEPADirectDebitGuaranteed;
use HeidelpaySofort;
use HeidelpayWeChatPay;
use InvalidArgumentException;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerApplePay;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerBancontact;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerPaylaterInstallment;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerPaylaterInvoice;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use UnzerSDK\Constants\IdStrings;
use UnzerSDK\Resources\PaymentTypes\Alipay;
use UnzerSDK\Resources\PaymentTypes\Applepay;
use UnzerSDK\Resources\PaymentTypes\Bancontact;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\EPS;
use UnzerSDK\Resources\PaymentTypes\Giropay;
use UnzerSDK\Resources\PaymentTypes\Ideal;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\PaymentTypes\Invoice;
use UnzerSDK\Resources\PaymentTypes\InvoiceSecured;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\PaymentTypes\PIS;
use UnzerSDK\Resources\PaymentTypes\Prepayment;
use UnzerSDK\Resources\PaymentTypes\Przelewy24;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebitSecured;
use UnzerSDK\Resources\PaymentTypes\Sofort;
use UnzerSDK\Resources\PaymentTypes\Wechatpay;
use UnzerSDK\Services\IdService;

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

    /**
     * @var array
     */
    private $options = [];

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
        UnzerPaylaterInvoice::class               => 'unzerrechnung(jetztkaufen,späterbezahlen)',
        UnzerBancontact::class                    => 'unzerbancontact',
        UnzerPaylaterInstallment::class           => 'unzerratenzahlung(paylater)'
    ];

    private const FACTORIES = [
        Applepay::class                  => 'createApplePayModule',
        Alipay::class                    => 'createAlipayModule',
        Card::class                      => 'createCardModule',
        EPS::class                       => 'createEPSModule',
        Giropay::class                   => 'createGiropayModule',
        InstallmentSecured::class        => 'createHirePurchaseDirectDebitModule',
        Ideal::class                     => 'createIdealModule',
        Invoice::class                   => 'createInvoiceModule',
        // InvoiceFactoring::class          => 'createInvoiceFactoringModule',
        // InvoiceGuaranteed::class         => 'createInvoiceGuaranteedModule',
        InvoiceSecured::class            => 'createInvoiceSecuredModule',
        PaylaterInvoice::class           => 'createPaylaterInvoiceModule',
        Paypal::class                    => 'createPaypalModule',
        PIS::class                       => 'createFlexiPayDirectModule',
        Prepayment::class                => 'createPrepaymentModule',
        Przelewy24::class                => 'createPrzelewy24Module',
        SepaDirectDebit::class           => 'createSepaDirectDebitModule',
        SepaDirectDebitSecured::class    => 'createSepaDirectDebitGuaranteedModule',
        Sofort::class                    => 'createSofortModule',
        Wechatpay::class                 => 'createWechatpayModule',
        Bancontact::class                => 'createBancontactModule',
        PaylaterInstallment::class       => 'createPaylaterInstallmentModule'
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
        $this->options = $options;

        if (array_key_exists($class, self::FACTORIES)) {
            return call_user_func([$this, self::FACTORIES[$class]]);
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

    /**
     * Create Bancontact Payment Module.
     *
     * @return HeidelpayPaymentMethod|UnzerBancontact
     */
    public function createBancontactModule(): HeidelpayPaymentMethod
    {
        return $this->create(UnzerBancontact::class);
    }

    /**
     * Create ApplePay Payment Module.
     *
     * @return HeidelpayPaymentMethod|UnzerApplePay
     */
    public function createApplePayModule(): HeidelpayPaymentMethod
    {
        return $this->create(UnzerApplePay::class);
    }

    /**
     * Create Alipay Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayEPS
     */
    public function createAlipayModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayAlipay::class);
    }

    /**
     * Create Credit Card Module
     *
     * @return HeidelpayPaymentMethod|HeidelpayCreditCard
     */
    public function createCardModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayCreditCard::class);
    }

    /**
     * Creatte EPS Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayEPS
     */
    public function createEPSModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayEPS::class);
    }

    /**
     * Create Giropay Module
     *
     * @return HeidelpayPaymentMethod|HeidelpayGiropay
     */
    public function createGiropayModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayGiropay::class);
    }

    /**
     * Create Hire Purchase Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayHirePurchaseDirectDebit
     */
    public function createHirePurchaseDirectDebitModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayHirePurchaseDirectDebit::class);
    }

    /**
     * Create iDEAL Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayiDEAL
     */
    public function createIdealModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayiDEAL::class);
    }

    /**
     * Create Invoice Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayInvoice
     */
    public function createInvoiceModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayInvoice::class);
    }

    /**
     * Create Invoice Factoring Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayInvoiceFactoring
     */
    public function createInvoiceFactoringModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayInvoiceFactoring::class);
    }

    /**
     * Create Invoice Guaranteed Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayInvoiceGuaranteed
     */
    public function createInvoiceGuaranteedModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayInvoiceGuaranteed::class);
    }

    /**
     * Create Invoice Secured Payment Module.
     *
     * !NOTE: Unzer has changed the sdk so that both factoring and guaranteed invoices are now
     * ! mapped to Invoice Secured, but it is not clear if one is removed or they both should act the same
     * ! As a result, we try to differentiate based on their Id String, default to invoice guaranteed!
     *
     * @return HeidelpayPaymentMethod|HeidelpayInvoiceGuaranteed|HeidelpayInvoiceFactoring
     */
    public function createInvoiceSecuredModule(): HeidelpayPaymentMethod
    {
        $module = HeidelpayInvoiceGuaranteed::class;

        if (array_key_exists('id-string', $this->options)) {
            if (IdService::getResourceTypeFromIdString($this->options['id-string']) == IdStrings::INVOICE_FACTORING) {
                $module = HeidelpayInvoiceFactoring::class;
            }
        }

        return $this->create($module);
    }

    /**
     * Create Paylater Invoice Payment Module.
     *
     * @return HeidelpayPaymentMethod|UnzerPaylaterInvoice
     */
    public function createPaylaterInvoiceModule(): HeidelpayPaymentMethod
    {
        return $this->create(UnzerPaylaterInvoice::class);
    }

    /**
     * Create Paylater Installment Payment Module.
     *
     * @return HeidelpayPaymentMethod|UnzerPaylaterInstallment
     */
    public function createPaylaterInstallmentModule(): HeidelpayPaymentMethod
    {
        return $this->create(UnzerPaylaterInstallment::class);
    }

    /**
     * Create Paypayl Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayPayPal
     */
    public function createPaypalModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayPayPal::class);
    }

    /**
     * Create FlexiPay Direct (PIS) Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayFlexiPayDirect
     */
    public function createFlexiPayDirectModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayFlexiPayDirect::class);
    }

    /**
     * Create Prepayment Payment Module
     *
     * @return HeidelpayPaymentMethod|HeidelpayPrepayment
     */
    public function createPrepaymentModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayPrepayment::class);
    }

    /**
     * Create Przelewy24 Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayPrzelewy24
     */
    public function createPrzelewy24Module(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayPrzelewy24::class);
    }

    /**
     * Create SEPA Direct Debit Module
     *
     * @return HeidelpayPaymentMethod|HeidelpaySEPADirectDebit
     */
    public function createSepaDirectDebitModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpaySEPADirectDebit::class);
    }

    /**
     * Create SEPA Direct Debit (guaranteed) Module
     *
     * @return HeidelpayPaymentMethod|HeidelpaySEPADirectDebitGuaranteed
     */
    public function createSepaDirectDebitGuaranteedModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpaySEPADirectDebitGuaranteed::class);
    }

    /**
     * Create Sofort Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpaySofort
     */
    public function createSofortModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpaySofort::class);
    }

    /**
     * Create WeChat Pay Payment Module.
     *
     * @return HeidelpayPaymentMethod|HeidelpayWeChatPay
     */
    public function createWechatpayModule(): HeidelpayPaymentMethod
    {
        return $this->create(HeidelpayWeChatPay::class);
    }
}
