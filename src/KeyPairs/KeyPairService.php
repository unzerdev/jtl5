<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\KeyPairs;

use Plugin\s360_unzer_shop5\src\Utils\Config;

final class KeyPairService
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
        return $this->model->findByPublic($public)?->getPrivateKey();
    }


    public function getPublicFromPrivate(string $private): ?string
    {
        return $this->model->findByPrivate($private)?->getPublicKey();
    }

    public function getPrivateKey(bool $isB2B, int $currency, int $paymentMethod): ?string
    {
        return $this->model->findKeyPair($isB2B, $currency, $paymentMethod)?->getPrivateKey();
    }

    public function getPublicKey(bool $isB2B, int $currency, int $paymentMethod): ?string
    {
        return $this->model->findKeyPair($isB2B, $currency, $paymentMethod)?->getPublicKey();
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
