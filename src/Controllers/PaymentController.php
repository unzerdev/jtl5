<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Controllers;

use JTL\Helpers\Request;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
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

        if (Shop::getPageType() === \PAGE_BESTELLVORGANG && $payment) {
            $checkoutSession = $session->get(SessionHelper::KEY_CHECKOUT_SESSION);
            $paymentMethod = Method::create($payment->cModulId);

            // Not a heidelpay method, abort!
            if (!$paymentMethod instanceof HeidelpayPaymentMethod) {
                return self::STATE_ABORT;
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
        if (Shop::getPageType() === \PAGE_BESTELLSTATUS) {
            $session->clear();
        }

        // Clear Payment Data if the customer wants to change his payment or shipping method
        if (
            Shop::getPageType() === \PAGE_BESTELLVORGANG &&
            (Request::verifyGPCDataInt('editZahlungsart') > 0 || Request::verifyGPCDataInt('editVersandart') > 0)
        ) {
            $session->clearCheckoutSession();
        }

        return self::STATE_ABORT;
    }
}
