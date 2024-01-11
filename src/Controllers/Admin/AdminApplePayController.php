<?php declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Controllers\Admin;

use Exception;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use Plugin\s360_unzer_shop5\src\ApplePay\CertificateException;
use Plugin\s360_unzer_shop5\src\ApplePay\CertificationService;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\JtlLinkHelper;
use RuntimeException;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Admin Settings Controller
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers\Admin
 */
class AdminApplePayController extends AdminController
{
    /**
     * @var CertificationService
     */
    private $certService;

    /**
     * @var JtlLinkHelper
     */
    private $linkHelper;

    /**
     * @required
     * @param CertificationService $certService
     * @return void
     */
    public function setCertService(CertificationService $certService): void
    {
        $this->certService = $certService;
    }

    /**
     * Add some global setting variables to view.
     *
     * @return void
     */
    protected function prepare(): void
    {
        parent::prepare();

        $this->linkHelper = new JtlLinkHelper;
        $this->smarty->assign('hpSettings', [
            'pluginUrl' => $this->linkHelper->getFullAdminUrl(),
            'formAction' => $this->linkHelper->getFullAdminTabUrl(JtlLinkHelper::ADMIN_TAB_APPLE_PAY)
        ]);
    }

    /**
     * Redirect to url
     *
     * @param string $url
     * @return void
     */
    private function redirect(string $url)
    {
        header('Location: ' . $url);
        exit();
    }

     /**
     * Handle Config Action.
     *
     * @return string
     */
    public function handle(): string
    {
        if (!$this->certService) {
            throw new RuntimeException('Did not set dependency CertificationService!');
        }

        // Save Settings
        if (Request::postInt('saveApplePaySettings') === 1 && Form::validateToken()) {
            $paymentCert = null;
            $merchantCert = null;
            $paymentFileKey = 'hpSettings-applepay-payment-upload';
            $merchantFileKey = 'hpSettings-applepay-merchant-upload';

            // Save general settings
            $this->config->set(
                Config::APPLEPAY_MERCHANT_IDENTIFIER,
                Request::postVar('hpSettings-applepay-merchant-id')
            );
            $this->config->set(
                Config::APPLEPAY_MERCHANT_DOMAIN,
                Request::postVar('hpSettings-applepay-merchant-domain')
            );
            $this->config->save();

            // Transform and save the apple pay certificates
            if ($_FILES[$paymentFileKey] && $_FILES[$paymentFileKey]['error'] == 0) {
                $paymentCert = $_FILES[$paymentFileKey]['tmp_name'];
            }

            if ($_FILES[$merchantFileKey] && $_FILES[$merchantFileKey]['error'] == 0) {
                $merchantCert = $_FILES[$merchantFileKey]['tmp_name'];
            }

            try {
                $this->certService->save($paymentCert, $merchantCert);
            } catch(CertificateException $exc) {
                $this->errorLog($exc->getMessage(), CertificationService::class);
                $this->addError(__($exc->getErrorId()));
            } catch(UnzerApiException $exc) {
                $this->errorLog($exc->getMerchantMessage(), CertificationService::class);
                $this->addError($exc->getMerchantMessage());
            } catch (Exception $exc) {
                $this->errorLog($exc->getMessage(), CertificationService::class);
                $this->addError($exc->getMessage());
            }
        }

        // Activate the current certificate in the unzer system
        if (Request::postInt('activateCertificate') === 1 && Form::validateToken()) {
            try {
                $this->certService->activateCertificates();
            } catch(UnzerApiException $exc) {
                $this->errorLog($exc->getMerchantMessage(), CertificationService::class);
                $this->addError($exc->getMerchantMessage());
            } catch (Exception $exc) {
                $this->errorLog($exc->getMessage(), CertificationService::class);
                $this->addError($exc->getMessage());
            }
        }

        // Download CSR files for the Merchant to upload to Apple
        if (Request::getVar('download') && Request::getVar('download') == 'payment_processing_certification') {
            $this->certService->download(Config::APPLEPAY_PAYMENT_CSR);
        }

        if (Request::getVar('download') && Request::getVar('download') == 'merchant_identity_certification') {
            $this->certService->download(Config::APPLEPAY_MERCHANT_CSR);
        }

        // Refresh Payment Processing Certs and Keys
        // we redirect to avoid issue with accidentally resubmitting when reloading the page
        if (Request::postInt('refreshPaymentProcessing') === 1 && Form::validateToken()) {
            $this->certService->refresh(CertificationService::CERT_TYPE_PAYMENT_PROCESSING);
            $this->redirect($this->linkHelper->getFullAdminTabUrl(JtlLinkHelper::ADMIN_TAB_APPLE_PAY));
        }

        // Refresh Merchant Validation Certs and Keys
        // we redirect to avoid issue with accidentally resubmitting when reloading the page
        if (Request::postInt('refreshMerchantValidation') === 1 && Form::validateToken()) {
            $this->certService->refresh(CertificationService::CERT_TYPE_MARCHANT_VALIDATION);
            $this->redirect($this->linkHelper->getFullAdminTabUrl(JtlLinkHelper::ADMIN_TAB_APPLE_PAY));
        }

        // Load Settings
        $settings = $this->smarty->getTemplateVars('hpSettings');
        $settings['merchantId'] = $this->config->get(Config::APPLEPAY_MERCHANT_IDENTIFIER);
        $settings['merchantDomain'] = $this->config->get(Config::APPLEPAY_MERCHANT_DOMAIN);
        $settings['unzerCertificateId'] = $this->config->get(Config::APPLEPAY_UNZER_CERTIFICATE_ID);
        $settings['unzerPrivateKeyId'] = $this->config->get(Config::APPLEPAY_UNZER_PRIVATE_KEY_ID);
        $settings['certs'] = $this->certService->all();

        return $this->view('template/applepay', ['hpSettings' => $settings]);
    }
}
