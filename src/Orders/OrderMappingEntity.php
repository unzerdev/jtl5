<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Orders;

use JTL\Checkout\Bestellung;
use Plugin\s360_unzer_shop5\src\Foundation\Entity;
use stdClass;

/**
 * Order Mapping Entity
 * @package Plugin\s360_unzer_shop5\src\Orders
 */
class OrderMappingEntity extends Entity
{
    /**
     * @var string JTL Order Number (cBestellNr).
     */
    protected $jtlOrderNumber;

    /**
     * @var string Heidelpay payment id.
     */
    protected $paymentId;

    /**
     * @var string|null Heidelpay Unique Transaction id.
     */
    protected $transactionUniqueId;

    /**
     * @var string Heidelpay payment state.
     */
    protected $paymentState;

    /**
     * @var string Heidelpay payment type name.
     */
    protected $paymentTypeName;

    /**
     * @var string Heidelpay payment type id.
     */
    protected $paymentTypeId;

    /**
     * @var Bestellung|null
     */
    protected $order;

    /**
     * @var string|null
     */
    protected $invoiceId;

    /**
     * @inheritDoc
     */
    public function toObject(): stdClass
    {
        $data = new stdClass;
        $data->jtl_order_id = $this->getId();
        $data->jtl_order_number = $this->getJtlOrderNumber();
        $data->invoice_id = $this->getInvoiceId();
        $data->payment_id = $this->getPaymentId();
        $data->transaction_unique_id = $this->getTransactionUniqueId();
        $data->payment_state = $this->getPaymentState();
        $data->payment_type_name = $this->getPaymentTypeName();
        $data->payment_type_id = $this->getPaymentTypeId();

        return $data;
    }

    /**
     * @inheritDoc
     * @return Entity|self
     */
    public static function create(stdClass $data): Entity
    {
        $entity = new self;
        $entity->setId((int) $data->jtl_order_id);
        $entity->setJtlOrderNumber($data->jtl_order_number);
        $entity->setInvoiceId($data->invoice_id);
        $entity->setPaymentId($data->payment_id);
        $entity->setTransactionUniqueId($data->transaction_unique_id);
        $entity->setPaymentState($data->payment_state);
        $entity->setPaymentTypeName($data->payment_type_name);
        $entity->setPaymentTypeId($data->payment_type_id);
        return $entity;
    }

    /**
     * Get jTL Order Number (cBestellNr).
     *
     * @return string
     */
    public function getJtlOrderNumber(): string
    {
        return $this->jtlOrderNumber;
    }

    /**
     * Set jTL Order Number (cBestellNr).
     *
     * @param string $jtlOrderNumber  JTL Order Number (cBestellNr).
     * @return self
     */
    public function setJtlOrderNumber(string $jtlOrderNumber): self
    {
        $this->jtlOrderNumber = $jtlOrderNumber;
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
     * Get heidelpay unique id.
     *
     * @return string|null
     */
    public function getTransactionUniqueId(): ?string
    {
        return $this->transactionUniqueId;
    }

    /**
     * Set heidelpay unique id.
     *
     * @param string|null $uniqueId  Heidelpay unique id.
     * @return self
     */
    public function setTransactionUniqueId(?string $uniqueId): self
    {
        $this->transactionUniqueId = $uniqueId;
        return $this;
    }

    /**
     * Get heidelpay payment state.
     *
     * @return string
     */
    public function getPaymentState(): string
    {
        return $this->paymentState;
    }

    /**
     * Set heidelpay payment state.
     *
     * @param string $paymentState  Heidelpay payment state.
     * @return self
     */
    public function setPaymentState(string $paymentState): self
    {
        $this->paymentState = $paymentState;
        return $this;
    }

    /**
     * Get heidelpay payment type name.
     *
     * @return string
     */
    public function getPaymentTypeName(): string
    {
        return $this->paymentTypeName;
    }

    /**
     * Set heidelpay payment type name.
     *
     * @param string $paymentTypeName  Heidelpay payment type name.
     * @return self
     */
    public function setPaymentTypeName(string $paymentTypeName): self
    {
        $this->paymentTypeName = $paymentTypeName;
        return $this;
    }

    /**
     * Get heidelpay payment type id.
     *
     * @return string
     */
    public function getPaymentTypeId(): string
    {
        return $this->paymentTypeId;
    }

    /**
     * Set heidelpay payment type id.
     *
     * @param string $paymentTypeId  Heidelpay payment type id.
     * @return self
     */
    public function setPaymentTypeId(string $paymentTypeId): self
    {
        $this->paymentTypeId = $paymentTypeId;
        return $this;
    }

    /**
     * Get wawi invoice id.
     *
     * @return string|null
     */
    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    /**
     * Set wawi invoice id.
     *
     * @param string|null $invoiceId  wawi invoice id.
     * @return self
     */
    public function setInvoiceId(?string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }

    /**
     * Get the value of order.
     *
     * @return Bestellung|null
     */
    public function getOrder(): ?Bestellung
    {
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
}
