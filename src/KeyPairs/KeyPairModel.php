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
        $result = $this->database->queryPrepared(
            "SELECT * FROM {$this->getTable()}
            WHERE is_b2b = :b2b AND currency_id = :currency AND payment_method_id = :paymentMethod",
            ['b2b' => $isB2B, 'currency' => $currency, 'paymentMethod' => $paymentMethod],
            ReturnType::SINGLE_OBJECT
        );

        if (empty($result)) {
            return null;
        }

        return $this->createEntity($result);
    }

    public function findByPublic(string $publicKey): ?KeyPairEntity
    {
        $result = $this->database->selectSingleRow($this->getTable(), 'public_key', $publicKey);

        if (empty($result)) {
            return null;
        }

        return $this->createEntity($result);
    }

    public function getCurrencies()
    {
        $result = $this->database->query(
            'SELECT kWaehrung as id, cISO as name FROM twaehrung',
            ReturnType::ARRAY_OF_ASSOC_ARRAYS
        );

        return array_column($result, null, 'id');
    }
}
