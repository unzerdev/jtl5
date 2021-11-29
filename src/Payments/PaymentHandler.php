<?php declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Payments;

use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Cart\Cart;
use JTL\Checkout\Bestellung;
use JTL\Helpers\Text;
use JTL\Plugin\Payment\MethodInterface;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Charges\ChargeHandler;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingEntity;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\JtlLoggerTrait;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use Plugin\s360_unzer_shop5\src\Utils\TranslatorTrait;

/**
 * Heidelpay Payment Handler.
 *
 * Handles the payment process on JTL site and does not interact with the Heidepay
 * API directly apart from providing the required parameters for the JS scripts.
 *
 * @package Plugin\s360_unzer_shop5\src\Payments
 */
class PaymentHandler
{
    use JtlLoggerTrait;
    use TranslatorTrait;

    public const REDIRECT_ON_FAILURE_URL = 'bestellvorgang.php';
    public const REDIRECT_TO_PAYMENT_SELECTION_URL = 'bestellvorgang.php?editZahlungsart=1';

    /**
     * @var PluginInterface
     */
    protected $plugin;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SessionHelper
     */
    protected $sessionHelper;

    /**
     * @var HeidelpayApiAdapter
     */
    protected $adapter;

    /**
     * @var ChargeHandler
     */
    protected $charges;

    /**
     * @var OrderMappingModel
     */
    protected $model;

    /**
     * @var HeidelpayPaymentMethod
     */
    protected $paymentMethod;

    /**
     * @param PluginInterface $plugin
     * @param Config $config
     * @param SessionHelper $session
     * @param HeidelpayApiAdapter $adapter
     * @param ChargeHandler $charges
     * @param OrderMappingModel $model
     */
    public function __construct(
        PluginInterface $plugin,
        Config $config,
        SessionHelper $session,
        HeidelpayApiAdapter $adapter,
        ChargeHandler $charges,
        OrderMappingModel $model
    ) {
        $this->plugin = $plugin;
        $this->config = $config;
        $this->session = $session;
        $this->adapter = $adapter;
        $this->charges = $charges;
        $this->model = $model;
    }

    /**
     * Set the current payment method to handle.
     *
     * @param HeidelpayPaymentMethod $paymentMethod
     * @return void
     */
    public function setPaymentMethod(HeidelpayPaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Handle the prepare payment process step for both preorder/duringCheckout variants.
     *
     * Does the following:
     * - in case of preorder=0 (order finalized), save the order mapping
     * - if the transaction is in the error state, add an error alert
     * - if paymentMethod is a redirect and there are no errors, we redirect to the payment provider
     * - if its not a redirect and there are no errors, we act according to the preorder state, as follows:
     *   * Preorder=0: accept payment
     *   * Preorder=1: finalize order, save order mapping, accept payment, redirect to bestellabschluss.php
     *
     * @param AbstractTransactionType $transaction
     * @param Bestellung $order
     * @param string|null $redirect URL to redirect to in case of an error
     * @return void
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function preparePayment(AbstractTransactionType $transaction, Bestellung $order, ?string $redirect): void
    {
        // Preorder = 0, order already finalized -> save order mapping now!
        if ($this->paymentMethod->getState() !== HeidelpayPaymentMethod::STATE_DURING_CHECKOUT) {
            $this->saveOrderMapping($transaction->getPayment(), $order);
        }

        // Redirect Payment
        if (!$transaction->isError() && $this->isRedirectPayment($this->paymentMethod)
            && $transaction->getRedirectUrl() !== null
        ) {
            $this->adapter->redirectTransaction($transaction, $_POST);
        }

        // No-Redirect and no error -> success, so we create the order
        if (!$transaction->isError()) {
            // Preorder=0, order finalized -> just accept the payment.
            if ($this->paymentMethod->getState() !== HeidelpayPaymentMethod::STATE_DURING_CHECKOUT) {
                $this->acceptPayment($order, $this->paymentMethod->hash, $transaction);
                $this->session->getFrontendSession()->cleanUp();
                return;
            }

            // Preorder=1, order not finalized yet -> finalize and save the order
            $finalizedOrder = finalisiereBestellung($transaction->getPayment()->getOrderId() ?? '');
            $this->saveOrderMapping($transaction->getPayment(), $finalizedOrder);
            $this->acceptPayment($finalizedOrder, $this->paymentMethod->hash, $transaction);

            // Pretend we were redirected to notify -> Redirect to success page
            $this->session->clear();
            $orderHash = $this->paymentMethod->getOrderHash($finalizedOrder);
            $this->debugLog('Redirecting to bestellabschluss.php?i=' . $orderHash, get_class($this->paymentMethod));
            header('Location: bestellabschluss.php?i=' . $orderHash);
            exit();
        }

        // An Error occured (Transaction not successful), so we redirect with a failure message
        $message = $transaction->getMessage();
        $merchant = Text::convertUTF8($message->getMerchant()) . ' | Code: ' . $message->getCode();
        $this->session->addErrorAlert(
            $merchant,
            Text::convertUTF8($message->getCustomer()),
            'heidelpayTransactionError',
            $redirect,
            get_class($this->paymentMethod)
        );
    }

    /**
     * This method is called as a callback after processing the transaction on the payment provider.
     *
     * Depending on the state:
     * - if successful (i.e. we have a payment ID), we save it as txn_id
     * - if unsuccessful, we clear the checkout session, so we can try the payment process again
     *
     * @param string $paymentHash
     * @return void
     */
    public function finishPayment(string $paymentHash): void
    {
        $paymentId = $this->session->has(SessionHelper::KEY_PAYMENT_ID);

        if (!empty($paymentId)) {
            $this->paymentMethod->setPayStatus(HeidelpayPaymentMethod::PAYSTATUS_SUCCESS);
            Shop::Container()->getDB()->update('tzahlungsid', 'cId', $paymentHash, (object)[
                'txn_id' => $paymentId
            ]);

            return;
        }

        $this->paymentMethod->setPayStatus(HeidelpayPaymentMethod::PAYSTATUS_FAILED);
        $this->session->clearCheckoutSession();
        $this->paymentMethod->deletePaymentHash($paymentHash);
    }

    /**
     * Called if the payment was successfull.
     *
     * Does the following:
     * - delete payment hash
     * - save payment transaction details
     * - set incoming payment
     * - set order status to paid (if there is no remaining amount)
     * - sent notification mail
     * - set order attributes
     * - clear checkout session
     *
     * @param Bestellung $order
     * @param string $paymentHash
     * @param AbstractTransactionType $transaction
     * @return void
     */
    public function acceptPayment(Bestellung $order, string $paymentHash, AbstractTransactionType $transaction): void
    {
        $this->paymentMethod->setPayStatus(HeidelpayPaymentMethod::PAYSTATUS_SUCCESS);
        $this->paymentMethod->deletePaymentHash($paymentHash);
        $payment = $transaction->getPayment();
        $this->session->clearCheckoutSession();

        /*
         * Handle Pending Orders.
         *
         * Prevent the WaWi from collection an order that is currently PENDING, because
         * maybe the confirmation from external provider is not there yet, i.e. only redirect payments.
         *
         * We use Hook 75, to stop this order from getting synced with the WaWi.
         * Otherwise we continue as normal.
         */
        if ($transaction->isPending() && $this->isRedirectPayment($this->paymentMethod)) {
            Shop::set('360HpOrderPending', true);
            $this->paymentMethod->setPayStatus(HeidelpayPaymentMethod::PAYSTATUS_PENDING);
        }

        // Add incoming payment
        foreach ($payment->getCharges() as $charge) {
            $this->charges->addCharge($charge, $this->paymentMethod, $order);
        }

        // !NOTE: Only use the webhooks, as it seems that remaining = 0 does not mean that the order is paid!
        // Mark order as paid if there is no remaining amount on the payment.
        // if ($payment->getAmount()->getRemaining() == 0) {
        //     $this->paymentMethod->setPayStatus(HeidelpayPaymentMethod::PAYSTATUS_PAID);
        //     $this->charges->markAsPaid($this->paymentMethod, $order);
        // }

        $this->saveOrderMapping($payment, $order); // Update order mapping

        // save order attributes
        $defaultAttrs = [
            HeidelpayPaymentMethod::ATTR_SHORT_ID        => $transaction->getShortId(),
            HeidelpayPaymentMethod::ATTR_PAYMENT_ID      => $payment->getId(),
            HeidelpayPaymentMethod::ATTR_PAYMENT_TYPE_ID => $transaction->getId()
        ];
        $this->model->saveOrderAttributes(
            $order,
            array_merge($defaultAttrs, $this->paymentMethod->getOrderAttributes($order, $transaction))
        );
    }

    /**
     * Called if the payment was unsuccessfull/aborted.
     *
     * Generates a new payment hash
     *
     * @param Bestellung $order
     * @param string $paymentHash
     * @param AbstractTransactionType $transaction
     * @return void
     */
    public function revokePayment(Bestellung $order, string $paymentHash, AbstractTransactionType $transaction): void
    {
        $this->paymentMethod->setPayStatus(HeidelpayPaymentMethod::PAYSTATUS_CANCEL);
        $this->paymentMethod->deletePaymentHash($paymentHash);

        // upsert order hash
        Shop::Container()->getDB()->executeQueryPrepared(
            'INSERT INTO tbestellid (kBestellung, dDatum, cId)
             VALUES (:order, :datum, :id)
             ON DUPLICATE KEY UPDATE kBestellung = :order, dDatum = :datum, cId = :id',
            [
                'order' => $order->kBestellung,
                'datum' => $order->dErstellt,
                'id'    => uniqid('', true)
            ],
            1
        );

        $this->paymentMethod->doLog($transaction->getMessage()->getMerchant());
    }

    /**
     * Validate the order/payment request
     *
     * @param Payment $payment
     * @return bool
     */
    public function validatePaymentRequest(Payment $payment): bool
    {
        if ($this->currencyChanged($payment)) {
            $this->session->addErrorAlert(
                'Aborting Checkout. Currency mismatch.',
                $this->trans(Config::LANG_PAYMENT_PROCESS_EXCEPTION),
                'transactionAborted',
                null,
                get_class($this->paymentMethod)
            );

            return false;
        }

        if ($this->basketChanged($payment)) {
            $this->plugin->getSession->addErrorAlert(
                'Aborting Checkout. Basket mismatch.',
                $this->trans(Config::LANG_PAYMENT_PROCESS_EXCEPTION),
                'transactionAborted',
                null,
                get_class($this->paymentMethod)
            );

            return false;
        }

        return true;
    }

    /**
     * Check if the currency changed compared to what the payment says.
     *
     * @param Payment $payment
     * @return boolean
     */
    public function currencyChanged(Payment $payment): bool
    {
        $currency = $this->session->getFrontendSession()->getCurrency();
        return $currency->cISO !== $payment->getCurrency();
    }

    /**
     * Check if the basket changed compared to what te session and payment are saying.
     *
     * @param Payment $payment
     * @return boolean
     */
    public function basketChanged(Payment $payment): bool
    {
        $currency = $this->session->getFrontendSession()->getCurrency();
        $basket = $this->session->getFrontendSession()->getCart();

        return Cart::getChecksum($basket) !== $this->session->get(SessionHelper::KEY_CART_CHECKSUM)
            || round($basket->gibGesamtsummeWaren(true) * $currency->fFaktor, 2) !== $payment->getAmount()->getTotal();
    }

    /**
     * Save the order mapping data.
     *
     * @param Payment $payment
     * @param Bestellung $order
     * @return void
     */
    public function saveOrderMapping(Payment $payment, Bestellung $order): void
    {
        /** @var Charge $charge */
        $charge = $payment->getChargeByIndex(0);
        $uniqId = $charge->getUniqueId();

        $entity = new OrderMappingEntity();
        $entity->setId((int) $order->kBestellung);
        $entity->setJtlOrderNumber($order->cBestellNr);
        $entity->setPaymentId($payment->getId());
        $entity->setTransactionUniqueId($uniqId);
        $entity->setPaymentState($payment->getStateName());
        $entity->setPaymentTypeId($payment->getPaymentType() ? $payment->getPaymentType()->getId() : '');
        $entity->setPaymentTypeName($payment->getPaymentType() ? $payment->getPaymentType()->getResourceName() : '');

        $this->model->save($entity);
    }

    /**
     * Check if a payment method is a redirect payment method.
     *
     * @param MethodInterface $paymentMethod
     * @return boolean
     */
    public function isRedirectPayment(MethodInterface $paymentMethod): bool
    {
        return $paymentMethod instanceof RedirectPaymentInterface;
    }

    /**
     * Prepare view, i.e. assign variables.
     *
     * @return void.
     */
    public function prepareView(): void
    {
        $smarty = Shop::Smarty();
        $data = $smarty->getTemplateVars('hpPayment') ?: [];

        $data['frontendTemplateUrl'] = $this->plugin->getPaths()->getFrontendURL() . 'template/';
        $data['frontendUrl']         = $this->plugin->getPaths()->getFrontendURL();
        $data['frontendPath']        = $this->plugin->getPaths()->getFrontendPath();
        $data['pluginPath']          = $this->plugin->getPaths()->getBasePath();
        $data['pluginVersion']       = (string) $this->plugin->getCurrentVersion();
        $data['config']              = $this->config->all();
        $data['customerId']          = $this->adapter->fetchCustomerId();
        $data['redirectingNote']     = $this->trans(Config::LANG_REDIRECTING);
        $data['locale']              = $this->adapter->mapToLocale(
            $this->session->getFrontendSession()->getLanguage()->cISOSprache ?? 'eng'
        );

        $smarty->assign('hpPayment', $data);
    }
}
