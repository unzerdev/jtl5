<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use Exception;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\HirePurchaseDirectDebit;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Cart\Cart;
use JTL\Checkout\Bestellung;
use JTL\Checkout\ZahlungsInfo;
use JTL\Helpers\Text;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepReviewOrderInterface;
use Plugin\s360_unzer_shop5\src\Payments\PaymentHandler;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\Compatibility;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use Plugin\s360_unzer_shop5\src\Utils\TranslatorTrait;
use RuntimeException;

require_once PFAD_ROOT . PFAD_INCLUDES . '/bestellabschluss_inc.php';

/**
 * Heidelpay FlexiPay Installment (Hire Purchase) Payment Method.
 *
 * As the name suggests, **hire purchase** is a payment method where the customer can buy your goods
 * or services via **instalments**. That way you as the merchant receive the full amount right away and
 * your customers have the convenience to pay partly (with interest) every month.
 *
 * Customers have the option to choose which installment plan they want to use.
 * 6 and 3 months are the most common, but other plans are possible as well.
 *
 * @see https://docs.heidelpay.com/docs/hire-purchase-payment
 * @deprecated
 */
class HeidelpayHirePurchaseDirectDebit extends HeidelpayPaymentMethod implements
    HandleStepAdditionalInterface,
    HandleStepReviewOrderInterface
{
    use HasBasket;
    use HasCustomer;
    use HasMetadata;
    use SupportsB2B;
    use TranslatorTrait;

    public const TEMPLATE_REVIEW_ORDER = 'template/hire_purchase_direct_debit';
    public const ATTR_PDF_LINK = 'unzer_rate_pdf_link';
    public const ATTR_TOTAL_AMOUNT = 'unzer_rate_total_amount';
    public const ATTR_TOTAL_PURCHASE_AMOUNT = 'unzer_rate_total_purchase_amount';
    public const ATTR_TOTAL_INTEREST_AMOUNT = 'unzer_rate_total_interest_amount';

    /**
     * Save the instalment rate information.
     *
     * @param Bestellung $order
     * @param Charge $transaction
     * @return array
     */
    public function getOrderAttributes(Bestellung $order, AbstractTransactionType $transaction): array
    {
        try {
            /** @var Authorization|null $auth */
            $auth = $transaction->getPayment()->getAuthorization();

            /** @var HirePurchaseDirectDebit $type */
            $type = $transaction->getPayment()->getPaymentType();

            // Save Payment Info
            $oPaymentInfo = new ZahlungsInfo(0, $order->kBestellung);
            $oPaymentInfo->kKunde            = $order->kKunde;
            $oPaymentInfo->kBestellung       = $order->kBestellung;
            $oPaymentInfo->cInhaber          = Text::convertUTF8($type->getAccountHolder() ?? '');
            $oPaymentInfo->cIBAN             = Text::convertUTF8($type->getIban() ?? '');
            $oPaymentInfo->cBIC              = Text::convertUTF8($type->getBic() ?? '');
            $oPaymentInfo->cKontoNr          = $oPaymentInfo->cIBAN;
            $oPaymentInfo->cBLZ              = $oPaymentInfo->cBIC;
            $oPaymentInfo->cBankName         = '';
            $oPaymentInfo->cKartenNr         = '';
            $oPaymentInfo->cCVV              = '';

            isset($oPaymentInfo->kZahlungsInfo) ? $oPaymentInfo->updateInDB() : $oPaymentInfo->insertInDB();

            if (!empty($auth)) {
                return [
                    self::ATTR_PDF_LINK              => $auth->getPDFLink(),
                    self::ATTR_TOTAL_AMOUNT          => $type->getTotalAmount(),
                    self::ATTR_TOTAL_PURCHASE_AMOUNT => $type->getTotalPurchaseAmount(),
                    self::ATTR_TOTAL_INTEREST_AMOUNT => $type->getTotalInterestAmount(),
                ];
            }
        } catch (Exception $exc) {
            $this->errorLog(
                'An exception was thrown while trying to get the pdf link order attribute '
                . Text::convertUTF8($exc->getMessage()),
                static::class
            );
        }

        return [
            self::ATTR_PDF_LINK              => null,
            self::ATTR_TOTAL_AMOUNT          => null,
            self::ATTR_TOTAL_PURCHASE_AMOUNT => null,
            self::ATTR_TOTAL_INTEREST_AMOUNT => null,
        ];
    }

    /**
     * Load effective interest from config and order amount.
     *
     * Note that the customer is a mandatory parameter and must at least contain name, address, email and birthdate.
     *
     * @param JTLSmarty $view
     * @return void
     */
    public function handleStepAdditional(JTLSmarty $view): void
    {
        // Create or fetch customer resource
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer(
            $this->adapter,
            $this->sessionHelper,
            false
        );
        $customer->setEmail($shopCustomer->cMail);
        $customer->setShippingAddress(
            $this->createHeidelpayAddress(
                $this->sessionHelper->getFrontendSession()->get('Lieferadresse')
            )
        );
        $this->debugLog('Customer Resource: ' . $customer->jsonSerialize(), static::class);

        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);
        $data = $view->getTemplateVars('hpPayment') ?: [];
        $data['customer'] = $customer;
        $data['customerId'] = $customer->getId();
        $data['effectiveInterest'] = str_replace(
            ',',
            '.',
            $config->getPaymentSetting('effectiveInterest', $this->moduleID)
        );
        $data['amount'] = round(
            $this->sessionHelper->getFrontendSession()->getCart()->gibGesamtsummeWaren(true),
            2
        );
        $data['currency'] = $this->sessionHelper->getFrontendSession()->getCurrency()->getCode();
        $data['orderDate'] = date('Y-m-d');

        $view->assign('hpPayment', $data);
    }

    /**
     * @inheritDoc
     */
    public function isValid(object $customer, Cart $cart): bool
    {
        //! Note: Payment Method is deprecated -> should not be used anymore
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isSelectable(): bool
    {
        //! Note: Payment Method is deprecated -> should not be used anymore
        return false;
    }

    /**
     * Check if payment settings are correct.
     * @inheritDoc
     */
    public function isValidIntern($args = []): bool
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);
        $effectiveInterest = $config->getPaymentSetting('effectiveInterest', $this->moduleID);
        if (empty($effectiveInterest)) {
            $this->setState(self::STATE_NOT_CONFIGURED);
            $this->doLog('Invalid Configuration. effectiveInterest is not set', LOGLEVEL_ERROR);
            return false;
        }

        return parent::isValidIntern($args);
    }

    /**
     * Save customer resource id in the session and authorize the order amount.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return bool
     */
    public function validateAdditional(): bool
    {
        $postPaymentData = $_POST['paymentData'] ?? [];

        // Save Customer ID if it exists
        if (isset($postPaymentData['customerId'])) {
            $this->sessionHelper->set(SessionHelper::KEY_CUSTOMER_ID, $postPaymentData['customerId']);

            // Call parent (also sets payment resource id which we need for the authorize call)
            if (parent::validateAdditional()) {
                return $this->authorizeInstalmentTransaction();
            }
        }

        return parent::validateAdditional();
    }

    /**
     * Fetch the selected instalment plan and show the customer the details.
     *
     * @return string|null
     */
    public function handleStepReviewOrder(JTLSmarty $view): ?string
    {
        $this->handler->prepareView();

        try {
            $paymentId = $this->sessionHelper->get(SessionHelper::KEY_PAYMENT_ID);
            $payment = $this->adapter->getCurrentConnection()->fetchPayment($paymentId);

            // If currency or basket change, redirect to select payment screen to reauthorize new amounts!
            if ($this->handler->currencyChanged($payment) || $this->handler->basketChanged($payment)) {
                $this->sessionHelper->clearCheckoutSession();
                $this->sessionHelper->clear(SessionHelper::KEY_CART_CHECKSUM);
                $this->sessionHelper->addErrorAlert(
                    'Aborting Checkout. Currency or Basket mismatch. Reauthorization needed!',
                    $this->trans(Config::LANG_CONFIRMATION_CHECKSUM),
                    'basketMismatch',
                    PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL,
                    static::class
                );

                return null;
            }

            // Abort, if we somehow have no authorization yet!
            /** @var Authorization|null $auth */
            $auth = $payment->getAuthorization();

            if (empty($auth)) {
                $this->errorLog(
                    'Authorization for payment id ' . $paymentId . ' is missing. Aborting Payment Process',
                    static::class
                );
                $this->sessionHelper->redirectError(
                    $this->trans(Config::LANG_PAYMENT_PROCESS_EXCEPTION),
                    'heidelpayMissingAuth',
                    PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL
                );
            }

            /** @var HirePurchaseDirectDebit $type */
            $type = $payment->getPaymentType();

            $data = [
                'pdfLink'             => $auth->getPDFLink(),
                'totalAmount'         => $type->getTotalAmount(),
                'totalPurchaseAmount' => $type->getTotalPurchaseAmount(),
                'totalInterestAmount' => $type->getTotalInterestAmount(),
                'currency'            => $payment->getAmount()->getCurrency(),
                'lang'                => [
                    'confirmTitle'        => $this->trans(Config::LANG_CONFIRM_INSTALLMENT_TITLE),
                    'downloadAndConfirm'  => $this->trans(Config::LANG_DOWNLOAD_AND_CONFIRM_INSTALLMENT_PLAN),
                    'totalPurchaseAmount' => $this->trans(Config::LANG_TOTAL_PURCHASE_AMOUNT),
                    'totalInterestAmount' => $this->trans(Config::LANG_TOTAL_INTEREST_AMOUNT),
                    'totalAmount'         => $this->trans(Config::LANG_TOTAL_AMOUNT),
                    'downloadYourPlan'    => sprintf(
                        $this->trans(Config::LANG_DOWNLOAD_YOUR_PLAN),
                        $auth->getPDFLink()
                    ),
                    'closeModal'          => $this->trans(Config::LANG_CLOSE_MODAL)
                ]
            ];

            $view->assign('hpInstalment', $data);

            return self::TEMPLATE_REVIEW_ORDER;
        } catch (UnzerApiException $exc) {
            $msg = $exc->getMerchantMessage() . ' | Id: ' . $exc->getErrorId() . ' | Code: ' . $exc->getCode();
            $this->errorLog(Text::convertUTF8($msg), static::class);
            $this->sessionHelper->redirectError(
                Text::convertUTF8($exc->getClientMessage()),
                'heidelpayTransactionError',
                PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL
            );
        } catch (RuntimeException $exc) {
            $this->errorLog(
                'An exception was thrown while using the Heidelpay SDK: ' . Text::convertUTF8($exc->getMessage()),
                static::class
            );
            $this->sessionHelper->redirectError(
                $this->trans(Config::LANG_PAYMENT_PROCESS_RUNTIME_EXCEPTION),
                'paymentRuntimeException',
                PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL
            );
        } catch (Exception $exc) {
            $this->errorLog('An error occured in the payment process: ' . $exc->getMessage(), static::class);
            $this->sessionHelper->redirectError(
                $this->trans(Config::LANG_PAYMENT_PROCESS_EXCEPTION),
                'paymentRuntimeException',
                PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL
            );
        }

        return null;
    }

    /**
     * Authorize the transaction.
     *
     * Note that the customer is a mandatory parameter and must at least contain name, address, email and birthdate.
     *
     * Basket id (= Order ID) is mandatory for Hire Purchase! In the finalize call we need to pass the order id, so
     * that jtl uses our generated order id instead of creating a new one!
     *
     * Please make sure that all amounts are correct, otherwise the Shipment call will fail.
     * Within basket do not use the discount element. It is not supported within Hire Purchase.
     *
     * @return bool
     */
    private function authorizeInstalmentTransaction(): bool
    {
        // We need to register an order id here otherwise the auth call will fail!
        // @see: BillPay for similiar behavior
        if (Compatibility::isShopAtLeast52()) {
            $orderId = $this->sessionHelper->get(SessionHelper::KEY_ORDER_ID) ?? getOrderHandler()->createOrderNo();
        } else {
            $orderId = $this->sessionHelper->get(SessionHelper::KEY_ORDER_ID) ?? \baueBestellnummer();
        }

        $this->sessionHelper->set(SessionHelper::KEY_ORDER_ID, $orderId);

        // Create or fetch customer resource
        $session = $this->sessionHelper->getFrontendSession();
        $shopCustomer = $session->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer(
            $this->adapter,
            $this->sessionHelper,
            false
        );
        $customer->setEmail($shopCustomer->cMail);
        $customer->setShippingAddress($this->createHeidelpayAddress($session->get('Lieferadresse')));
        $customer->setBillingAddress($this->createHeidelpayAddress($session->getCustomer()));
        $this->debugLog('Customer Resource: ' . $customer->jsonSerialize(), static::class);

        // Update existing customer resource if needed
        if ($customer->getId()) {
            $customer = $this->adapter->getCurrentConnection()->updateCustomer($customer);
            $this->debugLog('Updated Customer Resource: ' . $customer->jsonSerialize(), static::class);
        }

        // Create Basket
        $basket = $this->createHeidelpayBasket($session->getCart(), $session->getCurrency(), $session->getLanguage());
        $basket->setOrderId($orderId);
        $this->debugLog('Basket Resource: ' . $basket->jsonSerialize(), static::class);

        try {
            /** @var HirePurchaseDirectDebit $paymentType */
            $paymentType = $this->adapter->fetchPaymentType();

            // Authorize Transaction
            $auth = (new Authorization(
                $paymentType->getTotalPurchaseAmount(),
                $session->getCurrency()->getCode(),
                Shop::Container()->getLinkService()->getStaticRoute('bestellvorgang.php')
            ))->setOrderId($orderId);

            $authorization = $this->adapter->getCurrentConnection()->performAuthorization(
                $auth,
                $paymentType,
                $customer,
                $this->createMetadata(),
                $basket
            );

            $this->debugLog('Authorization Transaction: ' . $authorization->jsonSerialize(), static::class);
            $this->sessionHelper->set(SessionHelper::KEY_PAYMENT_ID, $authorization->getPaymentId());

            // Save Basket Checksum
            $this->sessionHelper->set(
                SessionHelper::KEY_CART_CHECKSUM,
                Cart::getChecksum($this->sessionHelper->getFrontendSession()->getCart())
            );

            if ($authorization->isSuccess()) {
                return true;
            }

            $msg = $authorization->getMessage()->getMerchant() . ' | Code: ' . $authorization->getMessage()->getCode();
            $this->errorLog(Text::convertUTF8($msg), static::class);
            $this->sessionHelper->redirectError(
                Text::convertUTF8($authorization->getMessage()->getCustomer()),
                'heidelpayTransactionError',
                PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL
            );
        } catch (UnzerApiException $exc) {
            $msg = $exc->getMerchantMessage() . ' | Id: ' . $exc->getErrorId() . ' | Code: ' . $exc->getCode();
            $this->errorLog(Text::convertUTF8($msg), static::class);
            $this->sessionHelper->redirectError(
                Text::convertUTF8($exc->getClientMessage()),
                'UnzerApiException',
                PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL
            );
        } catch (RuntimeException $exc) {
            $this->errorLog(
                'An exception was thrown while using the Heidelpay SDK: ' . Text::convertUTF8($exc->getMessage()),
                static::class
            );
            $this->sessionHelper->redirectError(
                $this->trans(Config::LANG_PAYMENT_PROCESS_RUNTIME_EXCEPTION),
                'paymentRuntimeException',
                PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL
            );
        } catch (Exception $exc) {
            $this->errorLog('An error occured in the payment process: ' . $exc->getMessage(), static::class);
            $this->sessionHelper->redirectError(
                $this->trans(Config::LANG_PAYMENT_PROCESS_EXCEPTION),
                'paymentRuntimeException',
                PaymentHandler::REDIRECT_TO_PAYMENT_SELECTION_URL
            );
        }
    }

    /**
     * Charge the authorized amount.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param BasePaymentType $payment
     * @param Bestellung|stdClass $order
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType
    {
        $payment = $this->adapter->fetchPayment();
        return $payment->charge();
    }
}
