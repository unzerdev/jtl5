<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Utils;

use JTL\Shop;

/**
 * Log Service Helper
 *
 * @package Plugin\s360_unzer_shop5\src\Utils
 */
class Logger
{
    public const LOG_PREFIX = '[Unzer]: ';

    /**
     * Debug Log
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        Shop::Container()->getLogService()->debug(self::LOG_PREFIX . $message, $context);
    }

    /**
     * Notice Log
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function notice(string $message, array $context = []): void
    {
        Shop::Container()->getLogService()->notice(self::LOG_PREFIX . $message, $context);
    }

    /**
     * Error Log
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        Shop::Container()->getLogService()->error(self::LOG_PREFIX . $message, $context);
    }
}
