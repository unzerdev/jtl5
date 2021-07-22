<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Foundation;

use JTL\DB\DbInterface;
use stdClass;

/**
 * Abstract Model
 * @package Plugin\s360_unzer_shop5\src\Foundation
 */
abstract class Model
{
    /**
     * @var string Primary Key Column Name
     */
    protected $primaryKey = 'id';

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
     * Get the name of the table.
     *
     * @return string
     */
    abstract public function getTable(): string;

    /**
     * Create the corresponding entity for a database object.
     *
     * @param stdClass $data
     * @return Entity
     */
    abstract public function createEntity(stdClass $data): Entity;

    /**
     * Save an entity to the database.
     *
     * @param Entity $entity
     * @return int Number of affected rows
     */
    public function save(Entity $entity): int
    {
        if (empty($this->database->select($this->getTable(), $this->primaryKey, $entity->getId()))) {
            return (int) $this->database->insert($this->getTable(), $entity->toObject());
        }

        return (int) $this->database->update(
            $this->getTable(),
            $this->primaryKey,
            $entity->getId(),
            $entity->toObject()
        );
    }

    /**
     * Find an entity based on its primary key.
     *
     * @param int $id
     * @return Entity|null
     */
    public function find(int $id): ?Entity
    {
        $result = $this->database->select($this->getTable(), $this->primaryKey, $id);

        if ($result) {
            return $this->createEntity($result);
        }

        return null;
    }

    /**
     * Delete an entity.
     *
     * @param int $id
     * @return int Number of affected rows
     */
    public function delete(int $id): int
    {
        return $this->database->delete($this->getTable(), $this->primaryKey, $id);
    }
}
