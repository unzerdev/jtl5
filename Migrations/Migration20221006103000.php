<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;
use Plugin\s360_unzer_shop5\src\Charges\ChargeMappingModel;

/**
 * Migration for Unzer Shop 5 Plugin
 * @package Plugin\s360_unzer_shop5\src\Migrations
 */
class Migration20221006103000 extends Migration implements IMigration
{
    public function up()
    {
        $this->execute(
            'ALTER TABLE ' . ChargeMappingModel::CHARGE_TABLE . '
            ADD `delivery_id` INT(10) DEFAULT NULL AFTER `payment_id`;'
        );
    }

    public function down()
    {
    }
}
