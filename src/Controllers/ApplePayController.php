<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Controllers;

use Exception;
use JTL\IO\IO;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\ApplePay\CertificationService;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use Plugin\s360_unzer_shop5\src\Utils\TranslatorTrait;
use RuntimeException;
use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Exceptions\ApplepayMerchantValidationException;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;

/**
 * Controller for Apple Pay
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers
 */
class ApplePayController extends Controller
{
    use TranslatorTrait;

    public const IO_FUNCTION_MERCHANT_VALIDATION = 'apple_pay_merchantvalidation';
    public const IO_FUNCTION_PAYMENT_AUTHORIZED = 'apple_pay_payment_authorized';

    /**
     * @var IO
     */
    private $io;

    /**
     * @var CertificationService
     */
    private $certificationService;

    /**
     * @var SessionHelper
     */
    private $sessionHelper;

    /**
     * @param PluginInterface $plugin
     * @param CertificationService $certificationService
     * @param IO $io
     */
    public function __construct(PluginInterface $plugin, CertificationService $certificationService, IO $io)
    {
        parent::__construct($plugin);

        $this->sessionHelper = Shop::Container()->get(SessionHelper::class);
        $this->certificationService = $certificationService;
        $this->io = $io;
    }

    /**
     * Register io functions.
     *
     * @return string
     */
    public function handle(): string
    {
        $this->io->register(self::IO_FUNCTION_MERCHANT_VALIDATION, function ($merchantValidationUrl) {
            return $this->validateMerchant($merchantValidationUrl);
        });

        $this->io->register(self::IO_FUNCTION_PAYMENT_AUTHORIZED, function (string $typeId) {
            return $this->paymentAuthorized($typeId);
        });

        return '';
    }

    /**
     * TODO: Testing
     * Authorize payment callback
     *
     * SIMILAR/SAME as what would happen in the validateAdditional method of the payment method, but
     * we need it before leaving the page to complete or abort the apple payment authorization
     *
     * @param string $typeId
     * @return void
     */
    private function paymentAuthorized(string $typeId)
    {
        $response = ['transactionStatus' => 'error'];

        try {
            $this->sessionHelper->setCheckoutSession($typeId);
            $response['transactionStatus'] = 'pending'; // Pending, as we did not charge the user yet
        } catch (\Exception $exc) {
            $this->errorLog($exc->getMessage(), 'APPLE PAY authorization endpoint: ');
        }

        $this->debugLog(
            'Authorize Payment Callback' . print_r(['typeId' => $typeId, 'response' => $response], true),
            self::class
        );

        return $response;
    }

    /**
     * Do the merchant validation request and return the result to the frontend.
     *
     * @param string $merchantValidationUrl
     * @return string|null
     */
    private function validateMerchant(string $merchantValidationUrl)
    {
        try {
            // Init Apple Pay Session for with merchant identifier and domain
            $merchantId = $this->config->get(Config::APPLEPAY_MERCHANT_IDENTIFIER);
            $merchantDomain = $this->config->get(Config::APPLEPAY_MERCHANT_DOMAIN);

            $this->debugLog(
                'Validating merchant: ' . print_r(
                    [
                        'url' => $merchantValidationUrl,
                        'merchantId' => $merchantId,
                        'merchantDomain' => $merchantDomain
                    ],
                    true
                ),
                static::class
            );

            $appleAdapter = new ApplepayAdapter();
            $applepaySession = new ApplepaySession(
                $this->config->get(Config::APPLEPAY_MERCHANT_IDENTIFIER),
                Shop::getSettingValue(\CONF_GLOBAL, 'global_shopname'),
                $this->config->get(Config::APPLEPAY_MERCHANT_DOMAIN)
            );

            // Export the Merchant Certificate and its key to tmp files and init the apple pay adapter
            $certPem = $this->certificationService->exportToFile(Config::APPLEPAY_MERCHANT_SIGNED_PEM);
            $privKey = $this->certificationService->exportToFile(Config::APPLEPAY_MERCHANT_NON_ENCRYPTED_PRIVATE_KEY);

            $appleAdapter->init($certPem->getRealPath(), $privKey->getRealPath());

            // Send the applepay validation request and delete tmp files (just to be sure)
            $result = $appleAdapter->validateApplePayMerchant($merchantValidationUrl, $applepaySession);
            unlink($certPem->getRealPath());
            unlink($privKey->getRealPath());

            $this->debugLog('Merchant Validation Result: ' . print_r(json_decode($result), true), static::class);

            return json_decode($result);
        } catch (RuntimeException | ApplepayMerchantValidationException $exc) {
            // Dont give internal error directly to the frontend.
            $this->errorLog($exc->getMessage(), 'APPLE PAY Merchant Validation: ');
            throw new Exception($this->trans(Config::LANG_ERROR_VALIDATING_MERCHANT));
        }
    }
}
