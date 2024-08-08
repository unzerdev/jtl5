<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\Migrations;

use JTL\DB\ReturnType;
use JTL\Plugin\Migration;
use JTL\Update\IMigration;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;

class Migration20230925100000 extends Migration implements IMigration
{
    public function up()
    {
        if (!$this->columnExists()) {
            $this->execute(
                'ALTER TABLE ' . OrderMappingModel::ORDER_TABLE . '
                ADD COLUMN  `payment_method_id` INT(10) NULL'
            );
        }
    }

    public function down()
    {
        if ($this->doDeleteData() && $this->columnExists()) {
            $this->execute(
                'ALTER TABLE `' . OrderMappingModel::ORDER_TABLE . '` DROP COLUMN payment_method_id',
                ReturnType::DEFAULT
            );
        }
    }

    private function columnExists(): bool
    {
        return !empty(
            $this->getDB()->executeQuery(
                'SHOW COLUMNS FROM ' . OrderMappingModel::ORDER_TABLE . ' LIKE \'payment_method_id\'',
                ReturnType::AFFECTED_ROWS
            )
        );
    }
}
