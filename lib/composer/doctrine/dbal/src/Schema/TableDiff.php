<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Table Diff.
 */
class TableDiff
{
    /** @var string */
    public $name;

    /** @var string|false */
    public $newName = false;

    /**
     * All added columns
     *
     * @var Column[]
     */
    public $addedColumns;

    /**
     * All changed columns
     *
     * @var ColumnDiff[]
     */
    public $changedColumns = [];

    /**
     * All removed columns
     *
     * @var Column[]
     */
    public $removedColumns = [];

    /**
     * Columns that are only renamed from key to column instance name.
     *
     * @var Column[]
     */
    public $renamedColumns = [];

    /**
     * All added indexes.
     *
     * @var Index[]
     */
    public $addedIndexes = [];

    /**
     * All changed indexes.
     *
     * @var Index[]
     */
    public $changedIndexes = [];

    /**
     * All removed indexes
     *
     * @var Index[]
     */
    public $removedIndexes = [];

    /**
     * Indexes that are only renamed but are identical otherwise.
     *
     * @var Index[]
     */
    public $renamedIndexes = [];

    /**
     * All added foreign key definitions
     *
     * @var ForeignKeyConstraint[]
     */
    public $addedForeignKeys = [];

    /**
     * All changed foreign keys
     *
     * @var ForeignKeyConstraint[]
     */
    public $changedForeignKeys = [];

    /**
     * All removed foreign keys
     *
     * @var ForeignKeyConstraint[]|string[]
     */
    public $removedForeignKeys = [];

    /** @var Table|null */
    public $fromTable;

    /**
     * Constructs an TableDiff object.
     *
     * @param string       $tableName
     * @param Column[]     $addedColumns
     * @param ColumnDiff[] $changedColumns
     * @param Column[]     $removedColumns
     * @param Index[]      $addedIndexes
     * @param Index[]      $changedIndexes
     * @param Index[]      $removedIndexes
     */
    public function __construct(
        $tableName,
        $addedColumns = [],
        $changedColumns = [],
        $removedColumns = [],
        $addedIndexes = [],
        $changedIndexes = [],
        $removedIndexes = [],
        ?Table $fromTable = null
    ) {
        $this->name           = $tableName;
        $this->addedColumns   = $addedColumns;
        $this->changedColumns = $changedColumns;
        $this->removedColumns = $removedColumns;
        $this->addedIndexes   = $addedIndexes;
        $this->changedIndexes = $changedIndexes;
        $this->removedIndexes = $removedIndexes;
        $this->fromTable      = $fromTable;
    }

    /**
     * @param AbstractPlatform $platform The platform to use for retrieving this table diff's name.
     *
     * @return Identifier
     */
    public function getName(AbstractPlatform $platform)
    {
        return new Identifier(
            $this->fromTable instanceof Table ? $this->fromTable->getQuotedName($platform) : $this->name
        );
    }

    /**
     * @return Identifier|false
     */
    public function getNewName()
    {
        if ($this->newName === false) {
            return false;
        }

        return new Identifier($this->newName);
    }
}
