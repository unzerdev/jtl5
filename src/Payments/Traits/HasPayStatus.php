<?php declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

/**
 * Trait HasPayStatus
 * @package Plugin\s360_unzer_shop5\src\Payments\Traits
 */
trait HasPayStatus
{
    /**
     * @var string Payment Status
     */
    protected $payStatus = '';

    /**
     * Get payment status
     *
     * @return string
     */
    public function getPayStatus(): string
    {
        return $this->payStatus;
    }

    /**
     * Set Payment Status
     *
     * @param string $status
     * @return void
     */
    public function setPayStatus(string $status): void
    {
        $this->payStatus = $status;
    }
}
