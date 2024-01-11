<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use Exception;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\ApplePay\CertificationService;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Unzer Apple Pay Payment Method.
 *
 * Apple Pay is a mobile wallet solution available to all users of Apple devices.
 *
 * @see https://docs.unzer.com/payment-methods/applepay/
 */
class UnzerApplePay extends HeidelpayPaymentMethod implements HandleStepAdditionalInterface
{
    use HasCustomer;
    use HasMetadata;
    use HasBasket;

    /**
     * @param JTLSmarty $view
     * @return void
     */
    public function handleStepAdditional(JTLSmarty $view): void
    {
        $data = $view->getTemplateVars('hpPayment') ?: [];
        $session = $this->sessionHelper->getFrontendSession();
        $countryCode = end(explode('-', $data['locale']));

        $data['snippets'] = [
            'NOT_SUPPORTED' => $this->plugin->getLocalization()->getTranslation(Config::LANG_APPLE_PAY_NOT_SUPPORTED)
                                ?? Config::LANG_APPLE_PAY_NOT_SUPPORTED,
            'CANCEL_BY_USER' => $this->plugin->getLocalization()->getTranslation(Config::LANG_APPLE_PAY_CANCEL_BY_USER)
                                ?? Config::LANG_APPLE_PAY_CANCEL_BY_USER
        ];

        $data['paymentRequest'] = [
            'countryCode'          => $countryCode,
            'currencyCode'         => $session->getCurrency()->getCode(),
            'supportedNetworks'    => ['visa', 'masterCard'],
            'merchantCapabilities' => ['supports3DS'],
            'total'                => [
                'label'  =>  Shop::getSettingValue(\CONF_GLOBAL, 'global_shopname'),
                'amount' => round($session->getCart()->gibGesamtsummeWaren(true), 2),
            ],
            'lineItems' => $this->createApplePayLineItems(
                $session->getCart(),
                $session->getCurrency(),
                $session->getLanguage()
            ),
        ];

        $view->assign('hpPayment', $data);
    }

    /**
     * Checks if apple pay method is fully configured
     *
     * @param array $args
     * @return boolean
     */
    public function isValidIntern($args = []): bool
    {
        try {
            /** @var CertificationService $certService */
            $certService = Shop::Container()->get(CertificationService::class);

            /** @var Config $config */
            $config = Shop::Container()->get(Config::class);

            $merchantId = $config->get(Config::APPLEPAY_MERCHANT_IDENTIFIER);
            $merchantDomain = $config->get(Config::APPLEPAY_MERCHANT_DOMAIN);
            $merchantCert = $certService->get(Config::APPLEPAY_MERCHANT_SIGNED_PEM);
            $merchantPrivateKey = $certService->get(Config::APPLEPAY_MERCHANT_PRIVATE_KEY);
            $paymentCert = $certService->get(Config::APPLEPAY_PAYMENT_SIGNED_PEM);
            $paymentPrivateKey = $certService->get(Config::APPLEPAY_PAYMENT_PRIVATE_KEY);

            if (
                empty($merchantId) || empty($merchantDomain) ||
                empty($merchantCert) || empty($merchantPrivateKey) ||
                empty($paymentCert) || empty($paymentPrivateKey)
            ) {
                $this->debugLog('ApplePay Payment Method not fully configured', self::class);
                return false;
            }
        } catch (Exception $exc) {
            $this->errorLog(
                'An error occured while validating apple pay payment method: ' . $exc->getMessage(),
                self::class
            );

            return false;
        }

        return parent::isValidIntern($args);
    }

    /**
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType
    {
        // Create a customer with shipping address for Paypal's Buyer Protection
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, false);
        $customer->setShippingAddress($this->createHeidelpayAddress($order->Lieferadresse));
        $customer->setBillingAddress($this->createHeidelpayAddress($order->oRechnungsadresse));

        // Update existing customer resource if needed
        if ($customer->getId()) {
            $customer = $this->adapter->getCurrentConnection()->updateCustomer($customer);
        }

        $charge = new Charge(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->getCode(),
            $this->getReturnURL($order)
        );
        $charge->setOrderId($order->cBestellNr ?? null);

        return $this->adapter->getCurrentConnection()->performCharge(
            $charge,
            $payment->getId(),
            $customer,
            $this->createMetadata()
        );
    }
}
