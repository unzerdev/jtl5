<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Foundation;

use JTL\Helpers\Text;

/**
 * Json Serializable Trait
 * @package Plugin\s360_unzer_shop5\src\Foundation
 */
trait JsonSerializableTrait
{
    /**
     * Serialize struct to json.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $vars = get_object_vars($this);
        $this->convertDateTimePropertiesToJsonStringRepresentation($vars);

        return Text::utf8_convert_recursive($vars);
    }

    /**
     * Convert Date Time Properties to match JSON String Representation.
     *
     * @param array $array
     * @return void
     */
    protected function convertDateTimePropertiesToJsonStringRepresentation(array &$array): void
    {
        foreach ($array as &$value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTime::RFC3339_EXTENDED);
            }
        }
    }
}
