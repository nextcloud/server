<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\SQL\Builder\DefaultSelectSQLBuilder;
use Doctrine\DBAL\SQL\Builder\SelectSQLBuilder;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;
use InvalidArgumentException;

use function array_diff_key;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function func_get_arg;
use function func_get_args;
use function func_num_args;
use function implode;
use function in_array;
use function is_numeric;
use function is_string;
use function sprintf;
use function str_replace;
use function strcasecmp;
use function strtolower;
use function strtoupper;
use function trim;

/**
 * Provides the base implementation for the lowest versions of supported MySQL-like database platforms.
 */
abstract class AbstractMySQLPlatform extends AbstractPlatform
{
    public const LENGTH_LIMIT_TINYTEXT   = 255;
    public const LENGTH_LIMIT_TEXT       = 65535;
    public const LENGTH_LIMIT_MEDIUMTEXT = 16777215;

    public const LENGTH_LIMIT_TINYBLOB   = 255;
    public const LENGTH_LIMIT_BLOB       = 65535;
    public const LENGTH_LIMIT_MEDIUMBLOB = 16777215;

    /**
     * {@inheritDoc}
     */
    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        if ($limit !== null) {
            $query .= sprintf(' LIMIT %d', $limit);

            if ($offset > 0) {
                $query .= sprintf(' OFFSET %d', $offset);
            }
        } elseif ($offset > 0) {
            // 2^64-1 is the maximum of unsigned BIGINT, the biggest limit possible
            $query .= sprintf(' LIMIT 18446744073709551615 OFFSET %d', $offset);
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@see quoteIdentifier()} to quote identifiers instead.
     */
    public function getIdentifierQuoteCharacter()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5388',
            'AbstractMySQLPlatform::getIdentifierQuoteCharacter() is deprecated. Use quoteIdentifier() instead.',
        );

        return '`';
    }

    /**
     * {@inheritDoc}
     */
    public function getRegexpExpression()
    {
        return 'RLIKE';
    }

    /**
     * {@inheritDoc}
     */
    public function getLocateExpression($str, $substr, $startPos = false)
    {
        if ($startPos === false) {
            return 'LOCATE(' . $substr . ', ' . $str . ')';
        }

        return 'LOCATE(' . $substr . ', ' . $str . ', ' . $startPos . ')';
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
     */
    protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit)
    {
        $function = $operator === '+' ? 'DATE_ADD' : 'DATE_SUB';

        return $function . '(' . $date . ', INTERVAL ' . $interval . ' ' . $unit . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateDiffExpression($date1, $date2)
    {
        return 'DATEDIFF(' . $date1 . ', ' . $date2 . ')';
    }

    public function getCurrentDatabaseExpression(): string
    {
        return 'DATABASE()';
    }

    /**
     * {@inheritDoc}
     */
    public function getLengthExpression($column)
    {
        return 'CHAR_LENGTH(' . $column . ')';
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     */
    public function getListDatabasesSQL()
    {
        return 'SHOW DATABASES';
    }

    /**
     * @deprecated
     *
     * {@inheritDoc}
     */
    public function getListTableConstraintsSQL($table)
    {
        return 'SHOW INDEX FROM ' . $table;
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     *
     * Two approaches to listing the table indexes. The information_schema is
     * preferred, because it doesn't cause problems with SQL keywords such as "order" or "table".
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        if ($database !== null) {
            return 'SELECT NON_UNIQUE AS Non_Unique, INDEX_NAME AS Key_name, COLUMN_NAME AS Column_Name,' .
                   ' SUB_PART AS Sub_Part, INDEX_TYPE AS Index_Type' .
                   ' FROM information_schema.STATISTICS WHERE TABLE_NAME = ' . $this->quoteStringLiteral($table) .
                   ' AND TABLE_SCHEMA = ' . $this->quoteStringLiteral($database) .
                   ' ORDER BY SEQ_IN_INDEX ASC';
        }

        return 'SHOW INDEX FROM ' . $table;
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     */
    public function getListViewsSQL($database)
    {
        return 'SELECT * FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ' . $this->quoteStringLiteral($database);
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
        // The schema name is passed multiple times as a literal in the WHERE clause instead of using a JOIN condition
        // in order to avoid performance issues on MySQL older than 8.0 and the corresponding MariaDB versions
        // caused by https://bugs.mysql.com/bug.php?id=81347
        return 'SELECT k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, ' .
               'k.REFERENCED_COLUMN_NAME /*!50116 , c.UPDATE_RULE, c.DELETE_RULE */ ' .
               'FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k /*!50116 ' .
               'INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS c ON ' .
               'c.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND ' .
               'c.TABLE_NAME = k.TABLE_NAME */ ' .
               'WHERE k.TABLE_NAME = ' . $this->quoteStringLiteral($table) . ' ' .
               'AND k.TABLE_SCHEMA = ' . $this->getDatabaseNameSQL($database) . ' /*!50116 ' .
               'AND c.CONSTRAINT_SCHEMA = ' . $this->getDatabaseNameSQL($database) . ' */' .
               'ORDER BY k.ORDINAL_POSITION';
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
                'Relying on the default string column length on MySQL is deprecated'
                    . ', specify the length explicitly.',
            );
        }

        return $fixed ? ($length > 0 ? 'CHAR(' . $length . ')' : 'CHAR(255)')
                : ($length > 0 ? 'VARCHAR(' . $length . ')' : 'VARCHAR(255)');
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
                'Relying on the default binary column length on MySQL is deprecated'
                . ', specify the length explicitly.',
            );
        }

        return $fixed
            ? 'BINARY(' . ($length > 0 ? $length : 255) . ')'
            : 'VARBINARY(' . ($length > 0 ? $length : 255) . ')';
    }

    /**
     * Gets the SQL snippet used to declare a CLOB column type.
     *     TINYTEXT   : 2 ^  8 - 1 = 255
     *     TEXT       : 2 ^ 16 - 1 = 65535
     *     MEDIUMTEXT : 2 ^ 24 - 1 = 16777215
     *     LONGTEXT   : 2 ^ 32 - 1 = 4294967295
     *
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $column)
    {
        if (! empty($column['length']) && is_numeric($column['length'])) {
            $length = $column['length'];

            if ($length <= static::LENGTH_LIMIT_TINYTEXT) {
                return 'TINYTEXT';
            }

            if ($length <= static::LENGTH_LIMIT_TEXT) {
                return 'TEXT';
            }

            if ($length <= static::LENGTH_LIMIT_MEDIUMTEXT) {
                return 'MEDIUMTEXT';
            }
        }

        return 'LONGTEXT';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTypeDeclarationSQL(array $column)
    {
        if (isset($column['version']) && $column['version'] === true) {
            return 'TIMESTAMP';
        }

        return 'DATETIME';
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
        return 'TIME';
    }

    /**
     * {@inheritDoc}
     */
    public function getBooleanTypeDeclarationSQL(array $column)
    {
        return 'TINYINT(1)';
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     *
     * MySQL prefers "autoincrement" identity columns since sequences can only
     * be emulated with a table.
     */
    public function prefersIdentityColumns()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/1519',
            'AbstractMySQLPlatform::prefersIdentityColumns() is deprecated.',
        );

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * MySQL supports this through AUTO_INCREMENT columns.
     */
    public function supportsIdentityColumns()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function supportsInlineColumnComments()
    {
        return true;
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

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTablesSQL()
    {
        return "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'";
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        return 'SELECT COLUMN_NAME AS Field, COLUMN_TYPE AS Type, IS_NULLABLE AS `Null`, ' .
               'COLUMN_KEY AS `Key`, COLUMN_DEFAULT AS `Default`, EXTRA AS Extra, COLUMN_COMMENT AS Comment, ' .
               'CHARACTER_SET_NAME AS CharacterSet, COLLATION_NAME AS Collation ' .
               'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ' . $this->getDatabaseNameSQL($database) .
               ' AND TABLE_NAME = ' . $this->quoteStringLiteral($table) .
               ' ORDER BY ORDINAL_POSITION ASC';
    }

    /**
     * @deprecated Use {@see getColumnTypeSQLSnippet()} instead.
     *
     * The SQL snippets required to elucidate a column type
     *
     * Returns an array of the form [column type SELECT snippet, additional JOIN statement snippet]
     *
     * @return array{string, string}
     */
    public function getColumnTypeSQLSnippets(string $tableAlias = 'c'): array
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6202',
            'AbstractMySQLPlatform::getColumnTypeSQLSnippets() is deprecated. '
            . 'Use AbstractMySQLPlatform::getColumnTypeSQLSnippet() instead.',
        );

        return [$this->getColumnTypeSQLSnippet(...func_get_args()), ''];
    }

    /**
     * The SQL snippet required to elucidate a column type
     *
     * Returns a column type SELECT snippet string
     */
    public function getColumnTypeSQLSnippet(string $tableAlias = 'c', ?string $databaseName = null): string
    {
        return $tableAlias . '.COLUMN_TYPE';
    }

    /** @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon. */
    public function getListTableMetadataSQL(string $table, ?string $database = null): string
    {
        return sprintf(
            <<<'SQL'
SELECT t.ENGINE,
       t.AUTO_INCREMENT,
       t.TABLE_COMMENT,
       t.CREATE_OPTIONS,
       t.TABLE_COLLATION,
       ccsa.CHARACTER_SET_NAME
FROM information_schema.TABLES t
    INNER JOIN information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` ccsa
        ON ccsa.COLLATION_NAME = t.TABLE_COLLATION
WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = %s AND TABLE_NAME = %s
SQL
            ,
            $this->getDatabaseNameSQL($database),
            $this->quoteStringLiteral($table),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateTablesSQL(array $tables): array
    {
        $sql = [];

        foreach ($tables as $table) {
            $sql = array_merge($sql, $this->getCreateTableWithoutForeignKeysSQL($table));
        }

        foreach ($tables as $table) {
            if (! $table->hasOption('engine') || $this->engineSupportsForeignKeys($table->getOption('engine'))) {
                foreach ($table->getForeignKeys() as $foreignKey) {
                    $sql[] = $this->getCreateForeignKeySQL(
                        $foreignKey,
                        $table->getQuotedName($this),
                    );
                }
            } elseif (count($table->getForeignKeys()) > 0) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5414',
                    'Relying on the DBAL not generating DDL for foreign keys on MySQL engines'
                        . ' other than InnoDB is deprecated.'
                        . ' Define foreign key constraints only if they are necessary.',
                );
            }
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCreateTableSQL($name, array $columns, array $options = [])
    {
        $queryFields = $this->getColumnDeclarationListSQL($columns);

        if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $constraintName => $definition) {
                $queryFields .= ', ' . $this->getUniqueConstraintDeclarationSQL($constraintName, $definition);
            }
        }

        // add all indexes
        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] as $indexName => $definition) {
                $queryFields .= ', ' . $this->getIndexDeclarationSQL($indexName, $definition);
            }
        }

        // attach all primary keys
        if (isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns   = array_unique(array_values($options['primary']));
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE ';

        if (! empty($options['temporary'])) {
            $query .= 'TEMPORARY ';
        }

        $query .= 'TABLE ' . $name . ' (' . $queryFields . ') ';
        $query .= $this->buildTableOptions($options);
        $query .= $this->buildPartitionOptions($options);

        $sql = [$query];

        // Propagate foreign key constraints only for InnoDB.
        if (isset($options['foreignKeys'])) {
            if (! isset($options['engine']) || $this->engineSupportsForeignKeys($options['engine'])) {
                foreach ($options['foreignKeys'] as $definition) {
                    $sql[] = $this->getCreateForeignKeySQL($definition, $name);
                }
            } elseif (count($options['foreignKeys']) > 0) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5414',
                    'Relying on the DBAL not generating DDL for foreign keys on MySQL engines'
                    . ' other than InnoDB is deprecated.'
                    . ' Define foreign key constraints only if they are necessary.',
                );
            }
        }

        return $sql;
    }

    public function createSelectSQLBuilder(): SelectSQLBuilder
    {
        return new DefaultSelectSQLBuilder($this, 'FOR UPDATE', null);
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function getDefaultValueDeclarationSQL($column)
    {
        // Unset the default value if the given column definition does not allow default values.
        if ($column['type'] instanceof TextType || $column['type'] instanceof BlobType) {
            $column['default'] = null;
        }

        return parent::getDefaultValueDeclarationSQL($column);
    }

    /**
     * Build SQL for table options
     *
     * @param mixed[] $options
     */
    private function buildTableOptions(array $options): string
    {
        if (isset($options['table_options'])) {
            return $options['table_options'];
        }

        $tableOptions = [];

        // Charset
        if (! isset($options['charset'])) {
            $options['charset'] = 'utf8';
        }

        $tableOptions[] = sprintf('DEFAULT CHARACTER SET %s', $options['charset']);

        if (isset($options['collate'])) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/5214',
                'The "collate" option is deprecated in favor of "collation" and will be removed in 4.0.',
            );
            $options['collation'] = $options['collate'];
        }

        // Collation
        if (! isset($options['collation'])) {
            $options['collation'] = $options['charset'] . '_unicode_ci';
        }

        $tableOptions[] = $this->getColumnCollationDeclarationSQL($options['collation']);

        // Engine
        if (! isset($options['engine'])) {
            $options['engine'] = 'InnoDB';
        }

        $tableOptions[] = sprintf('ENGINE = %s', $options['engine']);

        // Auto increment
        if (isset($options['auto_increment'])) {
            $tableOptions[] = sprintf('AUTO_INCREMENT = %s', $options['auto_increment']);
        }

        // Comment
        if (isset($options['comment'])) {
            $tableOptions[] = sprintf('COMMENT = %s ', $this->quoteStringLiteral($options['comment']));
        }

        // Row format
        if (isset($options['row_format'])) {
            $tableOptions[] = sprintf('ROW_FORMAT = %s', $options['row_format']);
        }

        return implode(' ', $tableOptions);
    }

    /**
     * Build SQL for partition options.
     *
     * @param mixed[] $options
     */
    private function buildPartitionOptions(array $options): string
    {
        return isset($options['partition_options'])
            ? ' ' . $options['partition_options']
            : '';
    }

    private function engineSupportsForeignKeys(string $engine): bool
    {
        return strcasecmp(trim($engine), 'InnoDB') === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        $columnSql  = [];
        $queryParts = [];
        $newName    = $diff->getNewName();

        if ($newName !== false) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5663',
                'Generation of SQL that renames a table using %s is deprecated. Use getRenameTableSQL() instead.',
                __METHOD__,
            );

            $queryParts[] = 'RENAME TO ' . $newName->getQuotedName($this);
        }

        foreach ($diff->getAddedColumns() as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnProperties = array_merge($column->toArray(), [
                'comment' => $this->getColumnComment($column),
            ]);

            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL(
                $column->getQuotedName($this),
                $columnProperties,
            );
        }

        foreach ($diff->getDroppedColumns() as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            $queryParts[] =  'DROP ' . $column->getQuotedName($this);
        }

        foreach ($diff->getModifiedColumns() as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            $newColumn = $columnDiff->getNewColumn();

            $newColumnProperties = array_merge($newColumn->toArray(), [
                'comment' => $this->getColumnComment($newColumn),
            ]);

            $oldColumn = $columnDiff->getOldColumn() ?? $columnDiff->getOldColumnName();

            $queryParts[] =  'CHANGE ' . $oldColumn->getQuotedName($this) . ' '
                . $this->getColumnDeclarationSQL($newColumn->getQuotedName($this), $newColumnProperties);
        }

        foreach ($diff->getRenamedColumns() as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            $oldColumnName = new Identifier($oldColumnName);

            $columnProperties = array_merge($column->toArray(), [
                'comment' => $this->getColumnComment($column),
            ]);

            $queryParts[] = 'CHANGE ' . $oldColumnName->getQuotedName($this) . ' '
                . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnProperties);
        }

        $addedIndexes    = $this->indexAssetsByLowerCaseName($diff->getAddedIndexes());
        $modifiedIndexes = $this->indexAssetsByLowerCaseName($diff->getModifiedIndexes());
        $diffModified    = false;

        if (isset($addedIndexes['primary'])) {
            $keyColumns   = array_unique(array_values($addedIndexes['primary']->getColumns()));
            $queryParts[] = 'ADD PRIMARY KEY (' . implode(', ', $keyColumns) . ')';
            unset($addedIndexes['primary']);
            $diffModified = true;
        } elseif (isset($modifiedIndexes['primary'])) {
            $addedColumns = $this->indexAssetsByLowerCaseName($diff->getAddedColumns());

            // Necessary in case the new primary key includes a new auto_increment column
            foreach ($modifiedIndexes['primary']->getColumns() as $columnName) {
                if (isset($addedColumns[$columnName]) && $addedColumns[$columnName]->getAutoincrement()) {
                    $keyColumns   = array_unique(array_values($modifiedIndexes['primary']->getColumns()));
                    $queryParts[] = 'DROP PRIMARY KEY';
                    $queryParts[] = 'ADD PRIMARY KEY (' . implode(', ', $keyColumns) . ')';
                    unset($modifiedIndexes['primary']);
                    $diffModified = true;
                    break;
                }
            }
        }

        if ($diffModified) {
            $diff = new TableDiff(
                $diff->name,
                $diff->getAddedColumns(),
                $diff->getModifiedColumns(),
                $diff->getDroppedColumns(),
                array_values($addedIndexes),
                array_values($modifiedIndexes),
                $diff->getDroppedIndexes(),
                $diff->getOldTable(),
                $diff->getAddedForeignKeys(),
                $diff->getModifiedForeignKeys(),
                $diff->getDroppedForeignKeys(),
                $diff->getRenamedColumns(),
                $diff->getRenamedIndexes(),
            );
        }

        $sql      = [];
        $tableSql = [];

        if (! $this->onSchemaAlterTable($diff, $tableSql)) {
            if (count($queryParts) > 0) {
                $sql[] = 'ALTER TABLE ' . ($diff->getOldTable() ?? $diff->getName($this))->getQuotedName($this) . ' '
                    . implode(', ', $queryParts);
            }

            $sql = array_merge(
                $this->getPreAlterTableIndexForeignKeySQL($diff),
                $sql,
                $this->getPostAlterTableIndexForeignKeySQL($diff),
            );
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $sql = [];

        $tableNameSQL = ($diff->getOldTable() ?? $diff->getName($this))->getQuotedName($this);

        foreach ($diff->getModifiedIndexes() as $changedIndex) {
            $sql = array_merge($sql, $this->getPreAlterTableAlterPrimaryKeySQL($diff, $changedIndex));
        }

        foreach ($diff->getDroppedIndexes() as $droppedIndex) {
            $sql = array_merge($sql, $this->getPreAlterTableAlterPrimaryKeySQL($diff, $droppedIndex));

            foreach ($diff->getAddedIndexes() as $addedIndex) {
                if ($droppedIndex->getColumns() !== $addedIndex->getColumns()) {
                    continue;
                }

                $indexClause = 'INDEX ' . $addedIndex->getName();

                if ($addedIndex->isPrimary()) {
                    $indexClause = 'PRIMARY KEY';
                } elseif ($addedIndex->isUnique()) {
                    $indexClause = 'UNIQUE INDEX ' . $addedIndex->getName();
                }

                $query  = 'ALTER TABLE ' . $tableNameSQL . ' DROP INDEX ' . $droppedIndex->getName() . ', ';
                $query .= 'ADD ' . $indexClause;
                $query .= ' (' . $this->getIndexFieldDeclarationListSQL($addedIndex) . ')';

                $sql[] = $query;

                $diff->unsetAddedIndex($addedIndex);
                $diff->unsetDroppedIndex($droppedIndex);

                break;
            }
        }

        $engine = 'INNODB';

        $table = $diff->getOldTable();

        if ($table !== null && $table->hasOption('engine')) {
            $engine = strtoupper(trim($table->getOption('engine')));
        }

        // Suppress foreign key constraint propagation on non-supporting engines.
        if ($engine !== 'INNODB') {
            $diff->addedForeignKeys   = [];
            $diff->changedForeignKeys = [];
            $diff->removedForeignKeys = [];
        }

        $sql = array_merge(
            $sql,
            $this->getPreAlterTableAlterIndexForeignKeySQL($diff),
            parent::getPreAlterTableIndexForeignKeySQL($diff),
            $this->getPreAlterTableRenameIndexForeignKeySQL($diff),
        );

        return $sql;
    }

    /**
     * @return string[]
     *
     * @throws Exception
     */
    private function getPreAlterTableAlterPrimaryKeySQL(TableDiff $diff, Index $index): array
    {
        if (! $index->isPrimary()) {
            return [];
        }

        $table = $diff->getOldTable();

        if ($table === null) {
            return [];
        }

        $sql = [];

        $tableNameSQL = ($diff->getOldTable() ?? $diff->getName($this))->getQuotedName($this);

        // Dropping primary keys requires to unset autoincrement attribute on the particular column first.
        foreach ($index->getColumns() as $columnName) {
            if (! $table->hasColumn($columnName)) {
                continue;
            }

            $column = $table->getColumn($columnName);

            if ($column->getAutoincrement() !== true) {
                continue;
            }

            $column->setAutoincrement(false);

            $sql[] = 'ALTER TABLE ' . $tableNameSQL . ' MODIFY ' .
                $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray());

            // original autoincrement information might be needed later on by other parts of the table alteration
            $column->setAutoincrement(true);
        }

        return $sql;
    }

    /**
     * @param TableDiff $diff The table diff to gather the SQL for.
     *
     * @return string[]
     *
     * @throws Exception
     */
    private function getPreAlterTableAlterIndexForeignKeySQL(TableDiff $diff): array
    {
        $table = $diff->getOldTable();

        if ($table === null) {
            return [];
        }

        $primaryKey = $table->getPrimaryKey();

        if ($primaryKey === null) {
            return [];
        }

        $primaryKeyColumns = [];

        foreach ($primaryKey->getColumns() as $columnName) {
            if (! $table->hasColumn($columnName)) {
                continue;
            }

            $primaryKeyColumns[] = $table->getColumn($columnName);
        }

        if (count($primaryKeyColumns) === 0) {
            return [];
        }

        $sql = [];

        $tableNameSQL = $table->getQuotedName($this);

        foreach ($diff->getModifiedIndexes() as $changedIndex) {
            // Changed primary key
            if (! $changedIndex->isPrimary()) {
                continue;
            }

            foreach ($primaryKeyColumns as $column) {
                // Check if an autoincrement column was dropped from the primary key.
                if (! $column->getAutoincrement() || in_array($column->getName(), $changedIndex->getColumns(), true)) {
                    continue;
                }

                // The autoincrement attribute needs to be removed from the dropped column
                // before we can drop and recreate the primary key.
                $column->setAutoincrement(false);

                $sql[] = 'ALTER TABLE ' . $tableNameSQL . ' MODIFY ' .
                    $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray());

                // Restore the autoincrement attribute as it might be needed later on
                // by other parts of the table alteration.
                $column->setAutoincrement(true);
            }
        }

        return $sql;
    }

    /**
     * @param TableDiff $diff The table diff to gather the SQL for.
     *
     * @return string[]
     */
    protected function getPreAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        $sql = [];

        $tableNameSQL = ($diff->getOldTable() ?? $diff->getName($this))->getQuotedName($this);

        foreach ($this->getRemainingForeignKeyConstraintsRequiringRenamedIndexes($diff) as $foreignKey) {
            if (in_array($foreignKey, $diff->getModifiedForeignKeys(), true)) {
                continue;
            }

            $sql[] = $this->getDropForeignKeySQL($foreignKey->getQuotedName($this), $tableNameSQL);
        }

        return $sql;
    }

    /**
     * Returns the remaining foreign key constraints that require one of the renamed indexes.
     *
     * "Remaining" here refers to the diff between the foreign keys currently defined in the associated
     * table and the foreign keys to be removed.
     *
     * @param TableDiff $diff The table diff to evaluate.
     *
     * @return ForeignKeyConstraint[]
     */
    private function getRemainingForeignKeyConstraintsRequiringRenamedIndexes(TableDiff $diff): array
    {
        if (count($diff->getRenamedIndexes()) === 0) {
            return [];
        }

        $table = $diff->getOldTable();

        if ($table === null) {
            return [];
        }

        $foreignKeys = [];
        /** @var ForeignKeyConstraint[] $remainingForeignKeys */
        $remainingForeignKeys = array_diff_key(
            $table->getForeignKeys(),
            $diff->getDroppedForeignKeys(),
        );

        foreach ($remainingForeignKeys as $foreignKey) {
            foreach ($diff->getRenamedIndexes() as $index) {
                if ($foreignKey->intersectsIndexColumns($index)) {
                    $foreignKeys[] = $foreignKey;

                    break;
                }
            }
        }

        return $foreignKeys;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPostAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        return array_merge(
            parent::getPostAlterTableIndexForeignKeySQL($diff),
            $this->getPostAlterTableRenameIndexForeignKeySQL($diff),
        );
    }

    /**
     * @param TableDiff $diff The table diff to gather the SQL for.
     *
     * @return string[]
     */
    protected function getPostAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        $sql     = [];
        $newName = $diff->getNewName();

        if ($newName !== false) {
            $tableNameSQL = $newName->getQuotedName($this);
        } else {
            $tableNameSQL = ($diff->getOldTable() ?? $diff->getName($this))->getQuotedName($this);
        }

        foreach ($this->getRemainingForeignKeyConstraintsRequiringRenamedIndexes($diff) as $foreignKey) {
            if (in_array($foreignKey, $diff->getModifiedForeignKeys(), true)) {
                continue;
            }

            $sql[] = $this->getCreateForeignKeySQL($foreignKey, $tableNameSQL);
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    protected function getCreateIndexSQLFlags(Index $index)
    {
        $type = '';
        if ($index->isUnique()) {
            $type .= 'UNIQUE ';
        } elseif ($index->hasFlag('fulltext')) {
            $type .= 'FULLTEXT ';
        } elseif ($index->hasFlag('spatial')) {
            $type .= 'SPATIAL ';
        }

        return $type;
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
    public function getFloatDeclarationSQL(array $column)
    {
        return 'DOUBLE PRECISION' . $this->getUnsignedDeclaration($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getDecimalTypeDeclarationSQL(array $column)
    {
        return parent::getDecimalTypeDeclarationSQL($column) . $this->getUnsignedDeclaration($column);
    }

    /**
     * Get unsigned declaration for a column.
     *
     * @param mixed[] $columnDef
     */
    private function getUnsignedDeclaration(array $columnDef): string
    {
        return ! empty($columnDef['unsigned']) ? ' UNSIGNED' : '';
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $column)
    {
        $autoinc = '';
        if (! empty($column['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT';
        }

        return $this->getUnsignedDeclaration($column) . $autoinc;
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function getColumnCharsetDeclarationSQL($charset)
    {
        return 'CHARACTER SET ' . $charset;
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey)
    {
        $query = '';
        if ($foreignKey->hasOption('match')) {
            $query .= ' MATCH ' . $foreignKey->getOption('match');
        }

        $query .= parent::getAdvancedForeignKeyOptionsSQL($foreignKey);

        return $query;
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

            $indexName = $index->getQuotedName($this);
        } elseif (is_string($index)) {
            $indexName = $index;
        } else {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $index parameter to be string or ' . Index::class . '.',
            );
        }

        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        } elseif (! is_string($table)) {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $table parameter to be string or ' . Table::class . '.',
            );
        }

        if ($index instanceof Index && $index->isPrimary()) {
            // MySQL primary keys are always named "PRIMARY",
            // so we cannot use them in statements because of them being keyword.
            return $this->getDropPrimaryKeySQL($table);
        }

        return 'DROP INDEX ' . $indexName . ' ON ' . $table;
    }

    /**
     * @param string $table
     *
     * @return string
     */
    protected function getDropPrimaryKeySQL($table)
    {
        return 'ALTER TABLE ' . $table . ' DROP PRIMARY KEY';
    }

    /**
     * The `ALTER TABLE ... DROP CONSTRAINT` syntax is only available as of MySQL 8.0.19.
     *
     * @link https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     */
    public function getDropUniqueConstraintSQL(string $name, string $tableName): string
    {
        return $this->getDropIndexSQL($name, $tableName);
    }

    /**
     * {@inheritDoc}
     */
    public function getSetTransactionIsolationSQL($level)
    {
        return 'SET SESSION TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSQL($level);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4749',
            'AbstractMySQLPlatform::getName() is deprecated. Identify platforms by their class.',
        );

        return 'mysql';
    }

    /**
     * {@inheritDoc}
     */
    public function getReadLockSQL()
    {
        return 'LOCK IN SHARE MODE';
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = [
            'bigint'     => Types::BIGINT,
            'binary'     => Types::BINARY,
            'blob'       => Types::BLOB,
            'char'       => Types::STRING,
            'date'       => Types::DATE_MUTABLE,
            'datetime'   => Types::DATETIME_MUTABLE,
            'decimal'    => Types::DECIMAL,
            'double'     => Types::FLOAT,
            'float'      => Types::FLOAT,
            'int'        => Types::INTEGER,
            'integer'    => Types::INTEGER,
            'longblob'   => Types::BLOB,
            'longtext'   => Types::TEXT,
            'mediumblob' => Types::BLOB,
            'mediumint'  => Types::INTEGER,
            'mediumtext' => Types::TEXT,
            'numeric'    => Types::DECIMAL,
            'real'       => Types::FLOAT,
            'set'        => Types::SIMPLE_ARRAY,
            'smallint'   => Types::SMALLINT,
            'string'     => Types::STRING,
            'text'       => Types::TEXT,
            'time'       => Types::TIME_MUTABLE,
            'timestamp'  => Types::DATETIME_MUTABLE,
            'tinyblob'   => Types::BLOB,
            'tinyint'    => Types::BOOLEAN,
            'tinytext'   => Types::TEXT,
            'varbinary'  => Types::BINARY,
            'varchar'    => Types::STRING,
            'year'       => Types::DATE_MUTABLE,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function getVarcharMaxLength()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3263',
            'AbstractMySQLPlatform::getVarcharMaxLength() is deprecated.',
        );

        return 65535;
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
            'AbstractMySQLPlatform::getBinaryMaxLength() is deprecated.',
        );

        return 65535;
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
            'AbstractMySQLPlatform::getReservedKeywordsClass() is deprecated,'
                . ' use AbstractMySQLPlatform::createReservedKeywordsList() instead.',
        );

        return Keywords\MySQLKeywords::class;
    }

    /**
     * {@inheritDoc}
     *
     * MySQL commits a transaction implicitly when DROP TABLE is executed, however not
     * if DROP TEMPORARY TABLE is executed.
     */
    public function getDropTemporaryTableSQL($table)
    {
        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        } elseif (! is_string($table)) {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $table parameter to be string or ' . Table::class . '.',
            );
        }

        return 'DROP TEMPORARY TABLE ' . $table;
    }

    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     *     TINYBLOB   : 2 ^  8 - 1 = 255
     *     BLOB       : 2 ^ 16 - 1 = 65535
     *     MEDIUMBLOB : 2 ^ 24 - 1 = 16777215
     *     LONGBLOB   : 2 ^ 32 - 1 = 4294967295
     *
     * {@inheritDoc}
     */
    public function getBlobTypeDeclarationSQL(array $column)
    {
        if (! empty($column['length']) && is_numeric($column['length'])) {
            $length = $column['length'];

            if ($length <= static::LENGTH_LIMIT_TINYBLOB) {
                return 'TINYBLOB';
            }

            if ($length <= static::LENGTH_LIMIT_BLOB) {
                return 'BLOB';
            }

            if ($length <= static::LENGTH_LIMIT_MEDIUMBLOB) {
                return 'MEDIUMBLOB';
            }
        }

        return 'LONGBLOB';
    }

    /**
     * {@inheritDoc}
     */
    public function quoteStringLiteral($str)
    {
        $str = str_replace('\\', '\\\\', $str); // MySQL requires backslashes to be escaped

        return parent::quoteStringLiteral($str);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultTransactionIsolationLevel()
    {
        return TransactionIsolationLevel::REPEATABLE_READ;
    }

    public function supportsColumnLengthIndexes(): bool
    {
        return true;
    }

    /** @deprecated Will be removed without replacement. */
    protected function getDatabaseNameSQL(?string $databaseName): string
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6215',
            '%s is deprecated without replacement.',
            __METHOD__,
        );

        if ($databaseName !== null) {
            return $this->quoteStringLiteral($databaseName);
        }

        return $this->getCurrentDatabaseExpression();
    }

    public function createSchemaManager(Connection $connection): MySQLSchemaManager
    {
        return new MySQLSchemaManager($connection, $this);
    }

    /**
     * @param list<T> $assets
     *
     * @return array<string,T>
     *
     * @template T of AbstractAsset
     */
    private function indexAssetsByLowerCaseName(array $assets): array
    {
        $result = [];

        foreach ($assets as $asset) {
            $result[strtolower($asset->getName())] = $asset;
        }

        return $result;
    }

    public function fetchTableOptionsByTable(bool $includeTableName): string
    {
        $sql = <<<'SQL'
    SELECT t.TABLE_NAME,
           t.ENGINE,
           t.AUTO_INCREMENT,
           t.TABLE_COMMENT,
           t.CREATE_OPTIONS,
           t.TABLE_COLLATION,
           ccsa.CHARACTER_SET_NAME
      FROM information_schema.TABLES t
        INNER JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY ccsa
          ON ccsa.COLLATION_NAME = t.TABLE_COLLATION
SQL;

        $conditions = ['t.TABLE_SCHEMA = ?'];

        if ($includeTableName) {
            $conditions[] = 't.TABLE_NAME = ?';
        }

        $conditions[] = "t.TABLE_TYPE = 'BASE TABLE'";

        return $sql . ' WHERE ' . implode(' AND ', $conditions);
    }
}
