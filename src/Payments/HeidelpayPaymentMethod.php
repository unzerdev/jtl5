<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Payments;

use Exception;
use JTL\Alert\Alert;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use JTL\Backend\Notification;
use JTL\Backend\NotificationEntry;
use JTL\Checkout\Bestellung;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Plugin\Payment\Method;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\NotificationInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\PaymentStatusInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasPayStatus;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasState;
use Plugin\s360_unzer_shop5\src\Payments\Traits\PriceCurrencyConverter;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\JtlLoggerTrait;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use Plugin\s360_unzer_shop5\src\Utils\TranslatorTrait;
use RuntimeException;
use stdClass;

/**
 * Basic Heidepay Payment Method
 *
 * @package Plugin\s360_unzer_shop5\src\Payments
 */
abstract class HeidelpayPaymentMethod extends Method implements NotificationInterface, PaymentStatusInterface
{
    // Order Attributes
    public const ATTR_IBAN = 'unzer_iban';
    public const ATTR_BIC = 'unzer_bic';
    public const ATTR_TRANSACTION_DESCRIPTOR = 'unzer_transaction_descriptor';
    public const ATTR_ACCOUNT_HOLDER = 'unzer_account_holder';
    public const ATTR_SHORT_ID = 'unzer_short_id';
    public const ATTR_PAYMENT_ID = 'unzer_payment_id';
    public const ATTR_PAYMENT_TYPE_ID = 'unzer_payment_type_id';

    use JtlLoggerTrait;
    use TranslatorTrait;
    use HasState;
    use HasPayStatus;
    use PriceCurrencyConverter;

    /**
     * @var PluginInterface
     */
    protected $plugin;

    /**
     * @var SessionHelper
     */
    protected $sessionHelper;

    /**
     * @var HeidelpayApiAdapter
     */
    protected $adapter;

    /**
     * @var PaymentHandler
     */
    protected $handler;

    /**
     * @var string
     */
    public $hash = '';

    /**
     * Perform the transaction on the payment type (i.e. authorize or charge).
     *
     * @param BasePaymentType $payment
     * @param stdClass|Bestellung $order
     * @return AbstractTransactionType
     * @throws UnzerApiException A UnzerApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    abstract protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType;

    /**
     * Get order attributes for a specific order
     *
     * @param Bestellung $order
     * @param AbstractTransactionType $transaction
     * @return array
     */
    public function getOrderAttributes(Bestellung $order, AbstractTransactionType $transaction): array
    {
        return [];
    }

    /**
     * Load dependencies
     *
     * @param integer $nAgainCheckout
     * @return self
     */
    public function init($nAgainCheckout = 0): self
    {
        parent::init($nAgainCheckout);

        $this->plugin = Shop::Container()->get(Config::PLUGIN_ID);
        $this->sessionHelper = Shop::Container()->get(SessionHelper::class);
        $this->handler = Shop::Container()->get(PaymentHandler::class);
        $this->adapter = Shop::Container()->get(HeidelpayApiAdapter::class);
        $this->handler->setPaymentMethod($this);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function initBackendNotification(): void
    {
        if (Shop::isFrontend() || $this->isValidIntern()) {
            return;
        }

        $message = $this->getStateMessage($this->getState());
        if (!empty($message)) {
            $payMethod = $this->plugin->getPaymentMethods()->getMethodByID($this->moduleID);

            if ($payMethod !== null) {
                $this->kZahlungsart = $payMethod->getMethodID();
            }

            $token = $this->sessionHelper->getFrontendSession()->get('jtl_token');
            $notification = new NotificationEntry(
                NotificationEntry::TYPE_WARNING,
                __('hpNotificationHeader'),
                \sprintf($message, __($this->caption)),
                'zahlungsarten.php?kZahlungsart=' . $this->kZahlungsart . '&token=' . $token
            );
            $notification->setPluginId((string) $this->plugin->getID());
            Notification::getInstance()->addNotify($notification);
        }
    }

    /**
     * Get the payment hashes of this payment method.
     *
     * - `cId`: Payment-ID, which is also in `tbestellid`
     * - `txn_id`: Payment ID of the payment service provider.
     *          If this exists, the payment has been processed by the provider.
     *
     * @param int $orderID
     * @return stdClass|null
     */
    protected function getPaymentHashes(int $orderID): ?stdClass
    {
        if ($orderID > 0) {
            $hashes = Shop::Container()->getDB()->executeQueryPrepared(
                'SELECT cId, txn_id
                    FROM tzahlungsid
                    WHERE kBestellung = :orderID',
                ['orderID' => $orderID],
                1
            );

            return $hashes === false ? null : $hashes;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getNotificationURL($hash): string
    {
        $url = parent::getNotificationURL($hash);
        return $url . (strpos($url, '?') === false ? '?' : '&') . 'state=' . $this->getState();
    }

    /**
     * Get the right returl url depending the payment state (duringCheckout).
     *
     * @inheritDoc
     */
    public function getReturnURL($order): string
    {
        if ($order === null) {
            return Shop::getURL() . '/bestellvorgang.php';
        }

        // If the payment was not processed by the payment provider yet, we return the notify.php url
        $hashes = $this->getPaymentHashes((int) $order->kBestellung ?? -1);
        if (isset($hashes) && !empty($hashes->cId) && empty($hashes->txn_id)) {
            return Shop::getURL() . '/includes/modules/notify.php?ph=' . $hashes->cId;
        }

        // If we have preorder enabled and not visited notify.php yet (ie order is not finalized yet)
        if (empty($order->kBestellung) && $this->duringCheckout && $this->handler->isRedirectPayment($this)) {
            return $this->getNotificationURL($this->hash);
        }

        return parent::getReturnURL($order);
    }

    /**
     * Checks if the additional template should be displayed (e.g. if user has not filled out the form yet).
     *
     * Return false, if the additional template should be displaed, otherwise return true.
     *
     * @param array $post
     * @return boolean
     */
    public function handleAdditional($post): bool
    {
        $this->handler->prepareView();
        $paymentData = $this->sessionHelper->getCheckoutSession();

        // Additional Step => Payment method exists and session contains customerId (at most)
        if (empty($paymentData) && $this instanceof HandleStepAdditionalInterface) {
            $this->debugLog('Handle Additional Payment Step', get_class($this));
            $this->handleStepAdditional(Shop::Smarty());
        }

        if (empty($paymentData['resourceId'])) {
            return false;
        }

        return parent::handleAdditional($post);
    }

    /**
     * Validate inputs of additional template.
     *
     * Return false if they are invalid
     * Return true if they are valid
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return bool
     */
    public function validateAdditional(): bool
    {
        $paymentData = $this->sessionHelper->getCheckoutSession();
        $postPaymentData = $_POST['paymentData'] ?? [];

        // Reset Payment Data if the customer wants to change his payment or shipping method
        if (Request::verifyGPCDataInt('editZahlungsart') > 0 || Request::verifyGPCDataInt('editVersandart') > 0) {
            $this->sessionHelper->clearCheckoutSession();
            return false;
        }

        // Check Form Inputs
        if (isset($postPaymentData['resourceId'])) {
            // Abort if CSRF Token is invalid
            if (!Form::validateToken()) {
                $this->sessionHelper->redirectError(
                    $this->trans(Config::LANG_INVALID_TOKEN),
                    'invalidToken'
                );
                return false;
            }

            // Save Payment Data
            $this->sessionHelper->setCheckoutSession($postPaymentData['resourceId']);
            return true;
        }

        // Resource ID already exists
        if (!empty($paymentData['resourceId'])) {
            return parent::validateAdditional();
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isValidIntern($args = []): bool
    {
        if ($this->duringCheckout) {
            $this->state = self::STATE_DURING_CHECKOUT;
        }

        return parent::isValidIntern($args);
    }

    /**
     * If this methods returns true, then notify.php uses the URL from getReturnURL (@see self::getReturnUrl).
     *
     * Here, this is the case if we have a payment no matter the actual state of it.
     *
     * @inheritDoc
     */
    public function redirectOnPaymentSuccess(): bool
    {
        $this->debugLog('redirectOnPaymentSuccess payStatus: ' . $this->getPayStatus(), static::class);
        return true;
    }

    /**
     * If this methods returns true, then notify.php redirects the customer in case of a cancelation.
     *
     * @inheritDoc
     */
    public function redirectOnCancel(): bool
    {
        $this->debugLog('redirectOnCancel payStatus: ' . $this->getPayStatus(), static::class);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function finalizeOrder(Bestellung $order, string $hash, array $args): bool
    {
        // Validate Payment Request (check: currency changed, order amount, cart checksum)
        if (isset($args['state']) && $args['state'] == self::STATE_DURING_CHECKOUT) {
            $payment = $this->adapter->fetchPayment();

            // Invalid Request (basket, currency mismatch)
            if (!$this->handler->validatePaymentRequest($payment)) {
                $this->setPayStatus(self::PAYSTATUS_FAILED);
                $this->deletePaymentHash($hash);
                return false;
            }

            // Payment is neither completed nor pending -> either an error occured or the user aborted the process
            // In either case, we do not finalize the order and let the user try again!
            if (!$payment->isCompleted() && !$payment->isPending()) {
                $this->setPayStatus(self::PAYSTATUS_FAILED);
                $this->deletePaymentHash($hash);

                $transaction = $this->adapter->getPaymentTransaction($payment);
                $this->sessionHelper->addErrorAlert(
                    Text::convertUTF8($transaction->getMessage()->getMerchant()),
                    Text::convertUTF8($transaction->getMessage()->getCustomer()),
                    'transactionError',
                    null,
                    static::class
                );

                return false;
            }

            return true;
        }

        return parent::finalizeOrder($order, $hash, $args);
    }

    /**
     * Is called by notify.php when the customer is redirected back from the payment provider.
     *
     * If everything was succesful, notify.php will redirect to bestellabschluss.php with GET-Parameter "i"
     * or status.php, depending on the shop configuration.
     *
     * This leads to the correct handling of the order within the JTL bestellabschluss (i.e. no re-creating the order)
     *
     * NOTE: bestellabschluss.php will handle order uploads and cleanup of the JTL Session data,
     * but status.php will not, so must must clean the session data in case of success!
     *
     * @inheritDoc
     */
    public function handleNotification($order, $hash, $args): void
    {
        parent::handleNotification($order, $hash, $args);

        $this->handler->finishPayment($hash);

        try {
            $payment = $this->adapter->fetchPayment();
            $transaction = $this->adapter->getPaymentTransaction($payment);

            // Preorder = 1 => Order was not finalized before and therefore no order mapping was saved. Do this now!
            if (isset($args['state']) && $args['state'] == self::STATE_DURING_CHECKOUT) {
                // update cBestellNummer because we already have generated it but have no way of telling JTL to use
                // it in the notify.php so a new one gets generated resulting in mismatched order ids ...
                $order->cBestellNr = $transaction->getOrderId();
                $order->updateInDB();

                $this->handler->saveOrderMapping($transaction->getPayment(), $order);
            }

            // The payment process has been successful (probably, as it can be pending).
            if ($payment->isCompleted() || $payment->isPending()) {
                $this->sessionHelper->clear(SessionHelper::KEY_CONFIRM_POST_ARRAY);

                // Abort Checkout if there is an error with the transaction
                if ($transaction->isError()) {
                    $this->deletePaymentHash($hash);

                    $this->sessionHelper->addErrorAlert(
                        'Aborting Checkout. Transaction was not successfull',
                        $this->trans(Config::LANG_PAYMENT_PROCESS_EXCEPTION),
                        'transactionAborted',
                        null,
                        static::class
                    );
                    return;
                }

                // Accept successful payment and clean up session
                $this->handler->acceptPayment($order, $hash, $transaction);
                $this->sessionHelper->clear();
                $this->sessionHelper->getFrontendSession()->cleanUp();
                return;
            }

            // If the payment is neither successful nor pending, something went wrong.
            $this->handler->revokePayment($order, $hash, $transaction);
            $this->sessionHelper->getFrontendSession()->cleanUp();

            $this->errorLog(Text::convertUTF8($transaction->getMessage()->getMerchant()), static::class);
            $this->sessionHelper->getAlertService()->addAlert(
                Alert::TYPE_ERROR,
                Text::convertUTF8($transaction->getMessage()->getCustomer()),
                'transactionError',
                ['saveInSession' => true]
            );
        } catch (UnzerApiException $exc) {
            $merchant = $exc->getMerchantMessage() . ' | Id: ' . $exc->getErrorId() . ' | Code: ' . $exc->getCode();
            $this->errorLog($merchant, static::class);
            $this->sessionHelper->getAlertService()->addAlert(
                Alert::TYPE_ERROR,
                Text::convertUTF8($exc->getClientMessage()),
                'UnzerApiException',
                ['saveInSession' => true]
            );
        } catch (RuntimeException $exc) {
            $merchant = 'An exception was thrown while using the Heidelpay SDK: ';
            $merchant .= Text::convertUTF8($exc->getMessage());

            $this->errorLog($merchant, static::class);
            $this->sessionHelper->getAlertService()->addAlert(
                Alert::TYPE_ERROR,
                $this->trans(Config::LANG_PAYMENT_PROCESS_RUNTIME_EXCEPTION),
                'paymentRuntimeException',
                ['saveInSession' => true]
            );
        } catch (Exception $exc) {
            $merchant = 'An error occured in the payment process: ' . $exc->getMessage();
            $this->errorLog($merchant, static::class);
            $this->sessionHelper->getAlertService()->addAlert(
                Alert::TYPE_ERROR,
                $this->trans(Config::LANG_PAYMENT_PROCESS_EXCEPTION),
                'paymentRuntimeException',
                ['saveInSession' => true]
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function preparePaymentProcess(Bestellung $order): void
    {
        $redirectError = null;
        $hashes = $this->getPaymentHashes((int) $order->kBestellung ?? -1);

        // We already processes this order, we just need to finish the payment process
        if (isset($hashes) && !empty($hashes->cId)) {
            $this->handler->finishPayment($hashes->cId);
            return;
        }

        // Preorder State (Preorder = 1), order not finalized
        if ($this->duringCheckout) {
            $order->cBestellNr = \baueBestellnummer();
            $redirectError = PaymentHandler::REDIRECT_ON_FAILURE_URL;
            $this->state = self::STATE_DURING_CHECKOUT;
        }

        try {
            $this->hash  = $this->generateHash($order);
            $paymentType = $this->adapter->fetchPaymentType();
            $transaction = $this->performTransaction($paymentType, $order);

            $this->handler->preparePayment($transaction, $order, $redirectError);
        } catch (UnzerApiException $exc) {
            $this->saveFailedTransaction($transaction, $order);
            $merchant = $exc->getMerchantMessage() . ' | Id: ' . $exc->getErrorId() . ' | Code: ' . $exc->getCode();

            $this->sessionHelper->addErrorAlert(
                Text::convertUTF8($merchant),
                Text::convertUTF8($exc->getClientMessage()),
                'UnzerApiException',
                $redirectError,
                static::class
            );
        } catch (RuntimeException $exc) {
            $this->saveFailedTransaction($transaction, $order);
            $merchant = 'An exception was thrown while using the Heidelpay SDK: ';
            $merchant .= Text::convertUTF8($exc->getMessage());
            $this->sessionHelper->addErrorAlert(
                $merchant,
                $this->trans(Config::LANG_PAYMENT_PROCESS_RUNTIME_EXCEPTION),
                'paymentRuntimeException',
                $redirectError,
                static::class
            );
        } catch (Exception $exc) {
            $this->saveFailedTransaction($transaction, $order);
            $merchant = 'An error occured in the payment process: ' . $exc->getMessage();
            $this->sessionHelper->addErrorAlert(
                $merchant,
                $this->trans(Config::LANG_PAYMENT_PROCESS_EXCEPTION),
                'paymentRuntimeException',
                $redirectError,
                static::class
            );
        }
    }

    /**
     *  If performing the transaction failed, save the order mapping because the order is still created!
     *
     * @param BasePaymentTyp|null $transaction
     * @param Bestellung|stdClass $order
     * @return void
     */
    private function saveFailedTransaction($transaction, $order): void
    {
        if (is_null($transaction) && !empty($order->cBestellNr)) {
            try {
                $payment = $this->adapter->getApi()->fetchPaymentByOrderId($order->cBestellNr);
                $this->handler->saveOrderMapping($payment, $order);
            } catch (Exception $err) {
                $this->errorLog('An error occured in the payment process: ' . $err->getMessage(), static::class);
            }
        }
    }
}
