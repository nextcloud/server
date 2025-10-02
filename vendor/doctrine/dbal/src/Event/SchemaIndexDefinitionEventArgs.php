<?php

namespace Doctrine\DBAL\Event;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Index;

/**
 * Event Arguments used when the portable index definition is generated inside {@see AbstractSchemaManager}.
 *
 * @deprecated
 */
class SchemaIndexDefinitionEventArgs extends SchemaEventArgs
{
    private ?Index $index = null;

    /**
     * Raw index data as fetched from the database.
     *
     * @var mixed[]
     */
    private array $tableIndex;

    /** @var string */
    private $table;

    private Connection $connection;

    /**
     * @param mixed[] $tableIndex
     * @param string  $table
     */
    public function __construct(array $tableIndex, $table, Connection $connection)
    {
        $this->tableIndex = $tableIndex;
        $this->table      = $table;
        $this->connection = $connection;
    }

    /**
     * Allows to clear the index which means the index will be excluded from tables index list.
     *
     * @return SchemaIndexDefinitionEventArgs
     */
    public function setIndex(?Index $index = null)
    {
        $this->index = $index;

        return $this;
    }

    /** @return Index|null */
    public function getIndex()
    {
        return $this->index;
    }

    /** @return mixed[] */
    public function getTableIndex()
    {
        return $this->tableIndex;
    }

    /** @return string */
    public function getTable()
    {
        return $this->table;
    }

    /** @return Connection */
    public function getConnection()
    {
        return $this->connection;
    }
}
