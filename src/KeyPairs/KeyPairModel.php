<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\KeyPairs;

use JTL\DB\ReturnType;
use Plugin\s360_unzer_shop5\src\Foundation\Entity;
use Plugin\s360_unzer_shop5\src\Foundation\Model;
use stdClass;

/**
 * Key Pair Model
 *
 * @method KeyPairEntity find(int $id) Find a mapped order by its id.
 * @package Plugin\s360_unzer_shop5\src\KeyPairs
 */
class KeyPairModel extends Model
{
    public const TABLE = 'xplugin_s360_unzer_shop5_keypairs';

    public function getTable(): string
    {
        return self::TABLE;
    }

    /**
     * @return KeyPairEntity
     */
    public function createEntity(stdClass $data): Entity
    {
        return KeyPairEntity::create($data);
    }

    /**
     * @return KeyPairEntity[]
     */
    public function all(): array
    {
        $result = $this->database->selectAll($this->getTable(), [], []);

        if (empty($result)) {
            return [];
        }

        $data = [];
        foreach ($result as $row) {
            $data[] = $this->createEntity($row);
        }

        return $data;
    }

    public function findKeyPair(bool $isB2B, int $currency, int $paymentMethod): ?KeyPairEntity
    {
        foreach ($this->all() as $keypair) {
            if ($isB2B === $keypair->isB2B() && $currency === $keypair->getCurrencyId() && in_array($paymentMethod, $keypair->getPaymentMethodIds())) {
                return $keypair;
            }
        }

        return null;
    }

    public function findByPublic(string $publicKey): ?KeyPairEntity
    {
        $result = $this->database->selectSingleRow($this->getTable(), 'public_key', $publicKey);

        if ($result === null) {
            return null;
        }

        return $this->createEntity($result);
    }


    public function findByPrivate(string $privateKey): ?KeyPairEntity
    {
        $result = $this->database->selectSingleRow($this->getTable(), 'private_key', $privateKey);

        if ($result === null) {
            return null;
        }

        return $this->createEntity($result);
    }

    public function getCurrencies(): array
    {
        $result = $this->database->query(
            'SELECT kWaehrung as id, cISO as name FROM twaehrung',
            ReturnType::ARRAY_OF_ASSOC_ARRAYS
        );

        return array_column($result, null, 'id');
    }
}
