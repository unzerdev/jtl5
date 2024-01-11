<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Utils;

use JTL\Exceptions\CircularReferenceException;
use JTL\Exceptions\ServiceNotFoundException;
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
        self::log($message, LOGLEVEL_DEBUG, $context);
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
        self::log($message, LOGLEVEL_NOTICE, $context);
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
        self::log($message, LOGLEVEL_ERROR, $context);
    }

    public static function log(string $message, int $level, array $context = []): void
    {
        try {
            // fallback for shop versions < 5.3.0
            $logger = Shop::Container()->getLogService();

            if (Compatibility::isShopAtLeast53()) {
                /** @var Logger $logger */
                $logger = Shop::Container()->get(Config::PLUGIN_ID)->getLogger();
            }

            if ($logger->isHandling($level)) {
                $logger->addRecord($level, self::LOG_PREFIX . $message, $context);
            }
        } catch (ServiceNotFoundException $ex) {
            // Too bad, no logging service exists. We cannot log this - ignore it.
        } catch (CircularReferenceException $ex) {
            // This should not be possible, unless this trait is used within a Logging service.
            // We cannot log this - ignore it.
        }
    }
}
