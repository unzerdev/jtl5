<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Controllers;

use JTL\Helpers\Request;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerPaylaterInstallment;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepReviewOrderInterface;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;

/**
 * Payment Frontend Controller.
 *
 * Hooks into the different payment steps, to provide additional functionallity to the payment methods.
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers
 */
class PaymentController extends Controller
{
    public const STATE_ABORT = 'abort';
    public const STATE_HANDLE_ADDITIONAL = 'additional';
    public const STATE_HANDLE_ORDER_REVIEW = 'review';
    public const STATE_HANDLE_FINILIZED_ORDER = 'finilized';

    /**
     * @inheritDoc
     */
    public function handle(): string
    {
        /** @var SessionHelper $session */
        $session = Shop::Container()->get(SessionHelper::class);
        $payment = $session->getFrontendSession()->get('Zahlungsart');

        // Clear session if the user changed the currency otherwise we might use the wrong IDs for a new keypair
        if (Request::verifyGPDataString('curr')) {
            $session->clearCheckoutSession();
            $session->clear(SessionHelper::KEY_CUSTOMER_ID);
        }

        // Clear Payment Data if the customer wants to change his payment or shipping method
        if (
            Shop::getPageType() === \PAGE_BESTELLVORGANG &&
            (Request::verifyGPCDataInt('editZahlungsart') > 0 || Request::verifyGPCDataInt('editVersandart') > 0)
        ) {
            $session->clearCheckoutSession();

            // clear ids to avoid using ids for other keypairs in which they might not exist
            $session->clear(SessionHelper::KEY_CUSTOMER_ID);
        }

        if (Shop::getPageType() === \PAGE_BESTELLVORGANG && $payment) {
            $checkoutSession = $session->get(SessionHelper::KEY_CHECKOUT_SESSION);
            $paymentMethod = Method::create($payment->cModulId);

            // Not a heidelpay method, abort!
            if (!$paymentMethod instanceof HeidelpayPaymentMethod) {
                return self::STATE_ABORT;
            }

            // Instalment Info
            if ($paymentMethod instanceof UnzerPaylaterInstallment) {
                $method = $this->config->get(Config::PQ_METHOD_INSTALMENT_INFO, 'after');
                $data = [
                    'info' => $this->plugin->getLocalization()->getTranslation(Config::LANG_INSTLAMENT_INFO)
                ];

                pq(
                    $this->config->get(Config::PQ_SELECTOR_INSTALMENT_INFO, '#complete-order-button')
                )->{$method}(
                    $this->view('template/instalment_info', $data),
                );
            }

            // Review Order => plugin session contains checkoutSession
            if ($checkoutSession && $paymentMethod instanceof HandleStepReviewOrderInterface) {
                $this->debugLog('Handle Review Order Step', get_class($paymentMethod));
                $template = $paymentMethod->handleStepReviewOrder($this->smarty);

                if ($template) {
                    $this->debugLog('Add Template: ' . $template, get_class($paymentMethod));

                    $pqMethod = $this->config->get(Config::PQ_METHOD_REVIEW_STEP, 'append');
                    pq(
                        $this->config->get(Config::PQ_SELECTOR_ERRORS, '#order-confirm')
                    )->$pqMethod($this->view($template));
                }

                return self::STATE_HANDLE_ORDER_REVIEW;
            }
        }

        // HP-121: Clear Plugin Session on the order status page in case that a user aborted its payment process,
        // otherwise the existing plugin session would mess things up
        if (Shop::getPageType() === \PAGE_BESTELLSTATUS || Shop::getPageType() === \PAGE_BESTELLABSCHLUSS) {
            $session->clear();

            // clear ids to avoid using ids for other keypairs in which they might not exist
            $session->clear(SessionHelper::KEY_CUSTOMER_ID);
            $session->clear(SessionHelper::KEY_ORDER_ID);
        }

        return self::STATE_ABORT;
    }
}
