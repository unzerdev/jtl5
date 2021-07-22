<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Utils;

use JTL\Plugin\Helper;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Localization Helper
 * @package Plugin\s360_unzer_shop5\src\Utils
 */
trait TranslatorTrait
{
    /**
     * Get Translation
     *
     * @param string $key
     * @param string $languageIso
     * @return string|null
     */
    public function trans(string $key, string $languageIso = null): ?string
    {
        /** @var PluginInterface $plugin */
        $plugin = Shop::Container()->get(Config::PLUGIN_ID);

        if (empty($languageIso)) {
            return $plugin->getLocalization()->getTranslation($key) ?? $key;
        }

        return $plugin->getLocalization()->getTranslations()[$key][\mb_convert_case($languageIso, \MB_CASE_UPPER)]
            ?? null;
    }
}
