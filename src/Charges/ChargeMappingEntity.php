<?php

declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Charges;

use JTL\Checkout\Bestellung;
use Plugin\s360_unzer_shop5\src\Foundation\Entity;
use stdClass;

/**
 * Charge Mapping Entity
 * @package Plugin\s360_unzer_shop5\src\Charges
 */
class ChargeMappingEntity extends Entity
{
    /**
     * @var string Heidelpay payment id.
     */
    private $paymentId;

    /**
     * @var string Heidelpay charge id.
     */
    private $chargeId;

    /**
     * @var int JTL Order id (kBestellung).
     */
    private $orderId;

    /**
     * @var int|null JTL Delivery Id (kLieferschein)
     */
    private $deliveryId = null;

    /**
     * @var Bestellung|null
     */
    private $order;

    /**
     * @inheritDoc
     */
    public function toObject(): stdClass
    {
        $data = new stdClass;
        $data->id = $this->getId();
        $data->order_id = $this->getOrderId();
        $data->payment_id = $this->getPaymentId();
        $data->charge_id = $this->getChargeId();
        $data->delivery_id = $this->getDeliveryId();

        return $data;
    }

    /**
     * @inheritDoc
     * @return Entity|self
     */
    public static function create(stdClass $data): Entity
    {
        $entity = new self;
        $entity->setId((int) $data->id);
        $entity->setOrderId((int) $data->order_id);
        $entity->setPaymentId($data->payment_id);
        $entity->setChargeId($data->charge_id);
        $entity->setDeliveryId($data->delivery_id ? (int) $data->delivery_id : null);
        return $entity;
    }

    /**
     * Get JTL Order Id (kBestellung).
     *
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * Set jTL Order Id (kBestellung).
     *
     * @param int $orderId  JTL Order Id (kBestellung).
     * @return self
     */
    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * Get heidelpay payment id.
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * Set heidelpay payment id.
     *
     * @param string $paymentId  Heidelpay payment id.
     * @return self
     */
    public function setPaymentId(string $paymentId): self
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    /**
     * Get heidelpay charge id.
     *
     * @return string
     */
    public function getChargeId(): string
    {
        return $this->chargeId;
    }

    /**
     * Set heidelpay charge id.
     *
     * @param string $chargeId  Heidelpay charge id.
     * @return self
     */
    public function setChargeId(string $chargeId): self
    {
        $this->chargeId = $chargeId;
        return $this;
    }

    /**
     * Get the value of order.
     *
     * @return Bestellung
     */
    public function getOrder(): Bestellung
    {
        if (is_null($this->order)) {
            $this->setOrder(new Bestellung($this->getOrderId(), true));
        }

        return $this->order;
    }

    /**
     * Set the value of order.
     *
     * @param Bestellung|null $order
     * @return self
     */
    public function setOrder(?Bestellung $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Get jTL Delivery Id (kLieferschein)
     *
     * @return int|null
     */
    public function getDeliveryId(): ?int
    {
        return $this->deliveryId;
    }

    /**
     * Set jTL Delivery Id (kLieferschein)
     *
     * @param int|null $deliveryId  JTL Delivery Id (kLieferschein)
     * @return self
     */
    public function setDeliveryId(?int $deliveryId): self
    {
        $this->deliveryId = $deliveryId;
        return $this;
    }
}
