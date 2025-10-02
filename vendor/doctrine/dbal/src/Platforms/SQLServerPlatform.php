<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\InvalidLockMode;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\SQLServer\SQL\Builder\SQLServerSelectSQLBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\SQLServerSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\SQL\Builder\SelectSQLBuilder;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;
use InvalidArgumentException;

use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function crc32;
use function dechex;
use function explode;
use function func_get_arg;
use function func_get_args;
use function func_num_args;
use function implode;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function preg_match;
use function preg_match_all;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strpos;
use function strtoupper;
use function substr;
use function substr_count;

use const PREG_OFFSET_CAPTURE;

/**
 * Provides the behavior, features and SQL dialect of the Microsoft SQL Server database platform
 * of the oldest supported version.
 */
class SQLServerPlatform extends AbstractPlatform
{
    public function createSelectSQLBuilder(): SelectSQLBuilder
    {
        return new SQLServerSelectSQLBuilder($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentDateSQL()
    {
        return $this->getConvertExpression('date', 'GETDATE()');
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentTimeSQL()
    {
        return $this->getConvertExpression('time', 'GETDATE()');
    }

    /**
     * Returns an expression that converts an expression of one data type to another.
     *
     * @param string $dataType   The target native data type. Alias data types cannot be used.
     * @param string $expression The SQL expression to convert.
     */
    private function getConvertExpression($dataType, $expression): string
    {
        return sprintf('CONVERT(%s, %s)', $dataType, $expression);
    }

    /**
     * {@inheritDoc}
     */
    protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit)
    {
        $factorClause = '';

        if ($operator === '-') {
            $factorClause = '-1 * ';
        }

        return 'DATEADD(' . $unit . ', ' . $factorClause . $interval . ', ' . $date . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateDiffExpression($date1, $date2)
    {
        return 'DATEDIFF(day, ' . $date2 . ',' . $date1 . ')';
    }

    /**
     * {@inheritDoc}
     *
     * Microsoft SQL Server prefers "autoincrement" identity columns
     * since sequences can only be emulated with a table.
     *
     * @deprecated
     */
    public function prefersIdentityColumns()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/1519',
            'SQLServerPlatform::prefersIdentityColumns() is deprecated.',
        );

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * Microsoft SQL Server supports this through AUTO_INCREMENT columns.
     */
    public function supportsIdentityColumns()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsReleaseSavepoints()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsSchemas()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function getDefaultSchemaName()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5513',
            '%s is deprecated.',
            __METHOD__,
        );

        return 'dbo';
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function supportsColumnCollation()
    {
        return true;
    }

    public function supportsSequences(): bool
    {
        return true;
    }

    public function getAlterSequenceSQL(Sequence $sequence): string
    {
        return 'ALTER SEQUENCE ' . $sequence->getQuotedName($this) .
            ' INCREMENT BY ' . $sequence->getAllocationSize();
    }

    public function getCreateSequenceSQL(Sequence $sequence): string
    {
        return 'CREATE SEQUENCE ' . $sequence->getQuotedName($this) .
            ' START WITH ' . $sequence->getInitialValue() .
            ' INCREMENT BY ' . $sequence->getAllocationSize() .
            ' MINVALUE ' . $sequence->getInitialValue();
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     */
    public function getListSequencesSQL($database)
    {
        return 'SELECT seq.name,
                       CAST(
                           seq.increment AS VARCHAR(MAX)
                       ) AS increment, -- CAST avoids driver error for sql_variant type
                       CAST(
                           seq.start_value AS VARCHAR(MAX)
                       ) AS start_value -- CAST avoids driver error for sql_variant type
                FROM   sys.sequences AS seq';
    }

    /**
     * {@inheritDoc}
     */
    public function getSequenceNextValSQL($sequence)
    {
        return 'SELECT NEXT VALUE FOR ' . $sequence;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function hasNativeGuidType()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5509',
            '%s is deprecated.',
            __METHOD__,
        );

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getDropForeignKeySQL($foreignKey, $table)
    {
        if (! $foreignKey instanceof ForeignKeyConstraint) {
            $foreignKey = new Identifier($foreignKey);
        }

        if (! $table instanceof Table) {
            $table = new Identifier($table);
        }

        $foreignKey = $foreignKey->getQuotedName($this);
        $table      = $table->getQuotedName($this);

        return 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $foreignKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getDropIndexSQL($index, $table = null)
    {
        if ($index instanceof Index) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $index as an Index object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $index = $index->getQuotedName($this);
        } elseif (! is_string($index)) {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $index parameter to be string or ' . Index::class . '.',
            );
        }

        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as an Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        } elseif (! is_string($table)) {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $table parameter to be string or ' . Table::class . '.',
            );
        }

        return 'DROP INDEX ' . $index . ' ON ' . $table;
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCreateTableSQL($name, array $columns, array $options = [])
    {
        $defaultConstraintsSql = [];
        $commentsSql           = [];

        $tableComment = $options['comment'] ?? null;
        if ($tableComment !== null) {
            $commentsSql[] = $this->getCommentOnTableSQL($name, $tableComment);
        }

        // @todo does other code breaks because of this?
        // force primary keys to be not null
        foreach ($columns as &$column) {
            if (! empty($column['primary'])) {
                $column['notnull'] = true;
            }

            // Build default constraints SQL statements.
            if (isset($column['default'])) {
                $defaultConstraintsSql[] = 'ALTER TABLE ' . $name .
                    ' ADD' . $this->getDefaultConstraintDeclarationSQL($name, $column);
            }

            if (empty($column['comment']) && ! is_numeric($column['comment'])) {
                continue;
            }

            $commentsSql[] = $this->getCreateColumnCommentSQL($name, $column['name'], $column['comment']);
        }

        $columnListSql = $this->getColumnDeclarationListSQL($columns);

        if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $constraintName => $definition) {
                $columnListSql .= ', ' . $this->getUniqueConstraintDeclarationSQL($constraintName, $definition);
            }
        }

        if (isset($options['primary']) && ! empty($options['primary'])) {
            $flags = '';
            if (isset($options['primary_index']) && $options['primary_index']->hasFlag('nonclustered')) {
                $flags = ' NONCLUSTERED';
            }

            $columnListSql .= ', PRIMARY KEY' . $flags
                . ' (' . implode(', ', array_unique(array_values($options['primary']))) . ')';
        }

        $query = 'CREATE TABLE ' . $name . ' (' . $columnListSql;

        $check = $this->getCheckDeclarationSQL($columns);
        if (! empty($check)) {
            $query .= ', ' . $check;
        }

        $query .= ')';

        $sql = [$query];

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] as $index) {
                $sql[] = $this->getCreateIndexSQL($index, $name);
            }
        }

        if (isset($options['foreignKeys'])) {
            foreach ($options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $name);
            }
        }

        return array_merge($sql, $commentsSql, $defaultConstraintsSql);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatePrimaryKeySQL(Index $index, $table)
    {
        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $identifier = $table->getQuotedName($this);
        } else {
            $identifier = $table;
        }

        $sql = 'ALTER TABLE ' . $identifier . ' ADD PRIMARY KEY';

        if ($index->hasFlag('nonclustered')) {
            $sql .= ' NONCLUSTERED';
        }

        return $sql . ' (' . $this->getIndexFieldDeclarationListSQL($index) . ')';
    }

    private function unquoteSingleIdentifier(string $possiblyQuotedName): string
    {
        return str_starts_with($possiblyQuotedName, '[') && str_ends_with($possiblyQuotedName, ']')
            ? substr($possiblyQuotedName, 1, -1)
            : $possiblyQuotedName;
    }

    /**
     * Returns the SQL statement for creating a column comment.
     *
     * SQL Server does not support native column comments,
     * therefore the extended properties functionality is used
     * as a workaround to store them.
     * The property name used to store column comments is "MS_Description"
     * which provides compatibility with SQL Server Management Studio,
     * as column comments are stored in the same property there when
     * specifying a column's "Description" attribute.
     *
     * @param string      $tableName  The quoted table name to which the column belongs.
     * @param string      $columnName The quoted column name to create the comment for.
     * @param string|null $comment    The column's comment.
     *
     * @return string
     */
    protected function getCreateColumnCommentSQL($tableName, $columnName, $comment)
    {
        if (strpos($tableName, '.') !== false) {
            [$schemaName, $tableName] = explode('.', $tableName);
        } else {
            $schemaName = 'dbo';
        }

        return $this->getAddExtendedPropertySQL(
            'MS_Description',
            $comment,
            'SCHEMA',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($schemaName)),
            'TABLE',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($tableName)),
            'COLUMN',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($columnName)),
        );
    }

    /**
     * Returns the SQL snippet for declaring a default constraint.
     *
     * @internal The method should be only used from within the SQLServerPlatform class hierarchy.
     *
     * @param string  $table  Name of the table to return the default constraint declaration for.
     * @param mixed[] $column Column definition.
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getDefaultConstraintDeclarationSQL($table, array $column)
    {
        if (! isset($column['default'])) {
            throw new InvalidArgumentException("Incomplete column definition. 'default' required.");
        }

        $columnName = new Identifier($column['name']);

        return ' CONSTRAINT ' .
            $this->generateDefaultConstraintName($table, $column['name']) .
            $this->getDefaultValueDeclarationSQL($column) .
            ' FOR ' . $columnName->getQuotedName($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateIndexSQL(Index $index, $table)
    {
        $constraint = parent::getCreateIndexSQL($index, $table);

        if ($index->isUnique() && ! $index->isPrimary()) {
            $constraint = $this->_appendUniqueConstraintDefinition($constraint, $index);
        }

        return $constraint;
    }

    /**
     * {@inheritDoc}
     */
    protected function getCreateIndexSQLFlags(Index $index)
    {
        $type = '';
        if ($index->isUnique()) {
            $type .= 'UNIQUE ';
        }

        if ($index->hasFlag('clustered')) {
            $type .= 'CLUSTERED ';
        } elseif ($index->hasFlag('nonclustered')) {
            $type .= 'NONCLUSTERED ';
        }

        return $type;
    }

    /**
     * Extend unique key constraint with required filters
     *
     * @param string $sql
     */
    private function _appendUniqueConstraintDefinition($sql, Index $index): string
    {
        $fields = [];

        foreach ($index->getQuotedColumns($this) as $field) {
            $fields[] = $field . ' IS NOT NULL';
        }

        return $sql . ' WHERE ' . implode(' AND ', $fields);
    }

    /**
     * {@inheritDoc}
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        $queryParts  = [];
        $sql         = [];
        $columnSql   = [];
        $commentsSql = [];

        $table = $diff->getOldTable() ?? $diff->getName($this);

        $tableName = $table->getName();

        foreach ($diff->getAddedColumns() as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnProperties = $column->toArray();

            $addColumnSql = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnProperties);

            if (isset($columnProperties['default'])) {
                $addColumnSql .= ' CONSTRAINT ' . $this->generateDefaultConstraintName(
                    $tableName,
                    $column->getQuotedName($this),
                ) . $this->getDefaultValueDeclarationSQL($columnProperties);
            }

            $queryParts[] = $addColumnSql;

            $comment = $this->getColumnComment($column);

            if (empty($comment) && ! is_numeric($comment)) {
                continue;
            }

            $commentsSql[] = $this->getCreateColumnCommentSQL(
                $tableName,
                $column->getQuotedName($this),
                $comment,
            );
        }

        foreach ($diff->getDroppedColumns() as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            $queryParts[] = 'DROP COLUMN ' . $column->getQuotedName($this);
        }

        foreach ($diff->getModifiedColumns() as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            $newColumn     = $columnDiff->getNewColumn();
            $newComment    = $this->getColumnComment($newColumn);
            $hasNewComment = ! empty($newComment) || is_numeric($newComment);

            $oldColumn = $columnDiff->getOldColumn();

            if ($oldColumn instanceof Column) {
                $oldComment    = $this->getColumnComment($oldColumn);
                $hasOldComment = ! empty($oldComment) || is_numeric($oldComment);

                if ($hasOldComment && $hasNewComment && $oldComment !== $newComment) {
                    $commentsSql[] = $this->getAlterColumnCommentSQL(
                        $tableName,
                        $newColumn->getQuotedName($this),
                        $newComment,
                    );
                } elseif ($hasOldComment && ! $hasNewComment) {
                    $commentsSql[] = $this->getDropColumnCommentSQL(
                        $tableName,
                        $newColumn->getQuotedName($this),
                    );
                } elseif (! $hasOldComment && $hasNewComment) {
                    $commentsSql[] = $this->getCreateColumnCommentSQL(
                        $tableName,
                        $newColumn->getQuotedName($this),
                        $newComment,
                    );
                }
            }

            // Do not add query part if only comment has changed.
            if ($columnDiff->hasCommentChanged() && count($columnDiff->changedProperties) === 1) {
                continue;
            }

            $requireDropDefaultConstraint = $this->alterColumnRequiresDropDefaultConstraint($columnDiff);

            if ($requireDropDefaultConstraint) {
                $oldColumn = $columnDiff->getOldColumn();

                if ($oldColumn !== null) {
                    $oldColumnName = $oldColumn->getName();
                } else {
                    $oldColumnName = $columnDiff->oldColumnName;
                }

                $queryParts[] = $this->getAlterTableDropDefaultConstraintClause($tableName, $oldColumnName);
            }

            $columnProperties = $newColumn->toArray();

            $queryParts[] = 'ALTER COLUMN ' .
                    $this->getColumnDeclarationSQL($newColumn->getQuotedName($this), $columnProperties);

            if (
                ! isset($columnProperties['default'])
                || (! $requireDropDefaultConstraint && ! $columnDiff->hasDefaultChanged())
            ) {
                continue;
            }

            $queryParts[] = $this->getAlterTableAddDefaultConstraintClause($tableName, $newColumn);
        }

        $tableNameSQL = $table->getQuotedName($this);

        foreach ($diff->getRenamedColumns() as $oldColumnName => $newColumn) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $newColumn, $diff, $columnSql)) {
                continue;
            }

            $oldColumnName = new Identifier($oldColumnName);

            $sql[] = "sp_rename '" . $tableNameSQL . '.' . $oldColumnName->getQuotedName($this) .
                "', '" . $newColumn->getQuotedName($this) . "', 'COLUMN'";

            // Recreate default constraint with new column name if necessary (for future reference).
            if ($newColumn->getDefault() === null) {
                continue;
            }

            $queryParts[] = $this->getAlterTableDropDefaultConstraintClause(
                $tableName,
                $oldColumnName->getQuotedName($this),
            );
            $queryParts[] = $this->getAlterTableAddDefaultConstraintClause($tableName, $newColumn);
        }

        $tableSql = [];

        if ($this->onSchemaAlterTable($diff, $tableSql)) {
            return array_merge($tableSql, $columnSql);
        }

        foreach ($queryParts as $query) {
            $sql[] = 'ALTER TABLE ' . $tableNameSQL . ' ' . $query;
        }

        $sql = array_merge($sql, $commentsSql);

        $newName = $diff->getNewName();

        if ($newName !== false) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5663',
                'Generation of "rename table" SQL using %s is deprecated. Use getRenameTableSQL() instead.',
                __METHOD__,
            );

            $sql = array_merge($sql, $this->getRenameTableSQL($tableName, $newName->getName()));
        }

        $sql = array_merge(
            $this->getPreAlterTableIndexForeignKeySQL($diff),
            $sql,
            $this->getPostAlterTableIndexForeignKeySQL($diff),
        );

        return array_merge($sql, $tableSql, $columnSql);
    }

    /**
     * {@inheritDoc}
     */
    public function getRenameTableSQL(string $oldName, string $newName): array
    {
        return [
            sprintf('sp_rename %s, %s', $this->quoteStringLiteral($oldName), $this->quoteStringLiteral($newName)),

            /* Rename table's default constraints names
             * to match the new table name.
             * This is necessary to ensure that the default
             * constraints can be referenced in future table
             * alterations as the table name is encoded in
             * default constraints' names. */
            sprintf(
                <<<'SQL'
                DECLARE @sql NVARCHAR(MAX) = N'';
                SELECT @sql += N'EXEC sp_rename N''' + dc.name + ''', N'''
                    + REPLACE(dc.name, '%s', '%s') + ''', ''OBJECT'';'
                    FROM sys.default_constraints dc
                    JOIN sys.tables tbl
                        ON dc.parent_object_id = tbl.object_id
                    WHERE tbl.name = %s;
                EXEC sp_executesql @sql
                SQL,
                $this->generateIdentifierName($oldName),
                $this->generateIdentifierName($newName),
                $this->quoteStringLiteral($newName),
            ),
        ];
    }

    /**
     * Returns the SQL clause for adding a default constraint in an ALTER TABLE statement.
     *
     * @param string $tableName The name of the table to generate the clause for.
     * @param Column $column    The column to generate the clause for.
     */
    private function getAlterTableAddDefaultConstraintClause($tableName, Column $column): string
    {
        $columnDef         = $column->toArray();
        $columnDef['name'] = $column->getQuotedName($this);

        return 'ADD' . $this->getDefaultConstraintDeclarationSQL($tableName, $columnDef);
    }

    /**
     * Returns the SQL clause for dropping an existing default constraint in an ALTER TABLE statement.
     *
     * @param string $tableName  The name of the table to generate the clause for.
     * @param string $columnName The name of the column to generate the clause for.
     */
    private function getAlterTableDropDefaultConstraintClause($tableName, $columnName): string
    {
        return 'DROP CONSTRAINT ' . $this->generateDefaultConstraintName($tableName, $columnName);
    }

    /**
     * Checks whether a column alteration requires dropping its default constraint first.
     *
     * Different to other database vendors SQL Server implements column default values
     * as constraints and therefore changes in a column's default value as well as changes
     * in a column's type require dropping the default constraint first before being to
     * alter the particular column to the new definition.
     */
    private function alterColumnRequiresDropDefaultConstraint(ColumnDiff $columnDiff): bool
    {
        $oldColumn = $columnDiff->getOldColumn();

        // We can only decide whether to drop an existing default constraint
        // if we know the original default value.
        if (! $oldColumn instanceof Column) {
            return false;
        }

        // We only need to drop an existing default constraint if we know the
        // column was defined with a default value before.
        if ($oldColumn->getDefault() === null) {
            return false;
        }

        // We need to drop an existing default constraint if the column was
        // defined with a default value before and it has changed.
        if ($columnDiff->hasDefaultChanged()) {
            return true;
        }

        // We need to drop an existing default constraint if the column was
        // defined with a default value before and the native column type has changed.
        return $columnDiff->hasTypeChanged() || $columnDiff->hasFixedChanged();
    }

    /**
     * Returns the SQL statement for altering a column comment.
     *
     * SQL Server does not support native column comments,
     * therefore the extended properties functionality is used
     * as a workaround to store them.
     * The property name used to store column comments is "MS_Description"
     * which provides compatibility with SQL Server Management Studio,
     * as column comments are stored in the same property there when
     * specifying a column's "Description" attribute.
     *
     * @param string      $tableName  The quoted table name to which the column belongs.
     * @param string      $columnName The quoted column name to alter the comment for.
     * @param string|null $comment    The column's comment.
     *
     * @return string
     */
    protected function getAlterColumnCommentSQL($tableName, $columnName, $comment)
    {
        if (strpos($tableName, '.') !== false) {
            [$schemaName, $tableName] = explode('.', $tableName);
        } else {
            $schemaName = 'dbo';
        }

        return $this->getUpdateExtendedPropertySQL(
            'MS_Description',
            $comment,
            'SCHEMA',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($schemaName)),
            'TABLE',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($tableName)),
            'COLUMN',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($columnName)),
        );
    }

    /**
     * Returns the SQL statement for dropping a column comment.
     *
     * SQL Server does not support native column comments,
     * therefore the extended properties functionality is used
     * as a workaround to store them.
     * The property name used to store column comments is "MS_Description"
     * which provides compatibility with SQL Server Management Studio,
     * as column comments are stored in the same property there when
     * specifying a column's "Description" attribute.
     *
     * @param string $tableName  The quoted table name to which the column belongs.
     * @param string $columnName The quoted column name to drop the comment for.
     *
     * @return string
     */
    protected function getDropColumnCommentSQL($tableName, $columnName)
    {
        if (strpos($tableName, '.') !== false) {
            [$schemaName, $tableName] = explode('.', $tableName);
        } else {
            $schemaName = 'dbo';
        }

        return $this->getDropExtendedPropertySQL(
            'MS_Description',
            'SCHEMA',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($schemaName)),
            'TABLE',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($tableName)),
            'COLUMN',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($columnName)),
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName)
    {
        return [sprintf(
            "EXEC sp_rename N'%s.%s', N'%s', N'INDEX'",
            $tableName,
            $oldIndexName,
            $index->getQuotedName($this),
        ),
        ];
    }

    /**
     * Returns the SQL statement for adding an extended property to a database object.
     *
     * @internal The method should be only used from within the SQLServerPlatform class hierarchy.
     *
     * @link http://msdn.microsoft.com/en-us/library/ms180047%28v=sql.90%29.aspx
     *
     * @param string      $name       The name of the property to add.
     * @param string|null $value      The value of the property to add.
     * @param string|null $level0Type The type of the object at level 0 the property belongs to.
     * @param string|null $level0Name The name of the object at level 0 the property belongs to.
     * @param string|null $level1Type The type of the object at level 1 the property belongs to.
     * @param string|null $level1Name The name of the object at level 1 the property belongs to.
     * @param string|null $level2Type The type of the object at level 2 the property belongs to.
     * @param string|null $level2Name The name of the object at level 2 the property belongs to.
     *
     * @return string
     */
    public function getAddExtendedPropertySQL(
        $name,
        $value = null,
        $level0Type = null,
        $level0Name = null,
        $level1Type = null,
        $level1Name = null,
        $level2Type = null,
        $level2Name = null
    ) {
        return 'EXEC sp_addextendedproperty ' .
            'N' . $this->quoteStringLiteral($name) . ', N' . $this->quoteStringLiteral($value ?? '') . ', ' .
            'N' . $this->quoteStringLiteral($level0Type ?? '') . ', ' . $level0Name . ', ' .
            'N' . $this->quoteStringLiteral($level1Type ?? '') . ', ' . $level1Name .
            ($level2Type !== null || $level2Name !== null
                ? ', N' . $this->quoteStringLiteral($level2Type ?? '') . ', ' . $level2Name
                : ''
            );
    }

    /**
     * Returns the SQL statement for dropping an extended property from a database object.
     *
     * @internal The method should be only used from within the SQLServerPlatform class hierarchy.
     *
     * @link http://technet.microsoft.com/en-gb/library/ms178595%28v=sql.90%29.aspx
     *
     * @param string      $name       The name of the property to drop.
     * @param string|null $level0Type The type of the object at level 0 the property belongs to.
     * @param string|null $level0Name The name of the object at level 0 the property belongs to.
     * @param string|null $level1Type The type of the object at level 1 the property belongs to.
     * @param string|null $level1Name The name of the object at level 1 the property belongs to.
     * @param string|null $level2Type The type of the object at level 2 the property belongs to.
     * @param string|null $level2Name The name of the object at level 2 the property belongs to.
     *
     * @return string
     */
    public function getDropExtendedPropertySQL(
        $name,
        $level0Type = null,
        $level0Name = null,
        $level1Type = null,
        $level1Name = null,
        $level2Type = null,
        $level2Name = null
    ) {
        return 'EXEC sp_dropextendedproperty ' .
            'N' . $this->quoteStringLiteral($name) . ', ' .
            'N' . $this->quoteStringLiteral($level0Type ?? '') . ', ' . $level0Name . ', ' .
            'N' . $this->quoteStringLiteral($level1Type ?? '') . ', ' . $level1Name .
            ($level2Type !== null || $level2Name !== null
                ? ', N' . $this->quoteStringLiteral($level2Type ?? '') . ', ' . $level2Name
                : ''
            );
    }

    /**
     * Returns the SQL statement for updating an extended property of a database object.
     *
     * @internal The method should be only used from within the SQLServerPlatform class hierarchy.
     *
     * @link http://msdn.microsoft.com/en-us/library/ms186885%28v=sql.90%29.aspx
     *
     * @param string      $name       The name of the property to update.
     * @param string|null $value      The value of the property to update.
     * @param string|null $level0Type The type of the object at level 0 the property belongs to.
     * @param string|null $level0Name The name of the object at level 0 the property belongs to.
     * @param string|null $level1Type The type of the object at level 1 the property belongs to.
     * @param string|null $level1Name The name of the object at level 1 the property belongs to.
     * @param string|null $level2Type The type of the object at level 2 the property belongs to.
     * @param string|null $level2Name The name of the object at level 2 the property belongs to.
     *
     * @return string
     */
    public function getUpdateExtendedPropertySQL(
        $name,
        $value = null,
        $level0Type = null,
        $level0Name = null,
        $level1Type = null,
        $level1Name = null,
        $level2Type = null,
        $level2Name = null
    ) {
        return 'EXEC sp_updateextendedproperty ' .
            'N' . $this->quoteStringLiteral($name) . ', N' . $this->quoteStringLiteral($value ?? '') . ', ' .
            'N' . $this->quoteStringLiteral($level0Type ?? '') . ', ' . $level0Name . ', ' .
            'N' . $this->quoteStringLiteral($level1Type ?? '') . ', ' . $level1Name .
            ($level2Type !== null || $level2Name !== null
                ? ', N' . $this->quoteStringLiteral($level2Type ?? '') . ', ' . $level2Name
                : ''
            );
    }

    /**
     * {@inheritDoc}
     */
    public function getEmptyIdentityInsertSQL($quotedTableName, $quotedIdentifierColumnName)
    {
        return 'INSERT INTO ' . $quotedTableName . ' DEFAULT VALUES';
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTablesSQL()
    {
        // "sysdiagrams" table must be ignored as it's internal SQL Server table for Database Diagrams
        // Category 2 must be ignored as it is "MS SQL Server 'pseudo-system' object[s]" for replication
        return 'SELECT name, SCHEMA_NAME (uid) AS schema_name FROM sysobjects'
            . " WHERE type = 'U' AND name != 'sysdiagrams' AND category != 2 ORDER BY name";
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        return "SELECT    col.name,
                          type.name AS type,
                          col.max_length AS length,
                          ~col.is_nullable AS notnull,
                          def.definition AS [default],
                          col.scale,
                          col.precision,
                          col.is_identity AS autoincrement,
                          col.collation_name AS collation,
                          CAST(prop.value AS NVARCHAR(MAX)) AS comment -- CAST avoids driver error for sql_variant type
                FROM      sys.columns AS col
                JOIN      sys.types AS type
                ON        col.user_type_id = type.user_type_id
                JOIN      sys.objects AS obj
                ON        col.object_id = obj.object_id
                JOIN      sys.schemas AS scm
                ON        obj.schema_id = scm.schema_id
                LEFT JOIN sys.default_constraints def
                ON        col.default_object_id = def.object_id
                AND       col.object_id = def.parent_object_id
                LEFT JOIN sys.extended_properties AS prop
                ON        obj.object_id = prop.major_id
                AND       col.column_id = prop.minor_id
                AND       prop.name = 'MS_Description'
                WHERE     obj.type = 'U'
                AND       " . $this->getTableWhereClause($table, 'scm.name', 'obj.name');
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * @param string      $table
     * @param string|null $database
     *
     * @return string
     */
    public function getListTableForeignKeysSQL($table, $database = null)
    {
        return 'SELECT f.name AS ForeignKey,
                SCHEMA_NAME (f.SCHEMA_ID) AS SchemaName,
                OBJECT_NAME (f.parent_object_id) AS TableName,
                COL_NAME (fc.parent_object_id,fc.parent_column_id) AS ColumnName,
                SCHEMA_NAME (o.SCHEMA_ID) ReferenceSchemaName,
                OBJECT_NAME (f.referenced_object_id) AS ReferenceTableName,
                COL_NAME(fc.referenced_object_id,fc.referenced_column_id) AS ReferenceColumnName,
                f.delete_referential_action_desc,
                f.update_referential_action_desc
                FROM sys.foreign_keys AS f
                INNER JOIN sys.foreign_key_columns AS fc
                INNER JOIN sys.objects AS o ON o.OBJECT_ID = fc.referenced_object_id
                ON f.OBJECT_ID = fc.constraint_object_id
                WHERE ' .
                $this->getTableWhereClause($table, 'SCHEMA_NAME (f.schema_id)', 'OBJECT_NAME (f.parent_object_id)') .
                ' ORDER BY fc.constraint_column_id';
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        return "SELECT idx.name AS key_name,
                       col.name AS column_name,
                       ~idx.is_unique AS non_unique,
                       idx.is_primary_key AS [primary],
                       CASE idx.type
                           WHEN '1' THEN 'clustered'
                           WHEN '2' THEN 'nonclustered'
                           ELSE NULL
                       END AS flags
                FROM sys.tables AS tbl
                JOIN sys.schemas AS scm ON tbl.schema_id = scm.schema_id
                JOIN sys.indexes AS idx ON tbl.object_id = idx.object_id
                JOIN sys.index_columns AS idxcol ON idx.object_id = idxcol.object_id AND idx.index_id = idxcol.index_id
                JOIN sys.columns AS col ON idxcol.object_id = col.object_id AND idxcol.column_id = col.column_id
                WHERE " . $this->getTableWhereClause($table, 'scm.name', 'tbl.name') . '
                ORDER BY idx.index_id ASC, idxcol.key_ordinal ASC';
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     */
    public function getListViewsSQL($database)
    {
        return "SELECT name, definition FROM sysobjects
                    INNER JOIN sys.sql_modules ON sysobjects.id = sys.sql_modules.object_id
                WHERE type = 'V' ORDER BY name";
    }

    /**
     * Returns the where clause to filter schema and table name in a query.
     *
     * @param string $table        The full qualified name of the table.
     * @param string $schemaColumn The name of the column to compare the schema to in the where clause.
     * @param string $tableColumn  The name of the column to compare the table to in the where clause.
     */
    private function getTableWhereClause($table, $schemaColumn, $tableColumn): string
    {
        if (strpos($table, '.') !== false) {
            [$schema, $table] = explode('.', $table);
            $schema           = $this->quoteStringLiteral($schema);
            $table            = $this->quoteStringLiteral($table);
        } else {
            $schema = 'SCHEMA_NAME()';
            $table  = $this->quoteStringLiteral($table);
        }

        return sprintf('(%s = %s AND %s = %s)', $tableColumn, $table, $schemaColumn, $schema);
    }

    /**
     * {@inheritDoc}
     */
    public function getLocateExpression($str, $substr, $startPos = false)
    {
        if ($startPos === false) {
            return 'CHARINDEX(' . $substr . ', ' . $str . ')';
        }

        return 'CHARINDEX(' . $substr . ', ' . $str . ', ' . $startPos . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getModExpression($expression1, $expression2)
    {
        return $expression1 . ' % ' . $expression2;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrimExpression($str, $mode = TrimMode::UNSPECIFIED, $char = false)
    {
        if ($char === false) {
            switch ($mode) {
                case TrimMode::LEADING:
                    $trimFn = 'LTRIM';
                    break;

                case TrimMode::TRAILING:
                    $trimFn = 'RTRIM';
                    break;

                default:
                    return 'LTRIM(RTRIM(' . $str . '))';
            }

            return $trimFn . '(' . $str . ')';
        }

        $pattern = "'%[^' + " . $char . " + ']%'";

        if ($mode === TrimMode::LEADING) {
            return 'stuff(' . $str . ', 1, patindex(' . $pattern . ', ' . $str . ') - 1, null)';
        }

        if ($mode === TrimMode::TRAILING) {
            return 'reverse(stuff(reverse(' . $str . '), 1, '
                . 'patindex(' . $pattern . ', reverse(' . $str . ')) - 1, null))';
        }

        return 'reverse(stuff(reverse(stuff(' . $str . ', 1, patindex(' . $pattern . ', ' . $str . ') - 1, null)), 1, '
            . 'patindex(' . $pattern . ', reverse(stuff(' . $str . ', 1, patindex(' . $pattern . ', ' . $str
            . ') - 1, null))) - 1, null))';
    }

    /**
     * {@inheritDoc}
     */
    public function getConcatExpression()
    {
        return sprintf('CONCAT(%s)', implode(', ', func_get_args()));
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     */
    public function getListDatabasesSQL()
    {
        return 'SELECT * FROM sys.databases';
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@see SQLServerSchemaManager::listSchemaNames()} instead.
     */
    public function getListNamespacesSQL()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4503',
            'SQLServerPlatform::getListNamespacesSQL() is deprecated,'
                . ' use SQLServerSchemaManager::listSchemaNames() instead.',
        );

        return "SELECT name FROM sys.schemas WHERE name NOT IN('guest', 'INFORMATION_SCHEMA', 'sys')";
    }

    /**
     * {@inheritDoc}
     */
    public function getSubstringExpression($string, $start, $length = null)
    {
        if ($length !== null) {
            return 'SUBSTRING(' . $string . ', ' . $start . ', ' . $length . ')';
        }

        return 'SUBSTRING(' . $string . ', ' . $start . ', LEN(' . $string . ') - ' . $start . ' + 1)';
    }

    /**
     * {@inheritDoc}
     */
    public function getLengthExpression($column)
    {
        return 'LEN(' . $column . ')';
    }

    public function getCurrentDatabaseExpression(): string
    {
        return 'DB_NAME()';
    }

    /**
     * {@inheritDoc}
     */
    public function getSetTransactionIsolationSQL($level)
    {
        return 'SET TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSQL($level);
    }

    /**
     * {@inheritDoc}
     */
    public function getIntegerTypeDeclarationSQL(array $column)
    {
        return 'INT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getBigIntTypeDeclarationSQL(array $column)
    {
        return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $column)
    {
        return 'SMALLINT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getGuidTypeDeclarationSQL(array $column)
    {
        return 'UNIQUEIDENTIFIER';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTzTypeDeclarationSQL(array $column)
    {
        return 'DATETIMEOFFSET(6)';
    }

    /**
     * {@inheritDoc}
     */
    public function getAsciiStringTypeDeclarationSQL(array $column): string
    {
        $length = $column['length'] ?? null;

        if (empty($column['fixed'])) {
            return sprintf('VARCHAR(%d)', $length ?? 255);
        }

        return sprintf('CHAR(%d)', $length ?? 255);
    }

    /**
     * {@inheritDoc}
     */
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed/*, $lengthOmitted = false*/)
    {
        if ($length <= 0 || (func_num_args() > 2 && func_get_arg(2))) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/3263',
                'Relying on the default string column length on SQL Server is deprecated'
                    . ', specify the length explicitly.',
            );
        }

        return $fixed
            ? 'NCHAR(' . ($length > 0 ? $length : 255) . ')'
            : 'NVARCHAR(' . ($length > 0 ? $length : 255) . ')';
    }

    /**
     * {@inheritDoc}
     */
    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed/*, $lengthOmitted = false*/)
    {
        if ($length <= 0 || (func_num_args() > 2 && func_get_arg(2))) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/3263',
                'Relying on the default binary column length on SQL Server is deprecated'
                    . ', specify the length explicitly.',
            );
        }

        return $fixed
            ? 'BINARY(' . ($length > 0 ? $length : 255) . ')'
            : 'VARBINARY(' . ($length > 0 ? $length : 255) . ')';
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function getBinaryMaxLength()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3263',
            'SQLServerPlatform::getBinaryMaxLength() is deprecated.',
        );

        return 8000;
    }

    /**
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $column)
    {
        return 'VARCHAR(MAX)';
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $column)
    {
        return ! empty($column['autoincrement']) ? ' IDENTITY' : '';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTypeDeclarationSQL(array $column)
    {
        // 3 - microseconds precision length
        // http://msdn.microsoft.com/en-us/library/ms187819.aspx
        return 'DATETIME2(6)';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTypeDeclarationSQL(array $column)
    {
        return 'DATE';
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeTypeDeclarationSQL(array $column)
    {
        return 'TIME(0)';
    }

    /**
     * {@inheritDoc}
     */
    public function getBooleanTypeDeclarationSQL(array $column)
    {
        return 'BIT';
    }

    /**
     * {@inheritDoc}
     */
    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        if ($limit === null && $offset <= 0) {
            return $query;
        }

        if ($this->shouldAddOrderBy($query)) {
            if (preg_match('/^SELECT\s+DISTINCT/im', $query) > 0) {
                // SQL Server won't let us order by a non-selected column in a DISTINCT query,
                // so we have to do this madness. This says, order by the first column in the
                // result. SQL Server's docs say that a nonordered query's result order is non-
                // deterministic anyway, so this won't do anything that a bunch of update and
                // deletes to the table wouldn't do anyway.
                $query .= ' ORDER BY 1';
            } else {
                // In another DBMS, we could do ORDER BY 0, but SQL Server gets angry if you
                // use constant expressions in the order by list.
                $query .= ' ORDER BY (SELECT 0)';
            }
        }

        // This looks somewhat like MYSQL, but limit/offset are in inverse positions
        // Supposedly SQL:2008 core standard.
        // Per TSQL spec, FETCH NEXT n ROWS ONLY is not valid without OFFSET n ROWS.
        $query .= sprintf(' OFFSET %d ROWS', $offset);

        if ($limit !== null) {
            $query .= sprintf(' FETCH NEXT %d ROWS ONLY', $limit);
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function convertBooleans($item)
    {
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                if (! is_bool($value) && ! is_numeric($value)) {
                    continue;
                }

                $item[$key] = (int) (bool) $value;
            }
        } elseif (is_bool($item) || is_numeric($item)) {
            $item = (int) (bool) $item;
        }

        return $item;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateTemporaryTableSnippetSQL()
    {
        return 'CREATE TABLE';
    }

    /**
     * {@inheritDoc}
     */
    public function getTemporaryTableName($tableName)
    {
        return '#' . $tableName;
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormatString()
    {
        return 'Y-m-d H:i:s.u';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormatString()
    {
        return 'Y-m-d';
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeFormatString()
    {
        return 'H:i:s';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTzFormatString()
    {
        return 'Y-m-d H:i:s.u P';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'mssql';
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = [
            'bigint'           => Types::BIGINT,
            'binary'           => Types::BINARY,
            'bit'              => Types::BOOLEAN,
            'blob'             => Types::BLOB,
            'char'             => Types::STRING,
            'date'             => Types::DATE_MUTABLE,
            'datetime'         => Types::DATETIME_MUTABLE,
            'datetime2'        => Types::DATETIME_MUTABLE,
            'datetimeoffset'   => Types::DATETIMETZ_MUTABLE,
            'decimal'          => Types::DECIMAL,
            'double'           => Types::FLOAT,
            'double precision' => Types::FLOAT,
            'float'            => Types::FLOAT,
            'image'            => Types::BLOB,
            'int'              => Types::INTEGER,
            'money'            => Types::INTEGER,
            'nchar'            => Types::STRING,
            'ntext'            => Types::TEXT,
            'numeric'          => Types::DECIMAL,
            'nvarchar'         => Types::STRING,
            'real'             => Types::FLOAT,
            'smalldatetime'    => Types::DATETIME_MUTABLE,
            'smallint'         => Types::SMALLINT,
            'smallmoney'       => Types::INTEGER,
            'sysname'          => Types::STRING,
            'text'             => Types::TEXT,
            'time'             => Types::TIME_MUTABLE,
            'tinyint'          => Types::SMALLINT,
            'uniqueidentifier' => Types::GUID,
            'varbinary'        => Types::BINARY,
            'varchar'          => Types::STRING,
            'xml'              => Types::TEXT,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function createSavePoint($savepoint)
    {
        return 'SAVE TRANSACTION ' . $savepoint;
    }

    /**
     * {@inheritDoc}
     */
    public function releaseSavePoint($savepoint)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function rollbackSavePoint($savepoint)
    {
        return 'ROLLBACK TRANSACTION ' . $savepoint;
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function getForeignKeyReferentialActionSQL($action)
    {
        // RESTRICT is not supported, therefore falling back to NO ACTION.
        if (strtoupper($action) === 'RESTRICT') {
            return 'NO ACTION';
        }

        return parent::getForeignKeyReferentialActionSQL($action);
    }

    public function appendLockHint(string $fromClause, int $lockMode): string
    {
        switch ($lockMode) {
            case LockMode::NONE:
            case LockMode::OPTIMISTIC:
                return $fromClause;

            case LockMode::PESSIMISTIC_READ:
                return $fromClause . ' WITH (HOLDLOCK, ROWLOCK)';

            case LockMode::PESSIMISTIC_WRITE:
                return $fromClause . ' WITH (UPDLOCK, ROWLOCK)';

            default:
                throw InvalidLockMode::fromLockMode($lockMode);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This API is not portable.
     */
    public function getForUpdateSQL()
    {
        return ' ';
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Implement {@see createReservedKeywordsList()} instead.
     */
    protected function getReservedKeywordsClass()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4510',
            'SQLServerPlatform::getReservedKeywordsClass() is deprecated,'
                . ' use SQLServerPlatform::createReservedKeywordsList() instead.',
        );

        return Keywords\SQLServer2012Keywords::class;
    }

    /**
     * {@inheritDoc}
     */
    public function quoteSingleIdentifier($str)
    {
        return '[' . str_replace(']', ']]', $str) . ']';
    }

    /**
     * {@inheritDoc}
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        $tableIdentifier = new Identifier($tableName);

        return 'TRUNCATE TABLE ' . $tableIdentifier->getQuotedName($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlobTypeDeclarationSQL(array $column)
    {
        return 'VARBINARY(MAX)';
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function getColumnDeclarationSQL($name, array $column)
    {
        if (isset($column['columnDefinition'])) {
            $columnDef = $this->getCustomTypeDeclarationSQL($column);
        } else {
            $collation = ! empty($column['collation']) ?
                ' ' . $this->getColumnCollationDeclarationSQL($column['collation']) : '';

            $notnull = ! empty($column['notnull']) ? ' NOT NULL' : '';

            if (! empty($column['unique'])) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5656',
                    'The usage of the "unique" column property is deprecated. Use unique constraints instead.',
                );

                $unique = ' ' . $this->getUniqueFieldDeclarationSQL();
            } else {
                $unique = '';
            }

            if (! empty($column['check'])) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5656',
                    'The usage of the "check" column property is deprecated.',
                );

                $check = ' ' . $column['check'];
            } else {
                $check = '';
            }

            $typeDecl  = $column['type']->getSQLDeclaration($column, $this);
            $columnDef = $typeDecl . $collation . $notnull . $unique . $check;
        }

        return $name . ' ' . $columnDef;
    }

    /**
     * {@inheritDoc}
     *
     * SQL Server does not support quoting collation identifiers.
     */
    public function getColumnCollationDeclarationSQL($collation)
    {
        return 'COLLATE ' . $collation;
    }

    public function columnsEqual(Column $column1, Column $column2): bool
    {
        if (! parent::columnsEqual($column1, $column2)) {
            return false;
        }

        return $this->getDefaultValueDeclarationSQL($column1->toArray())
            === $this->getDefaultValueDeclarationSQL($column2->toArray());
    }

    protected function getLikeWildcardCharacters(): string
    {
        return parent::getLikeWildcardCharacters() . '[]^';
    }

    /**
     * Returns a unique default constraint name for a table and column.
     *
     * @param string $table  Name of the table to generate the unique default constraint name for.
     * @param string $column Name of the column in the table to generate the unique default constraint name for.
     */
    private function generateDefaultConstraintName($table, $column): string
    {
        return 'DF_' . $this->generateIdentifierName($table) . '_' . $this->generateIdentifierName($column);
    }

    /**
     * Returns a hash value for a given identifier.
     *
     * @param string $identifier Identifier to generate a hash value for.
     */
    private function generateIdentifierName($identifier): string
    {
        // Always generate name for unquoted identifiers to ensure consistency.
        $identifier = new Identifier($identifier);

        return strtoupper(dechex(crc32($identifier->getName())));
    }

    protected function getCommentOnTableSQL(string $tableName, ?string $comment): string
    {
        if (str_contains($tableName, '.')) {
            [$schemaName, $tableName] = explode('.', $tableName);
        } else {
            $schemaName = 'dbo';
        }

        return $this->getAddExtendedPropertySQL(
            'MS_Description',
            $comment,
            'SCHEMA',
            $this->quoteStringLiteral($schemaName),
            'TABLE',
            $this->quoteStringLiteral($this->unquoteSingleIdentifier($tableName)),
        );
    }

    /** @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon. */
    public function getListTableMetadataSQL(string $table): string
    {
        return sprintf(
            <<<'SQL'
                SELECT
                  p.value AS [table_comment]
                FROM
                  sys.tables AS tbl
                  INNER JOIN sys.extended_properties AS p ON p.major_id=tbl.object_id AND p.minor_id=0 AND p.class=1
                WHERE
                  (tbl.name=N%s and SCHEMA_NAME(tbl.schema_id)=N'dbo' and p.name=N'MS_Description')
                SQL
            ,
            $this->quoteStringLiteral($table),
        );
    }

    /** @param string $query */
    private function shouldAddOrderBy($query): bool
    {
        // Find the position of the last instance of ORDER BY and ensure it is not within a parenthetical statement
        // but can be in a newline
        $matches      = [];
        $matchesCount = preg_match_all('/[\\s]+order\\s+by\\s/im', $query, $matches, PREG_OFFSET_CAPTURE);
        if ($matchesCount === 0) {
            return true;
        }

        // ORDER BY instance may be in a subquery after ORDER BY
        // e.g. SELECT col1 FROM test ORDER BY (SELECT col2 from test ORDER BY col2)
        // if in the searched query ORDER BY clause was found where
        // number of open parentheses after the occurrence of the clause is equal to
        // number of closed brackets after the occurrence of the clause,
        // it means that ORDER BY is included in the query being checked
        while ($matchesCount > 0) {
            $orderByPos          = $matches[0][--$matchesCount][1];
            $openBracketsCount   = substr_count($query, '(', $orderByPos);
            $closedBracketsCount = substr_count($query, ')', $orderByPos);
            if ($openBracketsCount === $closedBracketsCount) {
                return false;
            }
        }

        return true;
    }

    public function createSchemaManager(Connection $connection): SQLServerSchemaManager
    {
        return new SQLServerSchemaManager($connection, $this);
    }
}
