<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Orders;

use Plugin\s360_unzer_shop5\src\Foundation\Struct;

/**
 * Struct for Backend Order Views
 * @package Plugin\s360_unzer_shop5\src\Orders
 */
class OrderViewStruct extends Struct
{
    /**
     * @var OrderMappingEntity
     */
    protected $order;

    /**
     * @var string Rendered view
     */
    protected $view;

    /**
     * @param OrderMappingEntity $order
     * @param string $view
     */
    public function __construct(OrderMappingEntity $order, string $view)
    {
        $this->setOrder($order);
        $this->setView($view);
    }

    /**
     * Get rendered view
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * Set rendered view
     *
     * @param string $view  Rendered view
     * @return self
     */
    public function setView(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Get the value of order
     *
     * @return OrderMappingEntity
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the value of order
     *
     * @param OrderMappingEntity $order
     * @return self
     */
    public function setOrder(OrderMappingEntity $order): self
    {
        $this->order = $order;
        return $this;
    }
}
