<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Foundation;

use JsonSerializable;

/**
 * Abstract Struct Class
 * @package Plugin\s360_unzer_shop5\src\Foundation
 */
abstract class Struct implements JsonSerializable
{
    use JsonSerializableTrait;
}
