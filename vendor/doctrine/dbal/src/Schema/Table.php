<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Exception\InvalidTableName;
use Doctrine\DBAL\Schema\Visitor\Visitor;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;

use function array_filter;
use function array_keys;
use function array_merge;
use function in_array;
use function preg_match;
use function strlen;
use function strtolower;

use const ARRAY_FILTER_USE_KEY;

/**
 * Object Representation of a table.
 */
class Table extends AbstractAsset
{
    /** @var Column[] */
    protected $_columns = [];

    /** @var Index[] */
    protected $_indexes = [];

    /** @var string|null */
    protected $_primaryKeyName;

    /** @var UniqueConstraint[] */
    protected $uniqueConstraints = [];

    /** @var ForeignKeyConstraint[] */
    protected $_fkConstraints = [];

    /** @var mixed[] */
    protected $_options = [
        'create_options' => [],
    ];

    /** @var SchemaConfig|null */
    protected $_schemaConfig;

    /** @var Index[] */
    private array $implicitIndexes = [];

    /**
     * @param Column[]               $columns
     * @param Index[]                $indexes
     * @param UniqueConstraint[]     $uniqueConstraints
     * @param ForeignKeyConstraint[] $fkConstraints
     * @param mixed[]                $options
     *
     * @throws SchemaException
     * @throws Exception
     */
    public function __construct(
        string $name,
        array $columns = [],
        array $indexes = [],
        array $uniqueConstraints = [],
        array $fkConstraints = [],
        array $options = []
    ) {
        if ($name === '') {
            throw InvalidTableName::new($name);
        }

        $this->_setName($name);

        foreach ($columns as $column) {
            $this->_addColumn($column);
        }

        foreach ($indexes as $idx) {
            $this->_addIndex($idx);
        }

        foreach ($uniqueConstraints as $uniqueConstraint) {
            $this->_addUniqueConstraint($uniqueConstraint);
        }

        foreach ($fkConstraints as $constraint) {
            $this->_addForeignKeyConstraint($constraint);
        }

        $this->_options = array_merge($this->_options, $options);
    }

    /** @return void */
    public function setSchemaConfig(SchemaConfig $schemaConfig)
    {
        $this->_schemaConfig = $schemaConfig;
    }

    /** @return int */
    protected function _getMaxIdentifierLength()
    {
        if ($this->_schemaConfig instanceof SchemaConfig) {
            return $this->_schemaConfig->getMaxIdentifierLength();
        }

        return 63;
    }

    /**
     * Sets the Primary Key.
     *
     * @param string[]     $columnNames
     * @param string|false $indexName
     *
     * @return self
     *
     * @throws SchemaException
     */
    public function setPrimaryKey(array $columnNames, $indexName = false)
    {
        if ($indexName === false) {
            $indexName = 'primary';
        }

        $this->_addIndex($this->_createIndex($columnNames, $indexName, true, true));

        foreach ($columnNames as $columnName) {
            $column = $this->getColumn($columnName);
            $column->setNotnull(true);
        }

        return $this;
    }

    /**
     * @param string[] $columnNames
     * @param string[] $flags
     * @param mixed[]  $options
     *
     * @return self
     *
     * @throws SchemaException
     */
    public function addIndex(array $columnNames, ?string $indexName = null, array $flags = [], array $options = [])
    {
        $indexName ??= $this->_generateIdentifierName(
            array_merge([$this->getName()], $columnNames),
            'idx',
            $this->_getMaxIdentifierLength(),
        );

        return $this->_addIndex($this->_createIndex($columnNames, $indexName, false, false, $flags, $options));
    }

    /**
     * @param string[] $columnNames
     * @param string[] $flags
     * @param mixed[]  $options
     *
     * @return self
     */
    public function addUniqueConstraint(
        array $columnNames,
        ?string $indexName = null,
        array $flags = [],
        array $options = []
    ): Table {
        $indexName ??= $this->_generateIdentifierName(
            array_merge([$this->getName()], $columnNames),
            'uniq',
            $this->_getMaxIdentifierLength(),
        );

        return $this->_addUniqueConstraint($this->_createUniqueConstraint($columnNames, $indexName, $flags, $options));
    }

    /**
     * Drops the primary key from this table.
     *
     * @return void
     *
     * @throws SchemaException
     */
    public function dropPrimaryKey()
    {
        if ($this->_primaryKeyName === null) {
            return;
        }

        $this->dropIndex($this->_primaryKeyName);
        $this->_primaryKeyName = null;
    }

    /**
     * Drops an index from this table.
     *
     * @param string $name The index name.
     *
     * @return void
     *
     * @throws SchemaException If the index does not exist.
     */
    public function dropIndex($name)
    {
        $name = $this->normalizeIdentifier($name);

        if (! $this->hasIndex($name)) {
            throw SchemaException::indexDoesNotExist($name, $this->_name);
        }

        unset($this->_indexes[$name]);
    }

    /**
     * @param string[]    $columnNames
     * @param string|null $indexName
     * @param mixed[]     $options
     *
     * @return self
     *
     * @throws SchemaException
     */
    public function addUniqueIndex(array $columnNames, $indexName = null, array $options = [])
    {
        $indexName ??= $this->_generateIdentifierName(
            array_merge([$this->getName()], $columnNames),
            'uniq',
            $this->_getMaxIdentifierLength(),
        );

        return $this->_addIndex($this->_createIndex($columnNames, $indexName, true, false, [], $options));
    }

    /**
     * Renames an index.
     *
     * @param string      $oldName The name of the index to rename from.
     * @param string|null $newName The name of the index to rename to.
     *                                  If null is given, the index name will be auto-generated.
     *
     * @return self This table instance.
     *
     * @throws SchemaException If no index exists for the given current name
     *                         or if an index with the given new name already exists on this table.
     */
    public function renameIndex($oldName, $newName = null)
    {
        $oldName           = $this->normalizeIdentifier($oldName);
        $normalizedNewName = $this->normalizeIdentifier($newName);

        if ($oldName === $normalizedNewName) {
            return $this;
        }

        if (! $this->hasIndex($oldName)) {
            throw SchemaException::indexDoesNotExist($oldName, $this->_name);
        }

        if ($this->hasIndex($normalizedNewName)) {
            throw SchemaException::indexAlreadyExists($normalizedNewName, $this->_name);
        }

        $oldIndex = $this->_indexes[$oldName];

        if ($oldIndex->isPrimary()) {
            $this->dropPrimaryKey();

            return $this->setPrimaryKey($oldIndex->getColumns(), $newName ?? false);
        }

        unset($this->_indexes[$oldName]);

        if ($oldIndex->isUnique()) {
            return $this->addUniqueIndex($oldIndex->getColumns(), $newName, $oldIndex->getOptions());
        }

        return $this->addIndex($oldIndex->getColumns(), $newName, $oldIndex->getFlags(), $oldIndex->getOptions());
    }

    /**
     * Checks if an index begins in the order of the given columns.
     *
     * @param string[] $columnNames
     *
     * @return bool
     */
    public function columnsAreIndexed(array $columnNames)
    {
        foreach ($this->getIndexes() as $index) {
            if ($index->spansColumns($columnNames)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $columnNames
     * @param string   $indexName
     * @param bool     $isUnique
     * @param bool     $isPrimary
     * @param string[] $flags
     * @param mixed[]  $options
     *
     * @throws SchemaException
     */
    private function _createIndex(
        array $columnNames,
        $indexName,
        $isUnique,
        $isPrimary,
        array $flags = [],
        array $options = []
    ): Index {
        if (preg_match('(([^a-zA-Z0-9_]+))', $this->normalizeIdentifier($indexName)) === 1) {
            throw SchemaException::indexNameInvalid($indexName);
        }

        foreach ($columnNames as $columnName) {
            if (! $this->hasColumn($columnName)) {
                throw SchemaException::columnDoesNotExist($columnName, $this->_name);
            }
        }

        return new Index($indexName, $columnNames, $isUnique, $isPrimary, $flags, $options);
    }

    /**
     * @param string  $name
     * @param string  $typeName
     * @param mixed[] $options
     *
     * @return Column
     *
     * @throws SchemaException
     */
    public function addColumn($name, $typeName, array $options = [])
    {
        $column = new Column($name, Type::getType($typeName), $options);

        $this->_addColumn($column);

        return $column;
    }

    /**
     * Change Column Details.
     *
     * @deprecated Use {@link modifyColumn()} instead.
     *
     * @param string  $name
     * @param mixed[] $options
     *
     * @return self
     *
     * @throws SchemaException
     */
    public function changeColumn($name, array $options)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5747',
            '%s is deprecated. Use modifyColumn() instead.',
            __METHOD__,
        );

        return $this->modifyColumn($name, $options);
    }

    /**
     * @param string  $name
     * @param mixed[] $options
     *
     * @return self
     *
     * @throws SchemaException
     */
    public function modifyColumn($name, array $options)
    {
        $column = $this->getColumn($name);
        $column->setOptions($options);

        return $this;
    }

    /**
     * Drops a Column from the Table.
     *
     * @param string $name
     *
     * @return self
     */
    public function dropColumn($name)
    {
        $name = $this->normalizeIdentifier($name);

        unset($this->_columns[$name]);

        return $this;
    }

    /**
     * Adds a foreign key constraint.
     *
     * Name is inferred from the local columns.
     *
     * @param Table|string $foreignTable       Table schema instance or table name
     * @param string[]     $localColumnNames
     * @param string[]     $foreignColumnNames
     * @param mixed[]      $options
     * @param string|null  $name
     *
     * @return self
     *
     * @throws SchemaException
     */
    public function addForeignKeyConstraint(
        $foreignTable,
        array $localColumnNames,
        array $foreignColumnNames,
        array $options = [],
        $name = null
    ) {
        $name ??= $this->_generateIdentifierName(
            array_merge([$this->getName()], $localColumnNames),
            'fk',
            $this->_getMaxIdentifierLength(),
        );

        if ($foreignTable instanceof Table) {
            foreach ($foreignColumnNames as $columnName) {
                if (! $foreignTable->hasColumn($columnName)) {
                    throw SchemaException::columnDoesNotExist($columnName, $foreignTable->getName());
                }
            }
        }

        foreach ($localColumnNames as $columnName) {
            if (! $this->hasColumn($columnName)) {
                throw SchemaException::columnDoesNotExist($columnName, $this->_name);
            }
        }

        $constraint = new ForeignKeyConstraint(
            $localColumnNames,
            $foreignTable,
            $foreignColumnNames,
            $name,
            $options,
        );

        return $this->_addForeignKeyConstraint($constraint);
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return self
     */
    public function addOption($name, $value)
    {
        $this->_options[$name] = $value;

        return $this;
    }

    /**
     * @return void
     *
     * @throws SchemaException
     */
    protected function _addColumn(Column $column)
    {
        $columnName = $column->getName();
        $columnName = $this->normalizeIdentifier($columnName);

        if (isset($this->_columns[$columnName])) {
            throw SchemaException::columnAlreadyExists($this->getName(), $columnName);
        }

        $this->_columns[$columnName] = $column;
    }

    /**
     * Adds an index to the table.
     *
     * @return self
     *
     * @throws SchemaException
     */
    protected function _addIndex(Index $indexCandidate)
    {
        $indexName               = $indexCandidate->getName();
        $indexName               = $this->normalizeIdentifier($indexName);
        $replacedImplicitIndexes = [];

        foreach ($this->implicitIndexes as $name => $implicitIndex) {
            if (! $implicitIndex->isFulfilledBy($indexCandidate) || ! isset($this->_indexes[$name])) {
                continue;
            }

            $replacedImplicitIndexes[] = $name;
        }

        if (
            (isset($this->_indexes[$indexName]) && ! in_array($indexName, $replacedImplicitIndexes, true)) ||
            ($this->_primaryKeyName !== null && $indexCandidate->isPrimary())
        ) {
            throw SchemaException::indexAlreadyExists($indexName, $this->_name);
        }

        foreach ($replacedImplicitIndexes as $name) {
            unset($this->_indexes[$name], $this->implicitIndexes[$name]);
        }

        if ($indexCandidate->isPrimary()) {
            $this->_primaryKeyName = $indexName;
        }

        $this->_indexes[$indexName] = $indexCandidate;

        return $this;
    }

    /** @return self */
    protected function _addUniqueConstraint(UniqueConstraint $constraint): Table
    {
        $mergedNames = array_merge([$this->getName()], $constraint->getColumns());
        $name        = strlen($constraint->getName()) > 0
            ? $constraint->getName()
            : $this->_generateIdentifierName($mergedNames, 'fk', $this->_getMaxIdentifierLength());

        $name = $this->normalizeIdentifier($name);

        $this->uniqueConstraints[$name] = $constraint;

        // If there is already an index that fulfills this requirements drop the request. In the case of __construct
        // calling this method during hydration from schema-details all the explicitly added indexes lead to duplicates.
        // This creates computation overhead in this case, however no duplicate indexes are ever added (column based).
        $indexName = $this->_generateIdentifierName($mergedNames, 'idx', $this->_getMaxIdentifierLength());

        $indexCandidate = $this->_createIndex($constraint->getColumns(), $indexName, true, false);

        foreach ($this->_indexes as $existingIndex) {
            if ($indexCandidate->isFulfilledBy($existingIndex)) {
                return $this;
            }
        }

        $this->implicitIndexes[$this->normalizeIdentifier($indexName)] = $indexCandidate;

        return $this;
    }

    /** @return self */
    protected function _addForeignKeyConstraint(ForeignKeyConstraint $constraint)
    {
        $constraint->setLocalTable($this);

        if (strlen($constraint->getName()) > 0) {
            $name = $constraint->getName();
        } else {
            $name = $this->_generateIdentifierName(
                array_merge([$this->getName()], $constraint->getLocalColumns()),
                'fk',
                $this->_getMaxIdentifierLength(),
            );
        }

        $name = $this->normalizeIdentifier($name);

        $this->_fkConstraints[$name] = $constraint;

        /* Add an implicit index (defined by the DBAL) on the foreign key
           columns. If there is already a user-defined index that fulfills these
           requirements drop the request. In the case of __construct() calling
           this method during hydration from schema-details, all the explicitly
           added indexes lead to duplicates. This creates computation overhead in
           this case, however no duplicate indexes are ever added (based on
           columns). */
        $indexName = $this->_generateIdentifierName(
            array_merge([$this->getName()], $constraint->getColumns()),
            'idx',
            $this->_getMaxIdentifierLength(),
        );

        $indexCandidate = $this->_createIndex($constraint->getColumns(), $indexName, false, false);

        foreach ($this->_indexes as $existingIndex) {
            if ($indexCandidate->isFulfilledBy($existingIndex)) {
                return $this;
            }
        }

        $this->_addIndex($indexCandidate);
        $this->implicitIndexes[$this->normalizeIdentifier($indexName)] = $indexCandidate;

        return $this;
    }

    /**
     * Returns whether this table has a foreign key constraint with the given name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasForeignKey($name)
    {
        $name = $this->normalizeIdentifier($name);

        return isset($this->_fkConstraints[$name]);
    }

    /**
     * Returns the foreign key constraint with the given name.
     *
     * @param string $name The constraint name.
     *
     * @return ForeignKeyConstraint
     *
     * @throws SchemaException If the foreign key does not exist.
     */
    public function getForeignKey($name)
    {
        $name = $this->normalizeIdentifier($name);

        if (! $this->hasForeignKey($name)) {
            throw SchemaException::foreignKeyDoesNotExist($name, $this->_name);
        }

        return $this->_fkConstraints[$name];
    }

    /**
     * Removes the foreign key constraint with the given name.
     *
     * @param string $name The constraint name.
     *
     * @return void
     *
     * @throws SchemaException
     */
    public function removeForeignKey($name)
    {
        $name = $this->normalizeIdentifier($name);

        if (! $this->hasForeignKey($name)) {
            throw SchemaException::foreignKeyDoesNotExist($name, $this->_name);
        }

        unset($this->_fkConstraints[$name]);
    }

    /**
     * Returns whether this table has a unique constraint with the given name.
     */
    public function hasUniqueConstraint(string $name): bool
    {
        $name = $this->normalizeIdentifier($name);

        return isset($this->uniqueConstraints[$name]);
    }

    /**
     * Returns the unique constraint with the given name.
     *
     * @throws SchemaException If the unique constraint does not exist.
     */
    public function getUniqueConstraint(string $name): UniqueConstraint
    {
        $name = $this->normalizeIdentifier($name);

        if (! $this->hasUniqueConstraint($name)) {
            throw SchemaException::uniqueConstraintDoesNotExist($name, $this->_name);
        }

        return $this->uniqueConstraints[$name];
    }

    /**
     * Removes the unique constraint with the given name.
     *
     * @throws SchemaException If the unique constraint does not exist.
     */
    public function removeUniqueConstraint(string $name): void
    {
        $name = $this->normalizeIdentifier($name);

        if (! $this->hasUniqueConstraint($name)) {
            throw SchemaException::uniqueConstraintDoesNotExist($name, $this->_name);
        }

        unset($this->uniqueConstraints[$name]);
    }

    /**
     * Returns ordered list of columns (primary keys are first, then foreign keys, then the rest)
     *
     * @return Column[]
     */
    public function getColumns()
    {
        $primaryKeyColumns = $this->getPrimaryKey() !== null ? $this->getPrimaryKeyColumns() : [];
        $foreignKeyColumns = $this->getForeignKeyColumns();
        $remainderColumns  = $this->filterColumns(
            array_merge(array_keys($primaryKeyColumns), array_keys($foreignKeyColumns)),
            true,
        );

        return array_merge($primaryKeyColumns, $foreignKeyColumns, $remainderColumns);
    }

    /**
     * Returns the foreign key columns
     *
     * @deprecated Use {@see getForeignKey()} and {@see ForeignKeyConstraint::getLocalColumns()} instead.
     *
     * @return Column[]
     */
    public function getForeignKeyColumns()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5731',
            '%s is deprecated. Use getForeignKey() and ForeignKeyConstraint::getLocalColumns() instead.',
            __METHOD__,
        );

        $foreignKeyColumns = [];

        foreach ($this->getForeignKeys() as $foreignKey) {
            $foreignKeyColumns = array_merge($foreignKeyColumns, $foreignKey->getLocalColumns());
        }

        return $this->filterColumns($foreignKeyColumns);
    }

    /**
     * Returns only columns that have specified names
     *
     * @param string[] $columnNames
     *
     * @return Column[]
     */
    private function filterColumns(array $columnNames, bool $reverse = false): array
    {
        return array_filter($this->_columns, static function (string $columnName) use ($columnNames, $reverse): bool {
            return in_array($columnName, $columnNames, true) !== $reverse;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Returns whether this table has a Column with the given name.
     *
     * @param string $name The column name.
     *
     * @return bool
     */
    public function hasColumn($name)
    {
        $name = $this->normalizeIdentifier($name);

        return isset($this->_columns[$name]);
    }

    /**
     * Returns the Column with the given name.
     *
     * @param string $name The column name.
     *
     * @return Column
     *
     * @throws SchemaException If the column does not exist.
     */
    public function getColumn($name)
    {
        $name = $this->normalizeIdentifier($name);

        if (! $this->hasColumn($name)) {
            throw SchemaException::columnDoesNotExist($name, $this->_name);
        }

        return $this->_columns[$name];
    }

    /**
     * Returns the primary key.
     *
     * @return Index|null The primary key, or null if this Table has no primary key.
     */
    public function getPrimaryKey()
    {
        if ($this->_primaryKeyName !== null) {
            return $this->getIndex($this->_primaryKeyName);
        }

        return null;
    }

    /**
     * Returns the primary key columns.
     *
     * @deprecated Use {@see getPrimaryKey()} and {@see Index::getColumns()} instead.
     *
     * @return Column[]
     *
     * @throws Exception
     */
    public function getPrimaryKeyColumns()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5731',
            '%s is deprecated. Use getPrimaryKey() and Index::getColumns() instead.',
            __METHOD__,
        );

        $primaryKey = $this->getPrimaryKey();

        if ($primaryKey === null) {
            throw new Exception('Table ' . $this->getName() . ' has no primary key.');
        }

        return $this->filterColumns($primaryKey->getColumns());
    }

    /**
     * Returns whether this table has a primary key.
     *
     * @deprecated Use {@see getPrimaryKey()} instead.
     *
     * @return bool
     */
    public function hasPrimaryKey()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5731',
            '%s is deprecated. Use getPrimaryKey() instead.',
            __METHOD__,
        );

        return $this->_primaryKeyName !== null && $this->hasIndex($this->_primaryKeyName);
    }

    /**
     * Returns whether this table has an Index with the given name.
     *
     * @param string $name The index name.
     *
     * @return bool
     */
    public function hasIndex($name)
    {
        $name = $this->normalizeIdentifier($name);

        return isset($this->_indexes[$name]);
    }

    /**
     * Returns the Index with the given name.
     *
     * @param string $name The index name.
     *
     * @return Index
     *
     * @throws SchemaException If the index does not exist.
     */
    public function getIndex($name)
    {
        $name = $this->normalizeIdentifier($name);
        if (! $this->hasIndex($name)) {
            throw SchemaException::indexDoesNotExist($name, $this->_name);
        }

        return $this->_indexes[$name];
    }

    /** @return Index[] */
    public function getIndexes()
    {
        return $this->_indexes;
    }

    /**
     * Returns the unique constraints.
     *
     * @return UniqueConstraint[]
     */
    public function getUniqueConstraints(): array
    {
        return $this->uniqueConstraints;
    }

    /**
     * Returns the foreign key constraints.
     *
     * @return ForeignKeyConstraint[]
     */
    public function getForeignKeys()
    {
        return $this->_fkConstraints;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name)
    {
        return isset($this->_options[$name]);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        return $this->_options[$name];
    }

    /** @return mixed[] */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @deprecated
     *
     * @return void
     *
     * @throws SchemaException
     */
    public function visit(Visitor $visitor)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5435',
            'Table::visit() is deprecated.',
        );

        $visitor->acceptTable($this);

        foreach ($this->getColumns() as $column) {
            $visitor->acceptColumn($this, $column);
        }

        foreach ($this->getIndexes() as $index) {
            $visitor->acceptIndex($this, $index);
        }

        foreach ($this->getForeignKeys() as $constraint) {
            $visitor->acceptForeignKey($this, $constraint);
        }
    }

    /**
     * Clone of a Table triggers a deep clone of all affected assets.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->_columns as $k => $column) {
            $this->_columns[$k] = clone $column;
        }

        foreach ($this->_indexes as $k => $index) {
            $this->_indexes[$k] = clone $index;
        }

        foreach ($this->_fkConstraints as $k => $fk) {
            $this->_fkConstraints[$k] = clone $fk;
            $this->_fkConstraints[$k]->setLocalTable($this);
        }
    }

    /**
     * @param string[] $columnNames
     * @param string[] $flags
     * @param mixed[]  $options
     *
     * @throws SchemaException
     */
    private function _createUniqueConstraint(
        array $columnNames,
        string $indexName,
        array $flags = [],
        array $options = []
    ): UniqueConstraint {
        if (preg_match('(([^a-zA-Z0-9_]+))', $this->normalizeIdentifier($indexName)) === 1) {
            throw SchemaException::indexNameInvalid($indexName);
        }

        foreach ($columnNames as $columnName) {
            if (! $this->hasColumn($columnName)) {
                throw SchemaException::columnDoesNotExist($columnName, $this->_name);
            }
        }

        return new UniqueConstraint($indexName, $columnNames, $flags, $options);
    }

    /**
     * Normalizes a given identifier.
     *
     * Trims quotes and lowercases the given identifier.
     *
     * @return string The normalized identifier.
     */
    private function normalizeIdentifier(?string $identifier): string
    {
        if ($identifier === null) {
            return '';
        }

        return $this->trimQuotes(strtolower($identifier));
    }

    public function setComment(?string $comment): self
    {
        // For keeping backward compatibility with MySQL in previous releases, table comments are stored as options.
        $this->addOption('comment', $comment);

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->_options['comment'] ?? null;
    }
}
