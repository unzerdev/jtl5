<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\Migrations;

use JTL\DB\ReturnType;
use JTL\Plugin\Migration;
use JTL\Update\IMigration;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairModel;

class Migration20230919100000 extends Migration implements IMigration
{
    public function up()
    {
        // Create Order Mapping Table
        $this->execute(
            'CREATE TABLE IF NOT EXISTS ' . KeyPairModel::TABLE . ' (
                `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `private_key` VARCHAR(255) NOT NULL,
                `public_key` VARCHAR(255) NOT NULL,
                `is_b2b` TINYINT(1) DEFAULT 0,
                `currency_id` INT(10) NOT NULL,
                `payment_method_id` INT(10) NOT NULL
            ) ENGINE=InnoDB CHARSET=utf8 COLLATE utf8_unicode_ci;'
        );
    }

    public function down()
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `' . KeyPairModel::TABLE . '`', ReturnType::DEFAULT);
        }
    }
}
