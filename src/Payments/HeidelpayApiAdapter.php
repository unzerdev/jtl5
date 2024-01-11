<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments;

use Exception;
use UnzerSDK\Unzer;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\PaymentTypes\InvoiceSecured;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use JTL\Cart\Cart;
use JTL\Checkout\Bestellung;
use JTL\Helpers\Text;
use JTL\Session\Frontend;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairService;
use Plugin\s360_unzer_shop5\src\Utils\JtlLinkHelper;
use Plugin\s360_unzer_shop5\src\Utils\JtlLoggerTrait;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;

/**
 * Heidelpay API Adapter for JTL Shop.
 *
 * Handles API Class initiation and provides some helpers regarding the API usage.
 *
 * @package Plugin\s360_unzer_shop5\src\Payments
 */
class HeidelpayApiAdapter
{
    use JtlLoggerTrait;

    // Payment types that support shipment calls
    public const SUPPORTS_SHIPMENT = [
        InvoiceSecured::class,
        InstallmentSecured::class // Note: shipment docu says no, hdd says yes
    ];
    public const SHOULD_CHARGE_BEFORE_SHIPPING = [
        PaylaterInvoice::class,
        PaylaterInstallment::class
    ];

    /**
     * @var Unzer
     * @deprecated
     */
    private $api;

    private SessionHelper $session;
    private JtlLinkHelper $linkHelper;

    private array $connections = [];
    private ?Unzer $currentConnection = null;
    private ?Unzer $defaultConnection = null;
    private KeyPairService $keypairService;

    public function __construct(KeyPairService $keyPairService, SessionHelper $session, JtlLinkHelper $linkHelper)
    {
        $this->keypairService = $keyPairService;
        $this->session = $session;
        $this->linkHelper = $linkHelper;
        $this->setCurrentConnection();
    }

    public function getKeypairService(): KeyPairService
    {
        return $this->keypairService;
    }

    /**
     * Create a new API Client Instance
     *
     * @return Unzer
     */
    public function setCurrentConnection(?bool $isB2B = null, ?int $currency = null, ?int $paymentMethod = null): Unzer
    {
        // TODO: Try to load defaults from session?
        if ($isB2B === null || $currency === null || $paymentMethod === null) {
            $this->currentConnection = $this->getDefaultConnection();
            return $this->currentConnection;
        }

        // If no private key exists for the combination use the default connection as the current connection
        $privateKey = $this->keypairService->getPrivateKey($isB2B, $currency, $paymentMethod);
        if (empty($privateKey)) {
            $this->currentConnection = $this->getDefaultConnection();
            return $this->currentConnection;
        }

        // Setup connection
        $key = (int) $isB2B . ":{$currency}:{$paymentMethod}";
        $this->connections[$key] = new Unzer(
            $privateKey,
            $this->mapToLocale(Shop::getLanguageCode() ?? 'eng')
        );

        $this->currentConnection = $this->connections[$key];

        return $this->currentConnection;
    }

    public function getCurrentConnection(): Unzer
    {
        return $this->currentConnection ?? $this->getDefaultConnection();
    }

    public function getDefaultConnection(): Unzer
    {
        if (empty($this->defaultConnection)) {
            $this->defaultConnection = new Unzer(
                $this->keypairService->getDefaultPrivateKey(),
                $this->mapToLocale(Shop::getLanguageCode() ?? 'eng')
            );
        }

        return $this->defaultConnection;
    }

    public function getConnectionForOrder(Bestellung $order): Unzer
    {
        return $this->setCurrentConnection(
            (isset($order->oKunde->cFirma) && strlen(trim($order->oKunde->cFirma)) > 0) || (isset($order->oRechnungsadresse->cFirma) && strlen(trim($order->oRechnungsadresse->cFirma))),
            (int) $order->kWaehrung,
            (int) $order->kZahlungsart
        );
    }

    public function getConnectionForSession(): Unzer
    {
        return $this->setCurrentConnection(
            isset(Frontend::getCustomer()->cFirma) && strlen(trim(Frontend::getCustomer()->cFirma)) > 0,
            (int) Frontend::getCurrency()->getID(),
            (int) Frontend::get('AktiveZahlungsart')
        );
    }

    public function getConnectionForPublicKey(string $publicKey): Unzer
    {
        $privateKey = $this->keypairService->getPrivateFromPublic($publicKey);

        if (empty($privateKey)) {
            return $this->getDefaultConnection();
        }

        $this->currentConnection = new Unzer(
            $privateKey,
            $this->mapToLocale(Shop::getLanguageCode() ?? 'eng')
        );

        return $this->currentConnection;
    }

    /**
     * Get Api instance.
     * @deprecated
     * @return Unzer
     */
    public function getApi(): Unzer
    {
        return $this->api;
    }

    /**
     * Fetch customer id and save its id to the session.
     *
     * If we cannot find a mapped id we set the customer id to -1 to avoid quering the api multiple times.
     *
     * @return string|int|null
     */
    public function fetchCustomerId()
    {
        if (
            !$this->session->has(SessionHelper::KEY_CUSTOMER_ID) &&
            $this->session->getFrontendSession()->getCustomer()->isLoggedIn()
        ) {
            // Try to fetch the heidelpay customer by its shop id (kKunde).
            try {
                $customer = $this->getCurrentConnection()->fetchCustomerByExtCustomerId(
                    (string) $this->session->getFrontendSession()->getCustomer()->getID()
                );

                $this->session->set(SessionHelper::KEY_CUSTOMER_ID, $customer->getId());
            } catch (Exception $exc) {
                $this->session->set(SessionHelper::KEY_CUSTOMER_ID, -1);
                $this->debugLog('Tried to fetch customer by kKunde: ' . Text::convertUTF8($exc->getMessage()));
            }
        }

        return $this->session->get(SessionHelper::KEY_CUSTOMER_ID);
    }

    /**
     * Fetch a payment from the api.
     *
     * @throws UnzerApiException if there is an error returned on API-request.
     * @throws RuntimeException if there is an error while using the SDK
     * @param string|null $paymentId
     * @return Payment
     */
    public function fetchPayment(?string $paymentId = null): Payment
    {
        if (is_null($paymentId)) {
            $paymentId = $this->session->get(SessionHelper::KEY_PAYMENT_ID);
        }

        return $this->getCurrentConnection()->fetchPayment($paymentId);
    }

    /**
     * Get transaction of a payment.
     *
     * @param Payment $payment
     * @return AbstractTransactionType
     */
    public function getPaymentTransaction(Payment $payment): AbstractTransactionType
    {
        $transaction = $payment->getAuthorization();
        if (!$transaction instanceof Authorization) {
            $transaction = $payment->getChargeByIndex(0);
        }

        return $transaction;
    }

    /**
     * Fetch a payment type from the api.
     *
     * @throws UnzerApiException if there is an error returned on API-request.
     * @throws RuntimeException if there is an error while using the SDK
     * @param string|null $paymentTypeId
     * @return BasePaymentType
     */
    public function fetchPaymentType(?string $paymentTypeId = null): BasePaymentType
    {
        if (is_null($paymentTypeId)) {
            $paymentTypeId = $this->session->get(
                $this->session->buildSessionKey(
                    [SessionHelper::KEY_CHECKOUT_SESSION, SessionHelper::KEY_RESOURCE_ID]
                )
            );
        }

        return $this->getCurrentConnection()->fetchPaymentType($paymentTypeId);
    }

    /**
     * Checks if a payment method supports the shipment call.
     *
     * @param BasePaymentType $paymentType
     * @return bool
     */
    public function supportsShipment(BasePaymentType $paymentType): bool
    {
        return in_array(get_class($paymentType), self::SUPPORTS_SHIPMENT);
    }

    /**
     * Checks if a payment method should do a full charge before the shipment call.
     *
     * @param BasePaymentType $paymentType
     * @return bool
     */
    public function shouldChargeBeforeShipping(BasePaymentType $paymentType): bool
    {
        return in_array(get_class($paymentType), self::SHOULD_CHARGE_BEFORE_SHIPPING);
    }

    /**
     * Redirect transaction to external payment provider.
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @param AbstractTransactionType $transaction
     * @param Bestellung $order
     * @param array $postData
     * @return void
     */
    public function redirectTransaction(
        AbstractTransactionType $transaction,
        Bestellung $order,
        array $postData = []
    ): void {
        $this->session->set(SessionHelper::KEY_ORDER_ID, $order->cBestellNr);
        $this->session->set(SessionHelper::KEY_PAYMENT_ID, $transaction->getPaymentId());
        $this->session->set(SessionHelper::KEY_SHORT_ID, $transaction->getShortId());
        $this->session->set(SessionHelper::KEY_CONFIRM_POST_ARRAY, $postData);
        $this->session->set(
            SessionHelper::KEY_CART_CHECKSUM,
            Cart::getChecksum($this->session->getFrontendSession()->getCart())
        );

        header('Location: ' . $transaction->getRedirectUrl());
        exit();
    }

    /**
     * Map JTL ISO to iso used bei heidelpay
     *
     * @see https://docs.heidelpay.com/docs/web-integration#section-localization-and-languages
     * @param string $iso
     * @return string
     */
    public function mapToLocale(string $iso): string
    {
        $mapping = [
            'ger' => 'de-DE',
            'dut' => 'nl-NL',
            'fin' => 'fi',
            'dan' => 'da',
            'fre' => 'fr-FR',
            'ita' => 'it-IT',
            'spa' => 'es-ES',
            'por' => 'pt-PT',
            'slo' => 'sk-SK',
            'cze' => 'cs-CZ',
            'pol' => 'pl-PL',
        ];

        return $mapping[$iso] ?? 'en-GB';
    }
}
