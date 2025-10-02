<?php

namespace Doctrine\DBAL\Event;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\Deprecations\Deprecation;

use function array_merge;
use function func_get_args;
use function is_array;

/**
 * Event Arguments used when SQL queries for adding table columns are generated inside {@see AbstractPlatform}.
 *
 * @deprecated
 */
class SchemaAlterTableAddColumnEventArgs extends SchemaEventArgs
{
    private Column $column;
    private TableDiff $tableDiff;
    private AbstractPlatform $platform;

    /** @var string[] */
    private array $sql = [];

    public function __construct(Column $column, TableDiff $tableDiff, AbstractPlatform $platform)
    {
        $this->column    = $column;
        $this->tableDiff = $tableDiff;
        $this->platform  = $platform;
    }

    /** @return Column */
    public function getColumn()
    {
        return $this->column;
    }

    /** @return TableDiff */
    public function getTableDiff()
    {
        return $this->tableDiff;
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
     * @return SchemaAlterTableAddColumnEventArgs
     */
    public function addSql($sql)
    {
        if (is_array($sql)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/3580',
                'Passing multiple SQL statements as an array to SchemaAlterTableAddColumnEventaArrgs::addSql() ' .
                'is deprecated. Pass each statement as an individual argument instead.',
            );
        }

        $this->sql = array_merge($this->sql, is_array($sql) ? $sql : func_get_args());

        return $this;
    }

    /** @return string[] */
    public function getSql()
    {
        return $this->sql;
    }
}
