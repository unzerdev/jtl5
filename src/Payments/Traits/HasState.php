<?php declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use Plugin\s360_unzer_shop5\src\Payments\Interfaces\NotificationInterface;

/**
 * Trait HasState
 * @package Plugin\s360_unzer_shop5\src\Payments\Traits
 */
trait HasState
{
    /**
     * @var int Current State.
     */
    protected $state = NotificationInterface::STATE_VALID;

    /**
     * Get state.
     *
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Set state.
     *
     * @param int $status
     * @return void
     */
    public function setState(int $status): void
    {
        $this->state = $status;
    }

    /**
     * Get the state message
     *
     * @param integer $state
     * @return string
     */
    public function getStateMessage(int $state): string
    {
        switch ($state) {
            case NotificationInterface::STATE_DURING_CHECKOUT:
                return __('hpPaymentMethodDuringCheckoutNotification');
            case NotificationInterface::STATE_NOT_CONFIGURED:
            default:
                return __('hpPaymentMethodNotConfiguredNotification');
        }
    }
}
