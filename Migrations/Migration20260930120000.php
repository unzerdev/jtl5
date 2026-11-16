<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\Migrations;

use JTL\DB\ReturnType;
use JTL\Plugin\Migration;
use JTL\Update\IMigration;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairModel;

class Migration20260930120000 extends Migration implements IMigration
{
    public function up()
    {
        if (!$this->columnExists()) {
            $this->execute(
                'ALTER TABLE ' . KeyPairModel::TABLE . '
                ADD COLUMN `payment_method_ids` MEDIUMTEXT NULL DEFAULT NULL'
            );
        }
    }

    public function down()
    {
        if ($this->doDeleteData() && $this->columnExists()) {
            $this->execute(
                'ALTER TABLE `' . KeyPairModel::TABLE . '` DROP COLUMN payment_method_ids',
                ReturnType::DEFAULT
            );
        }
    }

    private function columnExists(): bool
    {
        return !empty(
        $this->getDB()->executeQuery(
            'SHOW COLUMNS FROM ' . KeyPairModel::TABLE . ' LIKE \'payment_method_ids\'',
            ReturnType::AFFECTED_ROWS
        )
        );
    }
}