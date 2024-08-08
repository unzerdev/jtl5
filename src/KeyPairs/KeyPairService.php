<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\KeyPairs;

use Plugin\s360_unzer_shop5\src\Utils\Config;

class KeyPairService
{
    private KeyPairModel $model;
    private Config $config;

    public function __construct(KeyPairModel $model, Config $config)
    {
        $this->model = $model;
        $this->config = $config;
    }

    public function getPrivateFromPublic(string $public): ?string
    {
        $keypair = $this->model->findByPublic($public);

        if (empty($keypair)) {
            return null;
        }

        return $keypair->getPrivateKey();
    }

    public function getPrivateKey(bool $isB2B, int $currency, int $paymentMethod): ?string
    {
        $keypair = $this->model->findKeyPair($isB2B, $currency, $paymentMethod);

        if (empty($keypair)) {
            return null;
        }

        return $keypair->getPrivateKey();
    }

    public function getPublicKey(bool $isB2B, int $currency, int $paymentMethod): ?string
    {
        $keypair = $this->model->findKeyPair($isB2B, $currency, $paymentMethod);

        if (empty($keypair)) {
            return null;
        }

        return $keypair->getPublicKey();
    }

    public function getDefaultPrivateKey(): ?string
    {
        return $this->config->get(Config::PRIVATE_KEY);
    }

    public function getDefaultPublicKey(): ?string
    {
        return $this->config->get(Config::PUBLIC_KEY);
    }
}
