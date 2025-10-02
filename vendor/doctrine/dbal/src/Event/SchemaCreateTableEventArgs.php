<?php

namespace Doctrine\DBAL\Event;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Table;

use function array_merge;
use function func_get_args;
use function is_array;

/**
 * Event Arguments used when SQL queries for creating tables are generated inside {@see AbstractPlatform}.
 *
 * @deprecated
 */
class SchemaCreateTableEventArgs extends SchemaEventArgs
{
    private Table $table;

    /** @var mixed[][] */
    private array $columns;

    /** @var mixed[] */
    private array $options;

    private AbstractPlatform $platform;

    /** @var string[] */
    private array $sql = [];

    /**
     * @param mixed[][] $columns
     * @param mixed[]   $options
     */
    public function __construct(Table $table, array $columns, array $options, AbstractPlatform $platform)
    {
        $this->table    = $table;
        $this->columns  = $columns;
        $this->options  = $options;
        $this->platform = $platform;
    }

    /** @return Table */
    public function getTable()
    {
        return $this->table;
    }

    /** @return mixed[][] */
    public function getColumns()
    {
        return $this->columns;
    }

    /** @return mixed[] */
    public function getOptions()
    {
        return $this->options;
    }

    /** @return AbstractPlatform */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Passing multiple SQL statements as an array is deprecated. Pass each statement as an individual argument instead.
     *
     * @param string|string[] $sql
     *
     * @return SchemaCreateTableEventArgs
     */
    public function addSql($sql)
    {
        $this->sql = array_merge($this->sql, is_array($sql) ? $sql : func_get_args());

        return $this;
    }

    /** @return string[] */
    public function getSql()
    {
        return $this->sql;
    }
}
