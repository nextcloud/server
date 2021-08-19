<?php

namespace Doctrine\DBAL\Event;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;

/**
 * Event Arguments used when the portable column definition is generated inside {@link AbstractPlatform}.
 */
class SchemaColumnDefinitionEventArgs extends SchemaEventArgs
{
    /** @var Column|null */
    private $column;

    /**
     * Raw column data as fetched from the database.
     *
     * @var mixed[]
     */
    private $tableColumn;

    /** @var string */
    private $table;

    /** @var string */
    private $database;

    /** @var Connection */
    private $connection;

    /**
     * @param mixed[] $tableColumn
     * @param string  $table
     * @param string  $database
     */
    public function __construct(array $tableColumn, $table, $database, Connection $connection)
    {
        $this->tableColumn = $tableColumn;
        $this->table       = $table;
        $this->database    = $database;
        $this->connection  = $connection;
    }

    /**
     * Allows to clear the column which means the column will be excluded from
     * tables column list.
     *
     * @return SchemaColumnDefinitionEventArgs
     */
    public function setColumn(?Column $column = null)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @return Column|null
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return mixed[]
     */
    public function getTableColumn()
    {
        return $this->tableColumn;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
