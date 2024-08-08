<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\ApplePay;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Apple Pay Certificates Resource
 *
 * @see https://docs.unzer.com/payment-methods/applepay/applepay-prerequisites/#upload-the-apple-signed-payment-processing-certificate-to-the-unzer-system
 */
class ActivateCertificateResource extends AbstractUnzerResource
{
    /**
     * @var string the unzer certificate id
     */
    private string $certificate = '';

    /**
     * @inheritDoc
     */
    public function getUri($appendId = true, $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return '/keypair/applepay/certificates/' . $this->getCertificate() . '/activate';
    }

    /**
     * Get the unzer certificate id
     *
     * @return string
     */
    public function getCertificate(): string
    {
        return $this->certificate;
    }

    /**
     * Set the unzer certificate id
     *
     * @param string $certificate  the unzer certificate id
     * @return self
     */
    public function setCertificate(string $certificate): self
    {
        $this->certificate = $certificate;
        return $this;
    }
}
