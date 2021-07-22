<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Foundation;

use JTL\DB\DbInterface;
use JTL\DB\ReturnType;
use JTL\Shop;

/**
 * Seeder Base Class to populate database tables.
 * @package Plugin\s360_unzer_shop5\src\Foundation
 */
abstract class Seeder
{
    /**
     * @var DbInterface
     */
    protected $database;

    /**
     * @param DbInterface $database
     */
    public function __construct(DbInterface $database)
    {
        $this->database = $database;
    }

    /**
     * Get the Database instance.
     *
     * @return DbInterface
     */
    public function getDatabase(): DbInterface
    {
        return $this->database;
    }

    /**
     * Checks if a table exists.
     *
     * @param string $table
     * @return boolean
     */
    public function tableExists(string $table): bool
    {
        $test = $this->database->executeQuery('SHOW TABLES LIKE "' . $table . '"', ReturnType::ARRAY_OF_ASSOC_ARRAYS);
        return !empty($test);
    }

    /**
     * Checks if a table is empty.
     *
     * @param string $table
     * @return boolean
     */
    public function isTableEmpty(string $table): bool
    {
        $test = $this->database->selectAll($table, [], []);
        return \is_array($test) && empty($test);
    }

    /**
     * Run the seeder.
     *
     * @return void
     */
    abstract public function run(): void;

    /**
     * Factory Method for creating the seeder instance
     *
     * @return Seeder
     */
    public static function load(): Seeder
    {
        return new static(Shop::Container()->getDB());
    }
}
