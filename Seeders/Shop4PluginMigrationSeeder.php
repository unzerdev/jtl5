<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\Seeders;

use JTL\DB\ReturnType;
use Plugin\s360_unzer_shop5\src\Charges\ChargeMappingModel;
use Plugin\s360_unzer_shop5\src\Foundation\Seeder;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Migrate the Shop 4 Plugin Tables over to Shop 5 Plugin Tables.
 * @package Plugin\s360_unzer_shop5\Seeders
 */
class Shop4PluginMigrationSeeder extends Seeder
{
    // Shop 4 Plugin Table Names
    private const SHOP4_CONFIG_TABLE = 'xplugin_s360_unzer_shop4_config';
    private const SHOP4_CHARGE_TABLE = 'xplugin_s360_unzer_shop4_charge';
    private const SHOP4_ORDER_TABLE = 'xplugin_s360_unzer_shop4_order';

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        // Migrate Config Table
        $configMigrationQuery = 'INSERT INTO :target: (`key`, `value`) SELECT `key`, `value` FROM :source:';
        $this->migrateTable(self::SHOP4_CONFIG_TABLE, Config::TABLE, $configMigrationQuery);

        // Migrate Charge Table
        $chargeMigrationQuery =
            'INSERT INTO :target: (`id`, `order_id`, `charge_id`, `payment_id`)
            SELECT `id`, `order_id`, `charge_id`, `payment_id` FROM :source:';
        $this->migrateTable(self::SHOP4_CHARGE_TABLE, ChargeMappingModel::CHARGE_TABLE, $chargeMigrationQuery);

        // Migrate Order Mapping Table
        $orderMigrationQuery =
            'INSERT INTO :target: (
                `jtl_order_id`,
                `jtl_order_number`,
                `invoice_id`,
                `payment_id`,
                `transaction_unique_id`,
                `payment_state`,
                `payment_type_name`,
                `payment_type_id`
            )
            SELECT
                `jtl_order_id`,
                `jtl_order_number`,
                `invoice_id`,
                `payment_id`,
                `transaction_unique_id`,
                `payment_state`,
                `payment_type_name`,
                `payment_type_id`
            FROM :source:';
        $this->migrateTable(self::SHOP4_ORDER_TABLE, OrderMappingModel::ORDER_TABLE, $orderMigrationQuery);
    }

    /**
     * Migrate a plugin table if the source exists and the taret is empty!
     *
     * @param string $source
     * @param string $target
     * @param string $query
     * @return void
     */
    private function migrateTable(string $source, string $target, string $query): void
    {
        // if the old table table exists and the target is empty, fill the target with the old content.
        if ($this->tableExists($source) && $this->isTableEmpty($target)) {
            $this->database->executeQuery(
                str_replace([':target:', ':source:'], [$target, $source], $query),
                ReturnType::AFFECTED_ROWS
            );
        }
    }
}
