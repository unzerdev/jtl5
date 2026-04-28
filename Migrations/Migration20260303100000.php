<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20260303100000 extends Migration implements IMigration
{
    public function up()
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS xplugin_s360_unzer_shop5_saved_payment_data (
                `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `kKunde` INT(10) UNSIGNED NOT NULL,
                `payment_method` VARCHAR(36) NOT NULL,
                `payment_type_id` VARCHAR(32) NOT NULL,
                `data` TEXT NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                FOREIGN KEY (`kKunde`) REFERENCES `tkunde`(`kKunde`) ON DELETE CASCADE
            ) ENGINE=InnoDB CHARSET=utf8 COLLATE utf8_unicode_ci;'
        );
    }

    public function down()
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `xplugin_s360_unzer_shop5_saved_payment_data`');
        }
    }

}
