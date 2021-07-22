<?php declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Interfaces;

/**
 * Interface NotificationInterface
 * @package Plugin\s360_unzer_shop5\src\Payments\Interfaces
 */
interface NotificationInterface
{
    public const STATE_VALID           = 0;
    public const STATE_NOT_CONFIGURED  = 0x01;
    public const STATE_DURING_CHECKOUT = 0x02;
    public const STATE_INVALID         = 0xFF;

    /**
     * Init Backend Notifications
     *
     * @return void
     */
    public function initBackendNotification(): void;

    /**
     * Get the state message
     *
     * @param integer $state
     * @return string
     */
    public function getStateMessage(int $state) : string;
}
