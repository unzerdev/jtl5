<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Data;

use JTL\DB\ReturnType;
use JTL\Plugin\Data\PaymentMethod;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\PaymentMethodModuleFactory;

final readonly class AvailablePaymentMethod
{
    public bool $isActiveUnzer;
    public bool $isValid;
    public string $configUrl;

    public function __construct(
        public string|null $id,
        public int         $methodId,
        public string      $logo,
        public string      $name,
        public array       $currencies,
        public array       $countries,
        public array       $customerTypes,
        public bool        $paymentBeforeOrderCompletion,
        public bool        $isActiveShop,
        public bool        $hasShippingMethod,
        public int         $sort,
    )
    {
        $this->isActiveUnzer = $this->id !== null;
        $this->isValid = $this->isActiveUnzer && $this->isActiveShop && $this->hasShippingMethod;
        $this->configUrl = Shop::getAdminURL(true) . '/paymentmethods?kZahlungsart=' . $this->methodId . '&token=' . Shop::getAdminSessionToken();
    }

    /**
     * @param object{
     *     supports: array<object{brands: string[], countries: [], channel: string, currency: string[]}>,
     *     type: string,
     *     allowCustomerTypes: string,
     *     allowCreditTransaction: bool
     * } $type
     */
    public static function from(HeidelpayPaymentMethod|PaymentMethod $paymentMethod, mixed $type): self
    {
        $method = $paymentMethod instanceof HeidelpayPaymentMethod ? $paymentMethod->getMethod() : $paymentMethod;
        $currencies = [];
        $countries = [];

        if ($type?->supports) {
            foreach ($type->supports as $support) {
                array_push($currencies, ...$support->currency);
                array_push($countries, ...$support->countries);
            }
        }

        //$currencies = !empty($currencies) ? $currencies : $method->getClassName()::getAllowedCurrencies(); // default to method currencies if api provided none
        //$countries = !empty($countries) ? $countries : $method->getClassName()::getAllowedCountries(); // default to method countries if api provided none

        $hasShippingMethod = Shop::Container()->getDB()->queryPrepared(
            'SELECT 1 FROM tversandartzahlungsart WHERE kZahlungsart = :id',
            ['id' => $method->getMethodID()],
            ReturnType::AFFECTED_ROWS
        );

        $path = $paymentMethod instanceof HeidelpayPaymentMethod
            ? $paymentMethod->plugin->getPaths()->getBaseURL()
            : Shop::getURL(true) . '/plugins/s360_unzer_shop5/';
        $path .= '/paymentmethod/images/';
        $logo = match (PaymentMethodModuleFactory::getResourceName($method)) {
            'alipay' =>  $path . 'Alipay.svg',
            'applepay' => $path . 'ApplePay.svg',
            'bancontact' => $path . 'Bancontact.svg',
            'card', 'clicktopay' => $path . 'Card.svg',
            'eps' => $path . 'EPS.svg',
            'googlepay' => $path . 'GooglePay.svg',
            'ideal' => $path . 'iDeal_WERO.svg',
            'klarna' => $path . 'Klarna.svg',
            'openbanking-pis' => $path . 'DirectBankTransferWL.svg',
            'paylater-direct-debit' => $path . 'Default.svg',
            'paylater-invoice' => $path . 'InvoiceWL.svg',
            'paylater-installment' => $path . 'InstalmentWL.svg',
            'paypal' => $path . 'Paypal.svg',
            'prepayment' => $path . 'PrepaymentWL.svg',
            'przelewy24' => $path . 'Przelewy24.svg',
            'sepa-direct-debit' => $path . 'SEPA.svg',
            'twint' => $path . 'Twint.svg',
            'wechatpay' => $path . 'WeChatPay.svg',
            'wero' => $path . 'WERO.svg',
            default => $method->getImage() ?: $path . 'Unzer.svg'
        };

        return new self(
            id: $type?->type,
            methodId: $method->getMethodID(),
            logo:  $logo,
            name: $method->getName(),
            currencies: array_unique($currencies),
            countries: array_unique($countries),
            customerTypes: array_unique(explode(',', $type?->allowCustomerTypes ?? '')),
            paymentBeforeOrderCompletion: $method->getDuringOrder(),
            isActiveShop: ($paymentMethod instanceof HeidelpayPaymentMethod && $paymentMethod->isValidIntern()) && $method->getActive() && $method->getUsable(),
            hasShippingMethod: $hasShippingMethod > 0,
            sort: $method->getSort()
        );
    }
}
