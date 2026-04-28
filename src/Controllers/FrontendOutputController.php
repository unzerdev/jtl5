<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Controllers;

use JTL\Checkout\Bestellung;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Services\SavedPaymentDataService;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Frontend Output Controller
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers
 */
class FrontendOutputController extends Controller
{
    private const TEMPLATE_ID_CHANGE_PAYMENT_METHOD = 'template/partials/change_payment';
    private const TEMPLATE_ID_PAYMENT_INFO = 'template/partials/payment_info';
    private const TEMPLATE_ID_SAVED_PAYMENT_DATA = 'template/partials/saved_payment_methods';

    /**
     * @inheritDoc
     */
    public function handle(): string
    {
        // Show Saved payment data
        if (Shop::getPageType() == \PAGE_MEINKONTO) {
            $pqMethod = $this->config->get(Config::PQ_METHOD_SAVED_PAYMENT_DATA, 'after');
            $pqSelector = $this->config->get(Config::PQ_SELECTOR_SAVED_PAYMENT_DATA, '.account-data-item-orders');
            $paymentData = (new SavedPaymentDataService())->getUserPaymentData();

            if (!empty($paymentData) && $pqSelector) {
                pq($pqSelector)->{$pqMethod}(
                    $this->view(self::TEMPLATE_ID_SAVED_PAYMENT_DATA, ['s360_unzer' => ['savedPaymentMethods' => $paymentData]])
                );
            }
        }

        // Add "Change Payment Button"/Link
        if (Shop::getPageType() == \PAGE_BESTELLVORGANG) {
            $snippet = $this->view(self::TEMPLATE_ID_CHANGE_PAYMENT_METHOD);
            $pqMethod = $this->config->get(Config::PQ_METHOD_CHANGE_PAYMENT_METHOD, 'append');
            $pqSelector = $this->config->get(Config::PQ_SELECTOR_CHANGE_PAYMENT_METHOD);

            if ($pqSelector) {
                pq($pqSelector)->$pqMethod($snippet);
            }
        }

        // Add Payment Information
        if (Shop::getPageType() == \PAGE_BESTELLABSCHLUSS) {
            /** @var Bestellung $order */
            $order = $this->smarty->getTemplateVars('Bestellung');
            if (
                !empty($order) && (
                strpos($order->Zahlungsart->cModulId, 'unzervorkasse') !== false ||
                strpos($order->Zahlungsart->cModulId, 'unzerrechnung') !== false)
            ) {
                $snippet = $this->view(self::TEMPLATE_ID_PAYMENT_INFO);
                $pqMethod = $this->config->get(Config::PQ_METHOD_PAYMENT_INFORMATION, 'append');

                pq(
                    $this->config->get(
                        Config::PQ_SELECTOR_PAYMENT_INFORMATION,
                        '#order-confirmation .card-body'
                    )
                )->$pqMethod($snippet);
            }
        }

        return '';
    }
}
