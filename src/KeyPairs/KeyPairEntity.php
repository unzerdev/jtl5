<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\KeyPairs;

use Plugin\s360_unzer_shop5\src\Foundation\Entity;
use stdClass;

class KeyPairEntity extends Entity
{
    protected string $privateKey;
    protected string $publicKey;
    protected bool $isB2B;
    protected int $currencyId;
    protected int $paymentMethodId;

    public function __construct(
        string $privateKey,
        string $publicKey,
        bool $isB2B,
        int $currencyId,
        int $paymentMethodId
    ) {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        $this->isB2B = $isB2B;
        $this->currencyId = $currencyId;
        $this->paymentMethodId = $paymentMethodId;
    }

    public function toObject(): stdClass
    {
        $data = new stdClass();

        $data->id = $this->getId();
        $data->private_key = $this->getPrivateKey();
        $data->public_key = $this->getPublicKey();
        $data->is_b2b = $this->isB2B();
        $data->currency_id = $this->getCurrencyId();
        $data->payment_method_id = $this->getPaymentMethodId();

        return $data;
    }

    public static function create(stdClass $data): Entity
    {
        $entity = new self(
            $data->private_key,
            $data->public_key,
            (bool) $data->is_b2b,
            (int) $data->currency_id,
            (int) $data->payment_method_id
        );

        if (!empty($data->id)) {
            $entity->setId((int) $data->id);
        }

        return $entity;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(string $privateKey): self
    {
        $this->privateKey = $privateKey;
        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    public function isB2B(): bool
    {
        return $this->isB2B;
    }

    public function setIsB2B(bool $isB2B): self
    {
        $this->isB2B = $isB2B;
        return $this;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function setCurrencyId(int $currencyId): self
    {
        $this->currencyId = $currencyId;
        return $this;
    }

    public function getPaymentMethodId(): int
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(int $paymentMethodId): self
    {
        $this->paymentMethodId = $paymentMethodId;
        return $this;
    }
}
