<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\ApplePay;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Apple Pay Private Keys Resource
 *
 * @see https://docs.unzer.com/payment-methods/applepay/applepay-prerequisites/#step-6-upload-your-pkcs-8-private-key-and-your-payment-processing-certificate-to-the-unzer-system
 */
class PrivateKeysResource extends AbstractUnzerResource
{
    /**
     * @var string The file type extension.
     */
    protected string $format = 'PEM';

    /**
     * @var string The type of the key.
     */
    protected string $type = 'private-key';

    /**
     * @var string The non-encrypted PKCS #8 private key.
     */
    protected string $certificate = '';

    /**
     * @inheritDoc
     */
    public function getUri($appendId = true, $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return '/keypair/applepay/privatekeys';
    }

    /**
     * Get the file type extension.
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Set the file type extension.
     *
     * @param string $format  The file type extension.
     * @return self
     */
    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get the type of the key.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the type of the key.
     *
     * @param string $type  The type of the key.
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the non-encrypted PKCS #8 private key.
     *
     * @return string
     */
    public function getCertificate(): string
    {
        return $this->certificate;
    }

    /**
     * Set the non-encrypted PKCS #8 private key.
     *
     * @param string $certificate  The non-encrypted PKCS #8 private key.
     * @return self
     */
    public function setCertificate(string $certificate): self
    {
        $this->certificate = $certificate;

        return $this;
    }
}
