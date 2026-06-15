<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use JTL\Session\Frontend;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HasPayButton;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasAuthorization;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Googlepay;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;

class UnzerGooglePay extends HeidelpayPaymentMethod implements HasPayButton, RedirectPaymentInterface, CancelableInterface
{
    use HasAuthorization;
    use HasMetadata;
    use HasCustomer;
    use HasBasket;
    use SupportsB2B;

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

    /**
     * Check if merchant id and name are set
     * @inheritDoc
     */
    public function isValidIntern($args = []): bool
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);

        if (empty($config->getPaymentSetting(Config::GPAY_MERCHANT_ID, $this->moduleID))) {
            return false;
        }

        if (empty($config->getPaymentSetting(Config::GPAY_MERCHANT_NAME, $this->moduleID))) {
            return false;
        }

        return parent::isValidIntern($args);
    }

    /**
     * Get the channel id for google pay and save it in the checkout session
     * @inheritDoc
     */
    public function handleAdditional($post): bool
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);
        // $channelId = $config->getPaymentSetting(Config::GPAY_GATEWAY_MERCHANT_ID, $this->moduleID);

        $this->adapter->getConnectionForSession();
        $channelId = $this->adapter->getChannelIdForPaymentType(Googlepay::getResourceName());

        if (!empty($channelId)) {
            $config->savePaymentSetting(Config::GPAY_GATEWAY_MERCHANT_ID, $this->moduleID, $channelId);
        }

        $this->sessionHelper->setCheckoutSession(null, $channelId);

        // always return true, as we do not want to show this step
        return true;
    }

    /**
     * Add the google pay button
     *
     * @param JTLSmarty $view
     * @return null|string
     */
    public function addPayButton(JTLSmarty $view): ?string
    {
        $channelId = $this->sessionHelper->getCheckoutSession()[SessionHelper::KEY_CHANNEL_ID] ?? null;

        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);
        $this->handler->prepareView();

        $data = $view->getTemplateVars('hpPayment') ?: [];

        // $basket = $this->createHeidelpayBasket(
        //     $this->sessionHelper->getFrontendSession()->getCart(),
        //     $this->sessionHelper->getFrontendSession()->getCurrency(),
        //     $this->sessionHelper->getFrontendSession()->getLanguage(),
        // );

        // $lineItems = array_map(static function (BasketItem $item) {
        //     return [
        //         'label' => $item->getTitle(),
        //         'type' => 'LINE_ITEM',
        //         'price' => (string) ($item->getQuantity() * $item->getAmountPerUnitGross())
        //     ];
        // }, $basket->getBasketItems());


        $data['googlepay'] = [
            'gatewayMerchantId' => $channelId,
            'merchantInfo' => [
                'merchantId' => $config->getPaymentSetting(Config::GPAY_MERCHANT_ID, $this->moduleID, $this->plugin),
                'merchantName' => $config->getPaymentSetting(Config::GPAY_MERCHANT_NAME, $this->moduleID, $this->plugin),
            ],
            'transactionInfo' => [
                // 'displayItems' => $lineItems,
                'countryCode' => $config->getPaymentSetting(Config::GPAY_COUNTRY_CODE, $this->moduleID, $this->plugin) ?? 'DK',
                'currencyCode' => $this->sessionHelper->getFrontendSession()->getCurrency()->getCode(),
                'totalPrice' => (string) round(
                    $this->sessionHelper->getFrontendSession()->getCart()->gibGesamtsummeWaren(true)
                    * Frontend::getCurrency()->getConversionFactor(),
                    2
                )
            ],
            'allowCreditCards' => $config->getPaymentSetting(Config::GPAY_ALLOW_CREDIT_CARDS, $this->moduleID, $this->plugin) !== 'N',
            'allowPrepaidCards' => $config->getPaymentSetting(Config::GPAY_ALLOW_PREPAID_CARDS, $this->moduleID, $this->plugin) !== 'N',
            'buttonOptions' => [
                'buttonColor' => $config->getPaymentSetting(Config::GPAY_BTN_COLOR, $this->moduleID, $this->plugin),
                'buttonSize' => $config->getPaymentSetting(Config::GPAY_BTN_SIZE, $this->moduleID, $this->plugin),
            ],
            'allowedCardNetworks' => []
        ];

        if ($config->getPaymentSetting(Config::GPAY_ACCEPT_MASTERCARD, $this->moduleID, $this->plugin) !== 'N') {
            $data['googlepay']['allowedCardNetworks'][] = 'MASTERCARD';
        }

        if ($config->getPaymentSetting(Config::GPAY_ACCEPT_VISA, $this->moduleID, $this->plugin) !== 'N') {
            $data['googlepay']['allowedCardNetworks'][] = 'VISA';
        }

        $view->assign('hpPayment', $data);


        return 'template/google_pay_button';
    }

    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);

        // Create or fetch customer resource
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer(
            $this->adapter,
            $this->sessionHelper,
            $this->isB2BCustomer($shopCustomer)
        );
        $customer->setShippingAddress($this->createHeidelpayAddress($order->Lieferadresse));
        $customer->setBillingAddress($this->createHeidelpayAddress($order->oRechnungsadresse));
        $this->debugLog('Customer Resource: ' . $customer->jsonSerialize(), static::class);

        // Update existing customer resource if needed
        if ($customer->getId()) {
            $customer = $this->adapter->getCurrentConnection()->updateCustomer($customer);
            $this->debugLog('Updated Customer Resource: ' . $customer->jsonSerialize(), static::class);
        }

        // Create Basket
        $session = $this->sessionHelper->getFrontendSession();
        $basket = $this->createHeidelpayBasket(
            $session->getCart(),
            $order->Waehrung,
            $session->getLanguage(),
            $order->cBestellNr ?? $payment->getId()
        );
        $this->debugLog('Basket Resource: ' . $basket->jsonSerialize(), static::class);

        // Authorize payment
        if ($config->getPaymentSetting(Config::GPAY_BOOKING_MODE, $this->moduleID) === 'authorize') {
            return $this->adapter->getCurrentConnection()->performAuthorization(
                $this->createAuthorization($shopCustomer, $order),
                $payment->getId(),
                $customer,
                $this->createMetadata(),
                $basket
            );
        }

        // Charge Payment
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
            $this->createMetadata(),
            $basket
        );
    }
}
