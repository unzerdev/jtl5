<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Charges;

use Plugin\s360_unzer_shop5\src\Foundation\Entity;
use Plugin\s360_unzer_shop5\src\Foundation\Model;
use stdClass;

/**
 * Charge Mapping Model
 *
 * @method ChargeMappingEntity find(int $id) Find a mapped Charge by its id.
 * @package Plugin\s360_unzer_shop5\src\Charges
 */
class ChargeMappingModel extends Model
{
    public const CHARGE_TABLE = 'xplugin_s360_unzer_shop5_charge';

    /**
     * @inheritDoc
     */
    public function getTable(): string
    {
        return self::CHARGE_TABLE;
    }

    /**
     * @inheritDoc
     * @return ChargeMappingEntity
     */
    public function createEntity(stdClass $data): Entity
    {
        return ChargeMappingEntity::create($data);
    }

    /**
     * Get all charges for an order
     *
     * @param integer $orderId
     * @return ChargeMappingEntity[]|array
     */
    public function getChargesForOrder(int $orderId): array
    {
        $rows = $this->database->selectAll($this->getTable(), 'order_id', $orderId);

        if (empty($rows)) {
            return [];
        }

        $data = [];
        foreach ($rows as $row) {
            $data[] = $this->createEntity($row);
        }

        return $data;
    }

    /**
     * Get a specific charge for an order.
     *
     * @param integer $orderId
     * @param string $chargeId
     * @return ChargeMappingEntity|null
     */
    public function getChargeForOrder(int $orderId, string $chargeId): ?ChargeMappingEntity
    {
        $result = $this->database->select($this->getTable(), 'order_id', $orderId, 'charge_id', $chargeId);

        if ($result) {
            return $this->createEntity($result);
        }

        return null;
    }

     /**
     * Get all charges which are delivered
     *
     * @param integer $orderId
     * @return ChargeMappingEntity[]|array
     */
    public function getProcessedDeliveries(int $orderId): array
    {
        $rows = $this->database->selectAll($this->getTable(), 'order_id', $orderId);

        if (empty($rows)) {
            return [];
        }

        $data = [];
        foreach ($rows as $row) {
            if ($row->delivery_id) {
                $data[] = $this->createEntity($row);
            }
        }

        return $data;
    }
}
