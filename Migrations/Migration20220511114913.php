<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Migration for Unzer Shop 5 Plugin
 * @package Plugin\s360_unzer_shop5\src\Migrations
 */
class Migration20220511114913 extends Migration implements IMigration
{
    public function up()
    {
        // Create Config Table
        $this->execute(
            'ALTER TABLE ' . Config::TABLE . '
            CHANGE `value` `value` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;'
        );
    }

    public function down()
    {
    }
}
