<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Foundation;

use JsonSerializable;

/**
 * Database Entity
 *
 * @package Plugin\s360_unzer_shop5\src\Foundation
 */
abstract class Entity implements JsonSerializable
{
    use JsonSerializableTrait;

    /**
     * @var int|null
     */
    protected $id;

    /**
     * Get ID of the entity.
     *
     * @return integer|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the id of the entity.
     *
     * @param integer $id
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get object representation of entity.
     *
     * @return \stdClass
     */
    abstract public function toObject(): \stdClass;

    /**
     * Create a new entity from a database object.
     *
     * @param \stdClass $data
     * @return Entity
     */
    abstract public static function create(\stdClass $data): Entity;
}
