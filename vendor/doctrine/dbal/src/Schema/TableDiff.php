<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Deprecations\Deprecation;

use function array_filter;
use function array_values;
use function count;

/**
 * Table Diff.
 */
class TableDiff
{
    /**
     * @deprecated Use {@see getOldTable()} instead.
     *
     * @var string
     */
    public $name;

    /**
     * @deprecated Rename tables via {@link AbstractSchemaManager::renameTable()} instead.
     *
     * @var string|false
     */
    public $newName = false;

    /**
     * All added columns
     *
     * @internal Use {@see getAddedColumns()} instead.
     *
     * @var Column[]
     */
    public $addedColumns;

    /**
     * All modified columns
     *
     * @internal Use {@see getModifiedColumns()} instead.
     *
     * @var ColumnDiff[]
     */
    public $changedColumns = [];

    /**
     * All dropped columns
     *
     * @internal Use {@see getDroppedColumns()} instead.
     *
     * @var Column[]
     */
    public $removedColumns = [];

    /**
     * Columns that are only renamed from key to column instance name.
     *
     * @internal Use {@see getRenamedColumns()} instead.
     *
     * @var Column[]
     */
    public $renamedColumns = [];

    /**
     * All added indexes.
     *
     * @internal Use {@see getAddedIndexes()} instead.
     *
     * @var Index[]
     */
    public $addedIndexes = [];

    /**
     * All changed indexes.
     *
     * @internal Use {@see getModifiedIndexes()} instead.
     *
     * @var Index[]
     */
    public $changedIndexes = [];

    /**
     * All removed indexes
     *
     * @internal Use {@see getDroppedIndexes()} instead.
     *
     * @var Index[]
     */
    public $removedIndexes = [];

    /**
     * Indexes that are only renamed but are identical otherwise.
     *
     * @internal Use {@see getRenamedIndexes()} instead.
     *
     * @var Index[]
     */
    public $renamedIndexes = [];

    /**
     * All added foreign key definitions
     *
     * @internal Use {@see getAddedForeignKeys()} instead.
     *
     * @var ForeignKeyConstraint[]
     */
    public $addedForeignKeys = [];

    /**
     * All changed foreign keys
     *
     * @internal Use {@see getModifiedForeignKeys()} instead.
     *
     * @var ForeignKeyConstraint[]
     */
    public $changedForeignKeys = [];

    /**
     * All removed foreign keys
     *
     * @internal Use {@see getDroppedForeignKeys()} instead.
     *
     * @var (ForeignKeyConstraint|string)[]
     */
    public $removedForeignKeys = [];

    /**
     * @internal Use {@see getOldTable()} instead.
     *
     * @var Table|null
     */
    public $fromTable;

    /**
     * Constructs a TableDiff object.
     *
     * @internal The diff can be only instantiated by a {@see Comparator}.
     *
     * @param string                            $tableName
     * @param array<Column>                     $addedColumns
     * @param array<ColumnDiff>                 $modifiedColumns
     * @param array<Column>                     $droppedColumns
     * @param array<Index>                      $addedIndexes
     * @param array<Index>                      $changedIndexes
     * @param array<Index>                      $removedIndexes
     * @param list<ForeignKeyConstraint>        $addedForeignKeys
     * @param list<ForeignKeyConstraint>        $changedForeignKeys
     * @param list<ForeignKeyConstraint|string> $removedForeignKeys
     * @param array<string,Column>              $renamedColumns
     * @param array<string,Index>               $renamedIndexes
     */
    public function __construct(
        $tableName,
        $addedColumns = [],
        $modifiedColumns = [],
        $droppedColumns = [],
        $addedIndexes = [],
        $changedIndexes = [],
        $removedIndexes = [],
        ?Table $fromTable = null,
        $addedForeignKeys = [],
        $changedForeignKeys = [],
        $removedForeignKeys = [],
        $renamedColumns = [],
        $renamedIndexes = []
    ) {
        $this->name               = $tableName;
        $this->addedColumns       = $addedColumns;
        $this->changedColumns     = $modifiedColumns;
        $this->renamedColumns     = $renamedColumns;
        $this->removedColumns     = $droppedColumns;
        $this->addedIndexes       = $addedIndexes;
        $this->changedIndexes     = $changedIndexes;
        $this->renamedIndexes     = $renamedIndexes;
        $this->removedIndexes     = $removedIndexes;
        $this->addedForeignKeys   = $addedForeignKeys;
        $this->changedForeignKeys = $changedForeignKeys;
        $this->removedForeignKeys = $removedForeignKeys;

        if ($fromTable === null) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5678',
                'Not passing the $fromTable to %s is deprecated.',
                __METHOD__,
            );
        }

        $this->fromTable = $fromTable;
    }

    /**
     * @deprecated Use {@see getOldTable()} instead.
     *
     * @param AbstractPlatform $platform The platform to use for retrieving this table diff's name.
     *
     * @return Identifier
     */
    public function getName(AbstractPlatform $platform)
    {
        return new Identifier(
            $this->fromTable instanceof Table ? $this->fromTable->getQuotedName($platform) : $this->name,
        );
    }

    /**
     * @deprecated Rename tables via {@link AbstractSchemaManager::renameTable()} instead.
     *
     * @return Identifier|false
     */
    public function getNewName()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5663',
            '%s is deprecated. Rename tables via AbstractSchemaManager::renameTable() instead.',
            __METHOD__,
        );

        if ($this->newName === false) {
            return false;
        }

        return new Identifier($this->newName);
    }

    public function getOldTable(): ?Table
    {
        return $this->fromTable;
    }

    /** @return list<Column> */
    public function getAddedColumns(): array
    {
        return array_values($this->addedColumns);
    }

    /** @return list<ColumnDiff> */
    public function getModifiedColumns(): array
    {
        return array_values($this->changedColumns);
    }

    /** @return list<Column> */
    public function getDroppedColumns(): array
    {
        return array_values($this->removedColumns);
    }

    /** @return array<string,Column> */
    public function getRenamedColumns(): array
    {
        return $this->renamedColumns;
    }

    /** @return list<Index> */
    public function getAddedIndexes(): array
    {
        return array_values($this->addedIndexes);
    }

    /**
     * @internal This method exists only for compatibility with the current implementation of schema managers
     *           that modify the diff while processing it.
     */
    public function unsetAddedIndex(Index $index): void
    {
        $this->addedIndexes = array_filter(
            $this->addedIndexes,
            static function (Index $addedIndex) use ($index): bool {
                return $addedIndex !== $index;
            },
        );
    }

    /** @return array<Index> */
    public function getModifiedIndexes(): array
    {
        return array_values($this->changedIndexes);
    }

    /** @return list<Index> */
    public function getDroppedIndexes(): array
    {
        return array_values($this->removedIndexes);
    }

    /**
     * @internal This method exists only for compatibility with the current implementation of schema managers
     *           that modify the diff while processing it.
     */
    public function unsetDroppedIndex(Index $index): void
    {
        $this->removedIndexes = array_filter(
            $this->removedIndexes,
            static function (Index $removedIndex) use ($index): bool {
                return $removedIndex !== $index;
            },
        );
    }

    /** @return array<string,Index> */
    public function getRenamedIndexes(): array
    {
        return $this->renamedIndexes;
    }

    /** @return list<ForeignKeyConstraint> */
    public function getAddedForeignKeys(): array
    {
        return $this->addedForeignKeys;
    }

    /** @return list<ForeignKeyConstraint> */
    public function getModifiedForeignKeys(): array
    {
        return $this->changedForeignKeys;
    }

    /** @return list<ForeignKeyConstraint|string> */
    public function getDroppedForeignKeys(): array
    {
        return $this->removedForeignKeys;
    }

    /**
     * @internal This method exists only for compatibility with the current implementation of the schema comparator.
     *
     * @param ForeignKeyConstraint|string $foreignKey
     */
    public function unsetDroppedForeignKey($foreignKey): void
    {
        $this->removedForeignKeys = array_filter(
            $this->removedForeignKeys,
            static function ($removedForeignKey) use ($foreignKey): bool {
                return $removedForeignKey !== $foreignKey;
            },
        );
    }

    /**
     * Returns whether the diff is empty (contains no changes).
     */
    public function isEmpty(): bool
    {
        return count($this->addedColumns) === 0
            && count($this->changedColumns) === 0
            && count($this->removedColumns) === 0
            && count($this->renamedColumns) === 0
            && count($this->addedIndexes) === 0
            && count($this->changedIndexes) === 0
            && count($this->removedIndexes) === 0
            && count($this->renamedIndexes) === 0
            && count($this->addedForeignKeys) === 0
            && count($this->changedForeignKeys) === 0
            && count($this->removedForeignKeys) === 0;
    }
}
