<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Utils;

use Exception;
use JTL\DB\DbInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Jtl Link Helper Utility
 *
 * @package Plugin\s360_unzer_shop5\src\Utils
 */
class JtlLinkHelper
{
    use JtlLoggerTrait;

    /**
     * * Note: These must match the entires in info.xml !
     */
    public const ADMIN_TAB_SETTINGS = 'Einstellungen';
    public const ADMIN_TAB_ORDERS = 'Bestellungen';
    public const ADMIN_TAB_APPLE_PAY = 'Apple Pay';

    // Frontend Files
    public const FRONTEND_FILE_WEBHOOKS = 'webhook.php';

    /**
     * @var DbInterface
     */
    protected $database;

    /**
     * @var int $kPlugin
     */
    protected $kPlugin;

    public function __construct()
    {
        $plugin = Shop::Container()->get(Config::PLUGIN_ID);
        $this->kPlugin = $plugin ? $plugin->getID() : -1;
        $this->database = Shop::Container()->getDB();
    }

    /**
     * Returns the URL to the plugin backend.
     *
     * Note that this is not the same as the url pathwise to the plugins adminmenu folder.
     *
     * @return string
     */
    public function getFullAdminUrl(): string
    {
        return Shop::getAdminURL(true) . '/plugin.php?kPlugin=' . $this->kPlugin;
    }

    /**
     * Get the url for a frontend link of this plugin for the current language.
     *
     * @param string $filename Name of the frontendlink file
     * @param int $langId
     * @return null|string
     */
    public function getFullFrontendFileUrl(string $filename, int $langId = 0): ?string
    {
        try {
            if ($langId <= 0) {
                $langId = Shop::getLanguage();
            }

            if (!empty($langId)) {
                $query = 'SELECT * FROM `tpluginlinkdatei` as `tpl`
                    LEFT JOIN `tseo` as `ts` ON `ts`.`kKey` = `tpl`.`kLink`
                    WHERE `ts`.`cKey` = "kLink" AND `tpl`.kPlugin = :kPlugin
                        AND `tpl`.`cDatei` = :cDatei AND `ts`.`kSprache` = :kSprache';

                $params = ['kPlugin' => $this->kPlugin, 'cDatei' => $filename, 'kSprache' => $langId];
                $result = $this->database->executeQueryPrepared($query, $params, 1);

                if (!empty($result)) {
                    return Shop::getURL(true) . '/' . $result->cSeo;
                }

                // Fallback to default language
                if ($langId !== Shop::getLanguage()) {
                    return $this->getFullFrontendFileUrl($filename, Shop::getLanguage());
                }
            }

            return null;
        } catch (Exception $exc) {
            $this->debugLog(
                'Exception while trying to get frontend link url for file "' . $filename . '": ' . $exc->getMessage(),
                static::class
            );
        }
    }

    /**
     * Get the full url for an admin tab
     *
     * @param string $tabname
     * @return string|null
     */
    public function getFullAdminTabUrl(string $tabname): ?string
    {
        try {
            $result = $this->database->select(
                'tpluginadminmenu',
                ['kPlugin', 'cName'],
                [$this->kPlugin, $tabname]
            );

            if (!empty($result)) {
                return $this->getFullAdminUrl() . '#plugin-tab-' . $result->kPluginAdminMenu;
            }
        } catch (Exception $exc) {
            $this->debugLog(
                'Exception while trying to get admin link fpr file "' . $tabname . '": ' . $exc->getMessage(),
                static::class
            );
        }

        return null;
    }
}
