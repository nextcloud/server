<?php

namespace Doctrine\DBAL\Schema;

use BadMethodCallException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types;
use Doctrine\Deprecations\Deprecation;

use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function assert;
use function count;
use function get_class;
use function sprintf;
use function strtolower;

/**
 * Compares two Schemas and return an instance of SchemaDiff.
 *
 * @method SchemaDiff compareSchemas(Schema $fromSchema, Schema $toSchema)
 */
class Comparator
{
    private ?AbstractPlatform $platform;

    /** @internal The comparator can be only instantiated by a schema manager. */
    public function __construct(?AbstractPlatform $platform = null)
    {
        if ($platform === null) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/4746',
                'Not passing a $platform to %s is deprecated.'
                    . ' Use AbstractSchemaManager::createComparator() to instantiate the comparator.',
                __METHOD__,
            );
        }

        $this->platform = $platform;
    }

    /** @param list<mixed> $args */
    public function __call(string $method, array $args): SchemaDiff
    {
        if ($method !== 'compareSchemas') {
            throw new BadMethodCallException(sprintf('Unknown method "%s"', $method));
        }

        return $this->doCompareSchemas(...$args);
    }

    /** @param list<mixed> $args */
    public static function __callStatic(string $method, array $args): SchemaDiff
    {
        if ($method !== 'compareSchemas') {
            throw new BadMethodCallException(sprintf('Unknown method "%s"', $method));
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4707',
            'Calling %s::%s() statically is deprecated.',
            self::class,
            $method,
        );

        $comparator = new self();

        return $comparator->doCompareSchemas(...$args);
    }

    /**
     * Returns a SchemaDiff object containing the differences between the schemas $fromSchema and $toSchema.
     *
     * This method should be called non-statically since it will be declared as non-static in the next major release.
     *
     * @return SchemaDiff
     *
     * @throws SchemaException
     */
    private function doCompareSchemas(
        Schema $fromSchema,
        Schema $toSchema
    ) {
        $createdSchemas   = [];
        $droppedSchemas   = [];
        $createdTables    = [];
        $alteredTables    = [];
        $droppedTables    = [];
        $createdSequences = [];
        $alteredSequences = [];
        $droppedSequences = [];

        $orphanedForeignKeys = [];

        $foreignKeysToTable = [];

        foreach ($toSchema->getNamespaces() as $namespace) {
            if ($fromSchema->hasNamespace($namespace)) {
                continue;
            }

            $createdSchemas[$namespace] = $namespace;
        }

        foreach ($fromSchema->getNamespaces() as $namespace) {
            if ($toSchema->hasNamespace($namespace)) {
                continue;
            }

            $droppedSchemas[$namespace] = $namespace;
        }

        foreach ($toSchema->getTables() as $table) {
            $tableName = $table->getShortestName($toSchema->getName());
            if (! $fromSchema->hasTable($tableName)) {
                $createdTables[$tableName] = $toSchema->getTable($tableName);
            } else {
                $tableDifferences = $this->diffTable(
                    $fromSchema->getTable($tableName),
                    $toSchema->getTable($tableName),
                );

                if ($tableDifferences !== false) {
                    $alteredTables[$tableName] = $tableDifferences;
                }
            }
        }

        /* Check if there are tables removed */
        foreach ($fromSchema->getTables() as $table) {
            $tableName = $table->getShortestName($fromSchema->getName());

            $table = $fromSchema->getTable($tableName);
            if (! $toSchema->hasTable($tableName)) {
                $droppedTables[$tableName] = $table;
            }

            // also remember all foreign keys that point to a specific table
            foreach ($table->getForeignKeys() as $foreignKey) {
                $foreignTable = strtolower($foreignKey->getForeignTableName());
                if (! isset($foreignKeysToTable[$foreignTable])) {
                    $foreignKeysToTable[$foreignTable] = [];
                }

                $foreignKeysToTable[$foreignTable][] = $foreignKey;
            }
        }

        foreach ($droppedTables as $tableName => $table) {
            if (! isset($foreignKeysToTable[$tableName])) {
                continue;
            }

            foreach ($foreignKeysToTable[$tableName] as $foreignKey) {
                if (isset($droppedTables[strtolower($foreignKey->getLocalTableName())])) {
                    continue;
                }

                $orphanedForeignKeys[] = $foreignKey;
            }

            // deleting duplicated foreign keys present on both on the orphanedForeignKey
            // and the removedForeignKeys from changedTables
            foreach ($foreignKeysToTable[$tableName] as $foreignKey) {
                // strtolower the table name to make if compatible with getShortestName
                $localTableName = strtolower($foreignKey->getLocalTableName());
                if (! isset($alteredTables[$localTableName])) {
                    continue;
                }

                foreach ($alteredTables[$localTableName]->getDroppedForeignKeys() as $droppedForeignKey) {
                    assert($droppedForeignKey instanceof ForeignKeyConstraint);

                    // We check if the key is from the removed table if not we skip.
                    if ($tableName !== strtolower($droppedForeignKey->getForeignTableName())) {
                        continue;
                    }

                    $alteredTables[$localTableName]->unsetDroppedForeignKey($droppedForeignKey);
                }
            }
        }

        foreach ($toSchema->getSequences() as $sequence) {
            $sequenceName = $sequence->getShortestName($toSchema->getName());
            if (! $fromSchema->hasSequence($sequenceName)) {
                if (! $this->isAutoIncrementSequenceInSchema($fromSchema, $sequence)) {
                    $createdSequences[] = $sequence;
                }
            } else {
                if ($this->diffSequence($sequence, $fromSchema->getSequence($sequenceName))) {
                    $alteredSequences[] = $toSchema->getSequence($sequenceName);
                }
            }
        }

        foreach ($fromSchema->getSequences() as $sequence) {
            if ($this->isAutoIncrementSequenceInSchema($toSchema, $sequence)) {
                continue;
            }

            $sequenceName = $sequence->getShortestName($fromSchema->getName());

            if ($toSchema->hasSequence($sequenceName)) {
                continue;
            }

            $droppedSequences[] = $sequence;
        }

        $diff = new SchemaDiff(
            $createdTables,
            $alteredTables,
            $droppedTables,
            $fromSchema,
            $createdSchemas,
            $droppedSchemas,
            $createdSequences,
            $alteredSequences,
            $droppedSequences,
        );

        $diff->orphanedForeignKeys = $orphanedForeignKeys;

        return $diff;
    }

    /**
     * @deprecated Use non-static call to {@see compareSchemas()} instead.
     *
     * @return SchemaDiff
     *
     * @throws SchemaException
     */
    public function compare(Schema $fromSchema, Schema $toSchema)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4707',
            'Method compare() is deprecated. Use a non-static call to compareSchemas() instead.',
        );

        return $this->compareSchemas($fromSchema, $toSchema);
    }

    /**
     * @param Schema   $schema
     * @param Sequence $sequence
     */
    private function isAutoIncrementSequenceInSchema($schema, $sequence): bool
    {
        foreach ($schema->getTables() as $table) {
            if ($sequence->isAutoIncrementsFor($table)) {
                return true;
            }
        }

        return false;
    }

    /** @return bool */
    public function diffSequence(Sequence $sequence1, Sequence $sequence2)
    {
        if ($sequence1->getAllocationSize() !== $sequence2->getAllocationSize()) {
            return true;
        }

        return $sequence1->getInitialValue() !== $sequence2->getInitialValue();
    }

    /**
     * Returns the difference between the tables $fromTable and $toTable.
     *
     * If there are no differences this method returns the boolean false.
     *
     * @deprecated Use {@see compareTables()} and, optionally, {@see TableDiff::isEmpty()} instead.
     *
     * @return TableDiff|false
     *
     * @throws Exception
     */
    public function diffTable(Table $fromTable, Table $toTable)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5770',
            '%s is deprecated. Use compareTables() instead.',
            __METHOD__,
        );

        $diff = $this->compareTables($fromTable, $toTable);

        if ($diff->isEmpty()) {
            return false;
        }

        return $diff;
    }

    /**
     * Compares the tables and returns the difference between them.
     *
     * @throws Exception
     */
    public function compareTables(Table $fromTable, Table $toTable): TableDiff
    {
        $addedColumns        = [];
        $modifiedColumns     = [];
        $droppedColumns      = [];
        $addedIndexes        = [];
        $modifiedIndexes     = [];
        $droppedIndexes      = [];
        $addedForeignKeys    = [];
        $modifiedForeignKeys = [];
        $droppedForeignKeys  = [];

        $fromTableColumns = $fromTable->getColumns();
        $toTableColumns   = $toTable->getColumns();

        /* See if all the columns in "from" table exist in "to" table */
        foreach ($toTableColumns as $columnName => $column) {
            if ($fromTable->hasColumn($columnName)) {
                continue;
            }

            $addedColumns[$columnName] = $column;
        }

        /* See if there are any removed columns in "to" table */
        foreach ($fromTableColumns as $columnName => $column) {
            // See if column is removed in "to" table.
            if (! $toTable->hasColumn($columnName)) {
                $droppedColumns[$columnName] = $column;

                continue;
            }

            $toColumn = $toTable->getColumn($columnName);

            // See if column has changed properties in "to" table.
            $changedProperties = $this->diffColumn($column, $toColumn);

            if ($this->platform !== null) {
                if ($this->columnsEqual($column, $toColumn)) {
                    continue;
                }
            } elseif (count($changedProperties) === 0) {
                continue;
            }

            $modifiedColumns[$column->getName()] = new ColumnDiff(
                $column->getName(),
                $toColumn,
                $changedProperties,
                $column,
            );
        }

        $renamedColumns = $this->detectRenamedColumns($addedColumns, $droppedColumns);

        $fromTableIndexes = $fromTable->getIndexes();
        $toTableIndexes   = $toTable->getIndexes();

        /* See if all the indexes in "from" table exist in "to" table */
        foreach ($toTableIndexes as $indexName => $index) {
            if (($index->isPrimary() && $fromTable->getPrimaryKey() !== null) || $fromTable->hasIndex($indexName)) {
                continue;
            }

            $addedIndexes[$indexName] = $index;
        }

        /* See if there are any removed indexes in "to" table */
        foreach ($fromTableIndexes as $indexName => $index) {
            // See if index is removed in "to" table.
            if (
                ($index->isPrimary() && $toTable->getPrimaryKey() === null) ||
                ! $index->isPrimary() && ! $toTable->hasIndex($indexName)
            ) {
                $droppedIndexes[$indexName] = $index;

                continue;
            }

            // See if index has changed in "to" table.
            $toTableIndex = $index->isPrimary() ? $toTable->getPrimaryKey() : $toTable->getIndex($indexName);
            assert($toTableIndex instanceof Index);

            if (! $this->diffIndex($index, $toTableIndex)) {
                continue;
            }

            $modifiedIndexes[$indexName] = $toTableIndex;
        }

        $renamedIndexes = $this->detectRenamedIndexes($addedIndexes, $droppedIndexes);

        $fromForeignKeys = $fromTable->getForeignKeys();
        $toForeignKeys   = $toTable->getForeignKeys();

        foreach ($fromForeignKeys as $fromKey => $fromConstraint) {
            foreach ($toForeignKeys as $toKey => $toConstraint) {
                if ($this->diffForeignKey($fromConstraint, $toConstraint) === false) {
                    unset($fromForeignKeys[$fromKey], $toForeignKeys[$toKey]);
                } else {
                    if (strtolower($fromConstraint->getName()) === strtolower($toConstraint->getName())) {
                        $modifiedForeignKeys[] = $toConstraint;

                        unset($fromForeignKeys[$fromKey], $toForeignKeys[$toKey]);
                    }
                }
            }
        }

        foreach ($fromForeignKeys as $fromConstraint) {
            $droppedForeignKeys[] = $fromConstraint;
        }

        foreach ($toForeignKeys as $toConstraint) {
            $addedForeignKeys[] = $toConstraint;
        }

        return new TableDiff(
            $toTable->getName(),
            $addedColumns,
            $modifiedColumns,
            $droppedColumns,
            $addedIndexes,
            $modifiedIndexes,
            $droppedIndexes,
            $fromTable,
            $addedForeignKeys,
            $modifiedForeignKeys,
            $droppedForeignKeys,
            $renamedColumns,
            $renamedIndexes,
        );
    }

    /**
     * Try to find columns that only changed their name, rename operations maybe cheaper than add/drop
     * however ambiguities between different possibilities should not lead to renaming at all.
     *
     * @param array<string,Column> $addedColumns
     * @param array<string,Column> $removedColumns
     *
     * @return array<string,Column>
     *
     * @throws Exception
     */
    private function detectRenamedColumns(array &$addedColumns, array &$removedColumns): array
    {
        $candidatesByName = [];

        foreach ($addedColumns as $addedColumnName => $addedColumn) {
            foreach ($removedColumns as $removedColumn) {
                if (! $this->columnsEqual($addedColumn, $removedColumn)) {
                    continue;
                }

                $candidatesByName[$addedColumn->getName()][] = [$removedColumn, $addedColumn, $addedColumnName];
            }
        }

        $renamedColumns = [];

        foreach ($candidatesByName as $candidates) {
            if (count($candidates) !== 1) {
                continue;
            }

            [$removedColumn, $addedColumn] = $candidates[0];
            $removedColumnName             = $removedColumn->getName();
            $addedColumnName               = strtolower($addedColumn->getName());

            if (isset($renamedColumns[$removedColumnName])) {
                continue;
            }

            $renamedColumns[$removedColumnName] = $addedColumn;
            unset(
                $addedColumns[$addedColumnName],
                $removedColumns[strtolower($removedColumnName)],
            );
        }

        return $renamedColumns;
    }

    /**
     * Try to find indexes that only changed their name, rename operations maybe cheaper than add/drop
     * however ambiguities between different possibilities should not lead to renaming at all.
     *
     * @param array<string,Index> $addedIndexes
     * @param array<string,Index> $removedIndexes
     *
     * @return array<string,Index>
     */
    private function detectRenamedIndexes(array &$addedIndexes, array &$removedIndexes): array
    {
        $candidatesByName = [];

        // Gather possible rename candidates by comparing each added and removed index based on semantics.
        foreach ($addedIndexes as $addedIndexName => $addedIndex) {
            foreach ($removedIndexes as $removedIndex) {
                if ($this->diffIndex($addedIndex, $removedIndex)) {
                    continue;
                }

                $candidatesByName[$addedIndex->getName()][] = [$removedIndex, $addedIndex, $addedIndexName];
            }
        }

        $renamedIndexes = [];

        foreach ($candidatesByName as $candidates) {
            // If the current rename candidate contains exactly one semantically equal index,
            // we can safely rename it.
            // Otherwise, it is unclear if a rename action is really intended,
            // therefore we let those ambiguous indexes be added/dropped.
            if (count($candidates) !== 1) {
                continue;
            }

            [$removedIndex, $addedIndex] = $candidates[0];

            $removedIndexName = strtolower($removedIndex->getName());
            $addedIndexName   = strtolower($addedIndex->getName());

            if (isset($renamedIndexes[$removedIndexName])) {
                continue;
            }

            $renamedIndexes[$removedIndexName] = $addedIndex;
            unset(
                $addedIndexes[$addedIndexName],
                $removedIndexes[$removedIndexName],
            );
        }

        return $renamedIndexes;
    }

    /**
     * @internal The method should be only used from within the {@see Comparator} class hierarchy.
     *
     * @return bool
     */
    public function diffForeignKey(ForeignKeyConstraint $key1, ForeignKeyConstraint $key2)
    {
        if (
            array_map('strtolower', $key1->getUnquotedLocalColumns())
            !== array_map('strtolower', $key2->getUnquotedLocalColumns())
        ) {
            return true;
        }

        if (
            array_map('strtolower', $key1->getUnquotedForeignColumns())
            !== array_map('strtolower', $key2->getUnquotedForeignColumns())
        ) {
            return true;
        }

        if ($key1->getUnqualifiedForeignTableName() !== $key2->getUnqualifiedForeignTableName()) {
            return true;
        }

        if ($key1->onUpdate() !== $key2->onUpdate()) {
            return true;
        }

        return $key1->onDelete() !== $key2->onDelete();
    }

    /**
     * Compares the definitions of the given columns
     *
     * @internal The method should be only used from within the {@see Comparator} class hierarchy.
     *
     * @throws Exception
     */
    public function columnsEqual(Column $column1, Column $column2): bool
    {
        if ($this->platform === null) {
            return $this->diffColumn($column1, $column2) === [];
        }

        return $this->platform->columnsEqual($column1, $column2);
    }

    /**
     * Returns the difference between the columns
     *
     * If there are differences this method returns the changed properties as a
     * string array, otherwise an empty array gets returned.
     *
     * @deprecated Use {@see columnsEqual()} instead.
     *
     * @return string[]
     */
    public function diffColumn(Column $column1, Column $column2)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5650',
            '%s is deprecated. Use diffTable() instead.',
            __METHOD__,
        );

        $properties1 = $column1->toArray();
        $properties2 = $column2->toArray();

        $changedProperties = [];

        if (get_class($properties1['type']) !== get_class($properties2['type'])) {
            $changedProperties[] = 'type';
        }

        foreach (['notnull', 'unsigned', 'autoincrement'] as $property) {
            if ($properties1[$property] === $properties2[$property]) {
                continue;
            }

            $changedProperties[] = $property;
        }

        // Null values need to be checked additionally as they tell whether to create or drop a default value.
        // null != 0, null != false, null != '' etc. This affects platform's table alteration SQL generation.
        if (
            ($properties1['default'] === null) !== ($properties2['default'] === null)
            || $properties1['default'] != $properties2['default'] // @phpstan-ignore notEqual.notAllowed
        ) {
            $changedProperties[] = 'default';
        }

        if (
            ($properties1['type'] instanceof Types\StringType && ! $properties1['type'] instanceof Types\GuidType) ||
            $properties1['type'] instanceof Types\BinaryType
        ) {
            // check if value of length is set at all, default value assumed otherwise.
            $length1 = $properties1['length'] ?? 255;
            $length2 = $properties2['length'] ?? 255;
            if ($length1 !== $length2) {
                $changedProperties[] = 'length';
            }

            if ($properties1['fixed'] !== $properties2['fixed']) {
                $changedProperties[] = 'fixed';
            }
        } elseif ($properties1['type'] instanceof Types\DecimalType) {
            if (($properties1['precision'] ?? 10) !== ($properties2['precision'] ?? 10)) {
                $changedProperties[] = 'precision';
            }

            if ($properties1['scale'] !== $properties2['scale']) {
                $changedProperties[] = 'scale';
            }
        }

        // A null value and an empty string are actually equal for a comment so they should not trigger a change.
        if (
            $properties1['comment'] !== $properties2['comment'] &&
            ! ($properties1['comment'] === null && $properties2['comment'] === '') &&
            ! ($properties2['comment'] === null && $properties1['comment'] === '')
        ) {
            $changedProperties[] = 'comment';
        }

        $customOptions1 = $column1->getCustomSchemaOptions();
        $customOptions2 = $column2->getCustomSchemaOptions();

        foreach (array_merge(array_keys($customOptions1), array_keys($customOptions2)) as $key) {
            if (! array_key_exists($key, $properties1) || ! array_key_exists($key, $properties2)) {
                $changedProperties[] = $key;
            } elseif ($properties1[$key] !== $properties2[$key]) {
                $changedProperties[] = $key;
            }
        }

        $platformOptions1 = $column1->getPlatformOptions();
        $platformOptions2 = $column2->getPlatformOptions();

        foreach (array_keys(array_intersect_key($platformOptions1, $platformOptions2)) as $key) {
            if ($properties1[$key] === $properties2[$key]) {
                continue;
            }

            $changedProperties[] = $key;
        }

        return array_unique($changedProperties);
    }

    /**
     * Finds the difference between the indexes $index1 and $index2.
     *
     * Compares $index1 with $index2 and returns true if there are any
     * differences or false in case there are no differences.
     *
     * @internal The method should be only used from within the {@see Comparator} class hierarchy.
     *
     * @return bool
     */
    public function diffIndex(Index $index1, Index $index2)
    {
        return ! ($index1->isFulfilledBy($index2) && $index2->isFulfilledBy($index1));
    }
}
