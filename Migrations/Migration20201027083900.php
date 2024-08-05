<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\Migrations;

use JTL\DB\ReturnType;
use JTL\Plugin\Migration;
use JTL\Update\IMigration;
use Plugin\s360_unzer_shop5\src\Charges\ChargeMappingModel;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Migration for Unzer Shop 5 Plugin
 * @package Plugin\s360_unzer_shop5\src\Migrations
 */
class Migration20201027083900 extends Migration implements IMigration
{
    public function up()
    {
        // Create Config Table
        $this->execute('CREATE TABLE IF NOT EXISTS ' . Config::TABLE . ' (
            `key` VARCHAR(255) NOT NULL PRIMARY KEY,
            `value` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB CHARSET=utf8 COLLATE utf8_unicode_ci;');

        // Create Order Mapping Table
        $this->execute(
            'CREATE TABLE IF NOT EXISTS ' . OrderMappingModel::ORDER_TABLE . ' (
                `jtl_order_id` INT(10) NOT NULL,    /* jtl order table id (kBestellung) */
                `jtl_order_number` VARCHAR(255),    /* jtl order number (cBestellNr) */
                `invoice_id` VARCHAR(255),          /* JTL WaWi Invoice Id */
                `payment_id` VARCHAR(255),          /* payment id from heidelpay */
                `transaction_unique_id` VARCHAR(255),/* unique transaction id from heidelpay */
                `payment_state` VARCHAR(255),       /* payment state from heidelpay (cached) */
                `payment_type_name` VARCHAR(255),   /* payment type name from heidelpay */
                `payment_type_id` VARCHAR(255),     /* payment type id from heidelpay */
                PRIMARY KEY (`jtl_order_id`, `payment_id`)
            ) ENGINE=InnoDB CHARSET=utf8 COLLATE utf8_unicode_ci;'
        );

        // Create Charge Mapping Table
        $this->execute(
            'CREATE TABLE IF NOT EXISTS ' . ChargeMappingModel::CHARGE_TABLE . ' (
                `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `order_id`  INT(10) NOT NULL,    /* jtl order table id (kBestellung) */
                `charge_id` VARCHAR(255),        /* charge id from heidelpay */
                `payment_id` VARCHAR(255)       /* payment id from heidelpay */
            ) ENGINE=InnoDB CHARSET=utf8 COLLATE utf8_unicode_ci;'
        );
    }

    public function down()
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `' . Config::TABLE . '`', ReturnType::DEFAULT);
            $this->execute('DROP TABLE IF EXISTS `' . OrderMappingModel::ORDER_TABLE . '`', ReturnType::DEFAULT);
            $this->execute('DROP TABLE IF EXISTS `' . ChargeMappingModel::CHARGE_TABLE . '`', ReturnType::DEFAULT);
        }
    }
}
