<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Utils;


/**
 * Helper class to allow for shop version checks if they are necessary to call the correct functions
 * or handle things differently.
 *
 * @package Plugin\s360_unzer_shop5\src\Utils
 */
final class Compatibility
{
    public static function isShopAtLeast52()
    {
        return version_compare(\APPLICATION_VERSION, '5.2.0-beta', '>=');
    }
}
