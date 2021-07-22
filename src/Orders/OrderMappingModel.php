<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Orders;

use JTL\Checkout\Bestellung;
use Plugin\s360_unzer_shop5\src\Foundation\Entity;
use Plugin\s360_unzer_shop5\src\Foundation\Model;
use stdClass;

/**
 * Order Mapping Model
 *
 * @method OrderMappingEntity find(int $id) Find a mapped order by its id.
 * @package Plugin\s360_unzer_shop5\src\Orders
 */
class OrderMappingModel extends Model
{
    public const ORDER_TABLE = 'xplugin_s360_unzer_shop5_order';

    /**
     * @var string
     */
    protected $primaryKey = 'jtl_order_id';

    /**
     * @inheritDoc
     */
    public function getTable(): string
    {
        return self::ORDER_TABLE;
    }

    /**
     * @inheritDoc
     * @return OrderMappingEntity
     */
    public function createEntity(stdClass $data): Entity
    {
        return OrderMappingEntity::create($data);
    }

    /**
     * Release an order so that it can be collected by the ERP.
     *
     * !IMPORTANT: Only release orders that are in the initial order state
     * (BESTELLUNG_STATUS_OFFEN) of the shop to prevent having the ERP
     * retrieve an order multiple times.
     *
     * @param integer $id
     * @return integer -1 if fails, number of affected rows if successful
     */
    public function releaseOrder(int $id): int
    {
        $orderMapping = $this->find($id);

        if (!empty($orderMapping)) {
            $obj = new stdClass;
            $obj->cAbgeholt = 'N';

            return (int) $this->database->update(
                'tbestellung',
                ['kBestellung', 'cStatus'],
                [$orderMapping->getId(), BESTELLUNG_STATUS_OFFEN],
                $obj
            );
        }

        return -1;
    }

    /**
     * Find an order mapping by its payment id
     *
     * @param string $paymentId
     * @return OrderMappingEntity|Entity|null
     */
    public function findByPayment(string $paymentId): ?OrderMappingEntity
    {
        $result = $this->database->select($this->getTable(), 'payment_id', $paymentId);

        if ($result) {
            $entity = $this->createEntity($result);
            $entity->setOrder(new Bestellung($entity->getId(), true));

            return $entity;
        }

        return null;
    }

    /**
     * Save order attributes
     *
     * @param Bestellung $order
     * @param array $attributes
     * @return void
     */
    public function saveOrderAttributes(Bestellung $order, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $attr = new stdClass;
            $attr->kBestellung = $order->kBestellung;
            $attr->cName = $key;
            $attr->cValue = $value;
            $this->database->insert('tbestellattribut', $attr);
        }
    }

    /**
     * Load orders paginated.
     *
     * @param integer $limit
     * @param integer $offset
     * @return array|OrderMappingEntity[]
     */
    public function loadOrders(int $limit, int $offset, ?string $search = null): array
    {
        // Load Mapped Orders
        $params = ['limit' => $limit, 'offset' => $offset];

        $searchQuery = '';
        if (!empty($search)) {
            $searchQuery = ' WHERE jtl_order_number LIKE :search OR payment_id LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $query =
            'SELECT * FROM ' . self::ORDER_TABLE .
            ' LEFT JOIN tbestellung ON tbestellung.kBestellung = ' . self::ORDER_TABLE . '.jtl_order_id
            ' . $searchQuery . '
             ORDER BY jtl_order_id DESC LIMIT :limit OFFSET :offset';

        $result = $this->database->executeQueryPrepared($query, $params, 2);

        if (empty($result)) {
            return [];
        }

        // Transform To Entities
        $data = [];
        foreach ($result as $row) {
            $order = new Bestellung();
            $attrs = get_object_vars($row);
            foreach ($attrs as $key => $val) {
                if (property_exists($order, $key)) {
                    $order->$key = $val;
                }
            }

            $entity = $this->createEntity($row);
            $entity->setOrder($order);
            $data[] = $entity;
        }

        return $data;
    }
}
