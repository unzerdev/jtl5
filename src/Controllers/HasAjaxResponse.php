<?php declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Controllers;

use JTL\Helpers\Text;
use RuntimeException;

/**
 * Ajax Response Trait
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers\Admin
 */
trait HasAjaxResponse
{
    /**
     * Encode data as utf-8 so we can use json_encode
     *
     * @param array|object|string $data
     * @return array|object|string
     */
    protected function encodeUTF8($data)
    {
        return Text::utf8_convert_recursive($data);
    }

    /**
     * Encode data and set correct header
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @throws RuntimeException if json_encode "throws" an error
     * @param array $data
     * @return void
     */
    protected function jsonResponse(array $data): void
    {
        header('Content-Type: text/json');
        $response = json_encode($this->encodeUTF8($data));

        if (json_last_error()) {
            throw new RuntimeException('Error in Ajax Response: ' . json_last_error_msg());
        }

        echo $response;
        exit();
    }
}
