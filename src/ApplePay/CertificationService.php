<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\ApplePay;

use JTL\Helpers\Request;
use JTL\Services\JTL\CryptoServiceInterface;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\RSA;
use Plugin\s360_unzer_shop5\src\ApplePay\CertificatesResource;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use RuntimeException;
use SplFileInfo;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Handle Apple Pay Certificates and keys
 *
 * @package Plugin\s360_unzer_shop5\src\ApplePay
 */
class CertificationService
{
    public const CERT_TYPE_PAYMENT_PROCESSING = 'PAYMENT_PROCESSING';
    public const CERT_TYPE_MARCHANT_VALIDATION = 'MARCHANT_VALIDATION';
    public const CERT_TYPE_ALL = 'ALL';

    public const CERTS = [
        Config::APPLEPAY_PAYMENT_ECC_KEY,
        Config::APPLEPAY_PAYMENT_CSR,
        Config::APPLEPAY_PAYMENT_SIGNED_PEM,
        Config::APPLEPAY_MERCHANT_PRIVATE_KEY,
        Config::APPLEPAY_MERCHANT_CSR,
        Config::APPLEPAY_MERCHANT_SIGNED_PEM,
    ];

    private CryptoServiceInterface $cryptoService;
    private Config $config;
    private HeidelpayApiAdapter $apiAdapter;

    /**
     * @param CryptoServiceInterface $cryptoService
     * @param Config $config
     * @param HeidelpayApiAdapter $apiAdapter
     */
    public function __construct(CryptoServiceInterface $cryptoService, Config $config, HeidelpayApiAdapter $apiAdapter)
    {
        $this->cryptoService = $cryptoService;
        $this->config = $config;
        $this->apiAdapter = $apiAdapter;
    }

    /**
     * Get all certs and keys
     *
     * @return array
     */
    public function all(): array
    {
        $data = [];

        foreach (self::CERTS as $cert) {
            $data[$cert] = $this->config->get($cert);

            if ($data[$cert]) {
                $data[$cert] = $this->cryptoService->decryptXTEA($data[$cert]);
            }
        }

        return $data;
    }

    /**
     * Get a specific decrypted CERT
     *
     * @param string $type
     * @return ?string
     */
    public function get(string $type): ?string
    {
        $cert = $this->config->get($type);

        if ($cert) {
            return trim($this->cryptoService->decryptXTEA($cert));
        }

        return $cert;
    }

    /**
     * Set a specific CERT and encrypt it
     *
     * @param string $type
     * @return self
     */
    public function set(string $type, string $cert): self
    {
        $cert = trim($cert);
        $this->config->set($type, !empty($cert) ? $this->cryptoService->encryptXTEA($cert) : '');
        return $this;
    }

    /**
     * Refresh certy
     *
     * @param string $type Either PAYMENT_PROCESSING, or MARCHANT_VALIDATION or ALL
     * @return void
     */
    public function refresh(string $type = self::CERT_TYPE_ALL): void
    {
        /**
         * Generate Payment Processing Certificate
         * @see https://docs.unzer.com/payment-methods/applepay/applepay-prerequisites/
         */
        if ($type === self::CERT_TYPE_PAYMENT_PROCESSING || $type == self::CERT_TYPE_ALL) {
            $paymentPrivate = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'prime256v1'
            ]);
            $paymentCsr = openssl_csr_new([], $paymentPrivate, ['digest_alg' => 'sha256']);

            openssl_pkey_export($paymentPrivate, $privateKeyText);
            openssl_csr_export($paymentCsr, $paymentCsrText);

            $this->set(Config::APPLEPAY_PAYMENT_ECC_KEY, $privateKeyText);
            $this->set(Config::APPLEPAY_PAYMENT_CSR, $paymentCsrText);
            $this->set(Config::APPLEPAY_PAYMENT_SIGNED_PEM, '');
            $this->config->set(Config::APPLEPAY_UNZER_CERTIFICATE_ID, '');
            $this->config->set(Config::APPLEPAY_UNZER_PRIVATE_KEY_ID, '');
        }

        /**
         * Generate Generate a Merchant Identity Certificate
         * @see https://docs.unzer.com/payment-methods/applepay/applepay-prerequisites/
         */
        if ($type === self::CERT_TYPE_MARCHANT_VALIDATION || $type == self::CERT_TYPE_ALL) {
            $merchantRsa = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
                'encrypt_key' => true
            ]);
            $merchantCsr = openssl_csr_new([], $merchantRsa);

            openssl_pkey_export($merchantRsa, $rsaText, BLOWFISH_KEY);
            openssl_csr_export($merchantCsr, $csrText);

            $this->set(Config::APPLEPAY_MERCHANT_PRIVATE_KEY, $rsaText);
            $this->set(Config::APPLEPAY_MERCHANT_CSR, $csrText);
            $this->set(Config::APPLEPAY_MERCHANT_SIGNED_PEM, '');
            $this->set(Config::APPLEPAY_MERCHANT_NON_ENCRYPTED_PRIVATE_KEY, '');
        }

        $this->config->save();
    }

    /**
     * Upload/Save the signed apple pay certs
     *
     * @throws UnzerApiException
     * @throws RuntimeException
     * @param string|null $paymentCert
     * @param string|null $merchantCert
     * @return void
     */
    public function save(?string $paymentCert, ?string $merchantCert): void
    {
        if ($paymentCert && file_exists($paymentCert)) {
            // convert cert to text: openssl x509 -inform der -in apple_pay.cer -out apple_pay.pem
            $paymentPem = $this->transformCertToText(file_get_contents($paymentCert));

            // Only update cert if it changed
            if ($paymentPem !== $this->get(Config::APPLEPAY_PAYMENT_SIGNED_PEM)) {
                // Convert your ECC private key to a non-encrypted PKCS #8 private key:
                // openssl pkcs8 -topk8 -nocrypt -in ecckey.key -out privatekey.key
                $signedPrivateKey = EC::loadPrivateKey($this->get(Config::APPLEPAY_PAYMENT_ECC_KEY))->toString('PKCS8');
                $this->set(Config::APPLEPAY_PAYMENT_SIGNED_PEM, $paymentPem);
                $this->set(Config::APPLEPAY_PAYMENT_PRIVATE_KEY, $signedPrivateKey);

                // Activate the certificate (just to be sure)
                $this->activateCertificates();
            }
        }

        if ($merchantCert && file_exists($merchantCert)) {
            // convert cert to text: openssl x509 -inform der -in merchant_id.cer -out merchant_id.pem
            $nonEncryptedKey = '';
            $merchantPem = $this->transformCertToText(file_get_contents($merchantCert));
            openssl_pkey_export(
                $this->get(Config::APPLEPAY_MERCHANT_PRIVATE_KEY),
                $nonEncryptedKey,
                BLOWFISH_KEY,
                [
                    'private_key_type' => OPENSSL_KEYTYPE_RSA,
                    'private_key_bits' => 2048,
                    'encrypt_key' => false
                ]
            );

            $this->set(Config::APPLEPAY_MERCHANT_SIGNED_PEM, $merchantPem);
            $this->set(
                Config::APPLEPAY_MERCHANT_NON_ENCRYPTED_PRIVATE_KEY,
                RSA::loadPrivateKey($nonEncryptedKey)->toString('PKCS1')
            );
        }

        $this->config->save();
    }

    /**
     * Upload and Activate the current certificates in the unzer system
     *
     * @return bool
     */
    public function activateCertificates(): bool
    {
        // Upload signedPrivateKey to unzer
        $httpService = $this->apiAdapter->getDefaultConnection()->getHttpService();
        $keypair = new PrivateKeysResource();
        $keypair->setParentResource($this->apiAdapter->getDefaultConnection());
        $keypair->setCertificate($this->get(Config::APPLEPAY_PAYMENT_PRIVATE_KEY));

        $response = json_decode(
            $httpService->send($keypair->getUri(), $keypair, HttpAdapterInterface::REQUEST_POST),
            true
        );
        $this->config->set(Config::APPLEPAY_UNZER_PRIVATE_KEY_ID, $response['id']);

        // Upload signedCert to unzer
        $cert = new CertificatesResource();
        $cert->setParentResource($this->apiAdapter->getDefaultConnection());
        $cert->setPrivateKey($response['id']);
        $cert->setCertificate($this->get(Config::APPLEPAY_PAYMENT_SIGNED_PEM));

        $response = json_decode(
            $httpService->send($cert->getUri(), $cert, HttpAdapterInterface::REQUEST_POST),
            true
        );

        $this->config->set(Config::APPLEPAY_UNZER_CERTIFICATE_ID, $response['id']);

        // Activate Certificate
        $httpService = $this->apiAdapter->getDefaultConnection()->getHttpService();
        $cert = new ActivateCertificateResource();
        $cert->setParentResource($this->apiAdapter->getDefaultConnection());
        $cert->setCertificate($this->config->get(Config::APPLEPAY_UNZER_CERTIFICATE_ID));

        $response = json_decode(
            $httpService->send($cert->getUri(), $cert, HttpAdapterInterface::REQUEST_POST),
            true
        );

        if ($response && $response['active']) {
            return true;
        }

        return false;
    }

    /**
     * Download either PAYMENT_CSR or MERCHANT_CSR.
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @param string $type
     * @return never
     */
    public function download(string $type)
    {
        switch ($type) {
            case Config::APPLEPAY_PAYMENT_CSR:
                $name = 'ecc.certSigningRequest';
                break;

            case Config::APPLEPAY_MERCHANT_CSR:
                $name = 'merchant_id.certSigningRequest';
                break;

            default:
                header(Request::makeHTTPHeader(404));
                exit;
        }

        $file = trim($this->get($type));

        header('Content-Description: File Transfer');
        header('Content-Type: text/csr');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . mb_strlen($file, '8bit'));
        echo $file;
        exit;
    }

    /**
     * Export Certificate to temporary file (to avoid leaking any infos to attackers)
     *
     * @throws RuntimeException if tmp file is not writeable
     * @param string $key
     * @return SplFileInfo
     */
    public function exportToFile(string $key): SplFileInfo
    {
        $fileInfo = new SplFileInfo(tempnam('/tmp', 'applepay-'));

        if (!$fileInfo->isWritable()) {
            throw new RuntimeException('Could not export certificate ' . $key . ' because tmp file is not writable!');
        }

        $fileObj = $fileInfo->openFile('w+');
        $fileObj->fwrite($this->get($key));

        return $fileInfo;
    }

    /**
     * Transform a certificate to text content
     *
     * `openssl x509 -inform der -in apple_pay.cer -out apple_pay.pem`
     *
     * @see https://gist.github.com/ajzele/4585931
     * @param string $certificate
     * @return string
     */
    public function transformCertToText(string $certificate): string
    {
        return
            '-----BEGIN CERTIFICATE-----' . PHP_EOL
            . chunk_split(base64_encode($certificate), 64, PHP_EOL)
            . '-----END CERTIFICATE-----' . PHP_EOL;
    }
}
