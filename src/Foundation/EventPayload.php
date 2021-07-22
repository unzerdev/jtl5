<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Foundation;

use UnzerSDK\Resources\AbstractUnzerResource;

/**
 * Webhook Event Payload
 *
 * @package Plugin\s360_unzer_shop5\src\Foundation
 */
class EventPayload
{
    /**
     * @var string The name of the event
     */
    private $event;

    /**
     * @var string The used public key
     */
    private $publicKey;

    /**
     * @var string The retrieving url
     */
    private $retrieveUrl;

    /**
     * @var string|null Optional payment id for payment related events.
     */
    private $paymentId;

    /**
     * @var AbstractUnzerResource The Webhook / Event Resource
     */
    private $resource;

    /**
     * @param string $event The name of the event
     * @param string $publicKey The used pubic key
     * @param string $retrieveUrl The retrieving url
     * @param string|null $paymentId Optional payment id
     */
    public function __construct(
        string $event,
        string $publicKey,
        string $retrieveUrl,
        ?string $paymentId,
        AbstractUnzerResource $resource
    ) {
        $this->setEvent($event);
        $this->setPublicKey($publicKey);
        $this->setRetrieveUrl($retrieveUrl);
        $this->setPaymentId($paymentId);
        $this->setResource($resource);
    }

    /**
     * Get the name of the event
     *
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * Set the name of the event
     *
     * @param string $event  The name of the event
     * @return self
     */
    public function setEvent(string $event): self
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Get the used public key
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Set the used public key
     *
     * @param string $publicKey  The used public key
     * @return self
     */
    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    /**
     * Get the retrieving url
     *
     * @return string
     */
    public function getRetrieveUrl(): string
    {
        return $this->retrieveUrl;
    }

    /**
     * Set the retrieving url
     *
     * @param string $retrieveUrl  The retrieving url
     * @return self
     */
    public function setRetrieveUrl(string $retrieveUrl): self
    {
        $this->retrieveUrl = $retrieveUrl;
        return $this;
    }

    /**
     * Get optional payment id for payment related events.
     *
     * @return string|null
     */
    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    /**
     * Set optional payment id for payment related events.
     *
     * @param string|null $paymentId  Optional payment id for payment related events.
     * @return self
     */
    public function setPaymentId(?string $paymentId): self
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    /**
     * Get the Webhook / Event Resource
     *
     * @return AbstractUnzerResource
     */
    public function getResource(): AbstractUnzerResource
    {
        return $this->resource;
    }

    /**
     * Set the Webhook / Event Resource
     *
     * @param AbstractUnzerResource $resource  The Webhook / Event Resource
     * @return self
     */
    public function setResource(AbstractUnzerResource $resource): self
    {
        $this->resource = $resource;
        return $this;
    }
}
