<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Controllers\Admin;

use Exception;
use UnzerSDK\Validators\PrivateKeyValidator;
use UnzerSDK\Validators\PublicKeyValidator;
use JTL\Helpers\Request;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairModel;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\JtlLinkHelper;
use Plugin\s360_unzer_shop5\src\Webhooks\PaymentEventSubscriber;

/**
 * Admin Settings Controller
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers\Admin
 */
class AdminSettingsController extends AdminController
{
    /**
     * Add some global setting variables to view.
     *
     * @return void
     */
    protected function prepare(): void
    {
        parent::prepare();

        $linkHelper = new JtlLinkHelper();

        $this->smarty->assign('hpSettings', [
            'formAction' => $linkHelper->getFullAdminTabUrl(JtlLinkHelper::ADMIN_TAB_SETTINGS)
        ]);
    }

    /**
     * Handle Config Action.
     *
     * @return string
     */
    public function handle(): string
    {
        // Handle Save Request
        if (Request::hasGPCData('saveSettings') && Request::postInt('saveSettings')) {
            $this->handleSaveRequest();
        }

        // Delete registered webhooks and register them again (useful when domain changed)!
        if (Request::hasGPCData('registerWebhooks') && Request::postInt('registerWebhooks')) {
            /** @var HeidelpayApiAdapter $adapter */
            $adapter = Shop::Container()->get(HeidelpayApiAdapter::class);
            $this->registerWebhooks($adapter);
        }

        $settings = $this->smarty->getTemplateVars('hpSettings');
        $settings['config'] = $this->config->all();

        try {
            /** @var HeidelpayApiAdapter $adapter */
            $adapter = Shop::Container()->get(HeidelpayApiAdapter::class);
            $webhooks = $adapter->getCurrentConnection()->fetchAllWebhooks();
            $settings['webhooks'] = \count($webhooks);
        } catch (\Exception $exc) {
            $settings['webhooks'] = false;
        }

        return $this->view('template/settings', ['hpSettings' => $settings]);
    }

    /**
     * Handle Save Request, ie validate and save input.
     *
     * @return void
     */
    protected function handleSaveRequest(): void
    {
        // If private key is hashed -> no new user input -> use existing one
        $privateKey = Request::postVar('privateKey');
        $info = password_get_info($privateKey);
        if (!empty($info) && $info['algo'] > 0) {
            $privateKey = $this->config->get(Config::PRIVATE_KEY);
        }

        // Set Config
        $this->config->set(Config::PRIVATE_KEY, $privateKey);
        $this->config->set(Config::PUBLIC_KEY, Request::postVar('publicKey'));
        $this->config->set(Config::MERCHANT_ID, Request::postVar('merchantId'));
        $this->config->set(Config::FONT_SIZE, Request::postVar('fontSize'));
        $this->config->set(Config::FONT_COLOR, Request::postVar('fontColor'));
        $this->config->set(Config::FONT_FAMILY, Request::postVar('fontFamily'));
        $this->config->set(Config::SELECTOR_SUBMIT_BTN, Request::postVar('selectorSubmitButton'));
        $this->config->set(
            Config::PQ_SELECTOR_CHANGE_PAYMENT_METHOD,
            Request::postVar('pqSelectorChangePaymentMethod')
        );
        $this->config->set(
            Config::PQ_METHOD_CHANGE_PAYMENT_METHOD,
            Request::postVar('pqMethodChangePaymentMethod')
        );
        $this->config->set(
            Config::PQ_SELECTOR_PAYMENT_INFORMATION,
            Request::postVar('pqSelectorPaymentInformation')
        );
        $this->config->set(
            Config::PQ_METHOD_PAYMENT_INFORMATION,
            Request::postVar('pqMethodPaymentInformation')
        );
        $this->config->set(
            Config::PQ_SELECTOR_INSTALMENT_INFO,
            Request::postVar('pqSelectorInstalmentInfo')
        );
        $this->config->set(
            Config::PQ_METHOD_INSTALMENT_INFO,
            Request::postVar('pqMethodInstalmentInfo')
        );
        $this->config->set(Config::PQ_SELECTOR_ERRORS, Request::postVar('pqSelectorErrors'));
        $this->config->set(Config::PQ_METHOD_ERRORS, Request::postVar('pqMethodErrors'));
        $this->config->set(Config::PQ_SELECTOR_REVIEW_STEP, Request::postVar('pqSelectorReviewStep'));
        $this->config->set(Config::PQ_METHOD_REVIEW_STEP, Request::postVar('pqMethodReviewStep'));
        $this->config->set(Config::ADD_INCOMING_PAYMENTS, Request::postVar('addIncomingPayments', false));

        // Validate
        $valid = true;

        if (empty($privateKey) || !PrivateKeyValidator::validate($privateKey)) {
            $this->addError(__('Ungültiger Private Key.'));
            $valid = false;
        }

        if (empty(Request::postVar('publicKey')) || !PublicKeyValidator::validate(Request::postVar('publicKey'))) {
            $this->addError(
                __('Ungültiger Public Key. Bitte stellen Sie sicher, dass sie hier Ihren Public Key und nicht Ihren Private Key angeben!')
            );
            $valid = false;
        }

        // If config is valid, save it in DB
        if ($valid) {
            $this->config->save();
            $this->addSuccess(__('Die Einstellungen wurden erfolgreich gespeichert.'));

            // Register Webhooks Event Handlers if needed
            /** @var HeidelpayApiAdapter $adapter */
            $adapter = Shop::Container()->get(HeidelpayApiAdapter::class);
            $this->registerWebhooks($adapter);
        }
    }

    /**
     * Register new webhooks.
     *
     * @param HeidelpayApiAdapter $adapter
     * @return void
     */
    protected function registerWebhooks(HeidelpayApiAdapter $adapter): void
    {
        $newEvents = array_keys(PaymentEventSubscriber::getSubscribedEvents());

        if (empty($newEvents)) {
            return;
        }

        // Register for all keyparis + default
        try {
            $linkHelper = new JtlLinkHelper();
            $model = new KeyPairModel(Shop::Container()->getDB());
            $keypairs = $model->all();

            foreach ($keypairs as $keypair) {
                $adapter->setCurrentConnection(
                    $keypair->isB2B(),
                    $keypair->getCurrencyId(),
                    $keypair->getPaymentMethodId()
                )->registerMultipleWebhooks(
                    $linkHelper->getFullFrontendFileUrl(JtlLinkHelper::FRONTEND_FILE_WEBHOOKS),
                    $newEvents
                );
            }

            $adapter->getDefaultConnection()->registerMultipleWebhooks(
                $linkHelper->getFullFrontendFileUrl(JtlLinkHelper::FRONTEND_FILE_WEBHOOKS),
                $newEvents
            );
        } catch (Exception $exc) {
            $this->errorLog('Could not register webhook: ' . $exc->getMessage(), static::class);
        }

        $this->debugLog('Registered the following new webhooks: ' . implode(', ', $newEvents), static::class);
        $this->addSuccess(__('Die Webhooks wurden erfolgreich gespeichert.'));
    }
}
