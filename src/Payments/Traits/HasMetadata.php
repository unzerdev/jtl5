<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use UnzerSDK\Resources\Metadata;

/**
 * Add Meta Data to transactions
 *
 * @package Plugin\s360_unzer_shop5\src\Payments\Traits
 */
trait HasMetadata
{
    /**
     * Create Metadata object
     *
     * @return Metadata
     */
    public function createMetadata(): Metadata
    {
        /** @var PluginInterface $plugin */
        $plugin = Shop::Container()->get(Config::PLUGIN_ID);

        return (new Metadata())
            ->setShopType('JTL')
            ->setShopVersion(\APPLICATION_VERSION)
            ->addMetadata('pluginType', 'unzerdev/jtl5')
            ->addMetadata('pluginVersion', (string) $plugin->getMeta()->getVersion())
            ->addMetadata('PHP', phpversion());
    }
}
