<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Utils;

use JTL\Shop;

/**
 * JTL Logger Trait
 * @package Plugin\s360_unzer_shop5\src\Utils
 */
trait JtlLoggerTrait
{
    /**
     * Write a log message as debug.
     *
     * @param mixed $message
     * @param string $context
     * @return void
     */
    public function debugLog($message, string $context = ''): void
    {
        $this->writeLog($message, JTLLOG_LEVEL_DEBUG, $context);
    }

    /**
     * Write a log message as notice.
     *
     * @param mixed $message
     * @param string $context
     * @return void
     */
    public function noticeLog($message, string $context = ''): void
    {
        $this->writeLog($message, JTLLOG_LEVEL_NOTICE, $context);
    }

    /**
     * Write a log message as error.
     *
     * @param mixed $message
     * @param string $context
     * @return void
     */
    public function errorLog($message, string $context = ''): void
    {
        $this->writeLog($message, JTLLOG_LEVEL_ERROR, $context);
    }

    /**
     * Write log entry
     *
     * @param mixed $message
     * @param integer $level
     * @param string $context
     * @return void
     */
    private function writeLog($message, int $level, string $context = ''): void
    {
        if ($context !== '') {
            $context .= ': ';
        }

        if (\is_array($message)) {
            foreach ($message as $msg) {
                Shop::Container()->getLogService()->addRecord($level, '[Unzer] ' . $context . $msg);
            }

            return;
        }

        if (\is_string($message)) {
            Shop::Container()->getLogService()->addRecord($level, '[Unzer] ' . $context . $message);
            return;
        }

        Shop::Container()->getLogService()->addRecord($level, '[Unzer] ' . $context . print_r($message, true));
    }
}
