<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\TextType;
use InvalidArgumentException;

use function array_diff_key;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function func_get_args;
use function implode;
use function in_array;
use function is_numeric;
use function is_string;
use function sprintf;
use function str_replace;
use function strtoupper;
use function trim;

/**
 * The MySQLPlatform provides the behavior, features and SQL dialect of the
 * MySQL database platform. This platform represents a MySQL 5.0 or greater platform that
 * uses the InnoDB storage engine.
 *
 * @todo   Rename: MySQLPlatform
 */
class MySQLPlatform extends AbstractPlatform
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
            $query .= ' LIMIT ' . $limit;

            if ($offset > 0) {
                $query .= ' OFFSET ' . $offset;
            }
        } elseif ($offset > 0) {
            // 2^64-1 is the maximum of unsigned BIGINT, the biggest limit possible
            $query .= ' LIMIT 18446744073709551615 OFFSET ' . $offset;
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierQuoteCharacter()
    {
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
     * {@inheritdoc}
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
    public function getListDatabasesSQL()
    {
        return 'SHOW DATABASES';
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableConstraintsSQL($table)
    {
        return 'SHOW INDEX FROM ' . $table;
    }

    /**
     * {@inheritDoc}
     *
     * Two approaches to listing the table indexes. The information_schema is
     * preferred, because it doesn't cause problems with SQL keywords such as "order" or "table".
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        if ($database !== null) {
            $database = $this->quoteStringLiteral($database);
            $table    = $this->quoteStringLiteral($table);

            return 'SELECT NON_UNIQUE AS Non_Unique, INDEX_NAME AS Key_name, COLUMN_NAME AS Column_Name,' .
                   ' SUB_PART AS Sub_Part, INDEX_TYPE AS Index_Type' .
                   ' FROM information_schema.STATISTICS WHERE TABLE_NAME = ' . $table .
                   ' AND TABLE_SCHEMA = ' . $database .
                   ' ORDER BY SEQ_IN_INDEX ASC';
        }

        return 'SHOW INDEX FROM ' . $table;
    }

    /**
     * {@inheritDoc}
     */
    public function getListViewsSQL($database)
    {
        $database = $this->quoteStringLiteral($database);

        return 'SELECT * FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ' . $database;
    }

    /**
     * @param string      $table
     * @param string|null $database
     *
     * @return string
     */
    public function getListTableForeignKeysSQL($table, $database = null)
    {
        $table = $this->quoteStringLiteral($table);

        if ($database !== null) {
            $database = $this->quoteStringLiteral($database);
        }

        $sql = 'SELECT DISTINCT k.`CONSTRAINT_NAME`, k.`COLUMN_NAME`, k.`REFERENCED_TABLE_NAME`, ' .
               'k.`REFERENCED_COLUMN_NAME` /*!50116 , c.update_rule, c.delete_rule */ ' .
               'FROM information_schema.key_column_usage k /*!50116 ' .
               'INNER JOIN information_schema.referential_constraints c ON ' .
               '  c.constraint_name = k.constraint_name AND ' .
               '  c.table_name = ' . $table . ' */ WHERE k.table_name = ' . $table;

        $databaseNameSql = $database ?? 'DATABASE()';

        return $sql . ' AND k.table_schema = ' . $databaseNameSql
            . ' /*!50116 AND c.constraint_schema = ' . $databaseNameSql . ' */'
            . ' AND k.`REFERENCED_COLUMN_NAME` is not NULL';
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateViewSQL($name, $sql)
    {
        return 'CREATE VIEW ' . $name . ' AS ' . $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function getDropViewSQL($name)
    {
        return 'DROP VIEW ' . $name;
    }

    /**
     * {@inheritDoc}
     */
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed)
    {
        return $fixed ? ($length > 0 ? 'CHAR(' . $length . ')' : 'CHAR(255)')
                : ($length > 0 ? 'VARCHAR(' . $length . ')' : 'VARCHAR(255)');
    }

    /**
     * {@inheritdoc}
     */
    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed)
    {
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
     * MySQL prefers "autoincrement" identity columns since sequences can only
     * be emulated with a table.
     */
    public function prefersIdentityColumns()
    {
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
     */
    public function supportsInlineColumnComments()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsColumnCollation()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getListTablesSQL()
    {
        return "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'";
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        $table = $this->quoteStringLiteral($table);

        if ($database !== null) {
            $database = $this->quoteStringLiteral($database);
        } else {
            $database = 'DATABASE()';
        }

        return 'SELECT COLUMN_NAME AS Field, COLUMN_TYPE AS Type, IS_NULLABLE AS `Null`, ' .
               'COLUMN_KEY AS `Key`, COLUMN_DEFAULT AS `Default`, EXTRA AS Extra, COLUMN_COMMENT AS Comment, ' .
               'CHARACTER_SET_NAME AS CharacterSet, COLLATION_NAME AS Collation ' .
               'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ' . $database . ' AND TABLE_NAME = ' . $table .
               ' ORDER BY ORDINAL_POSITION ASC';
    }

    public function getListTableMetadataSQL(string $table, ?string $database = null): string
    {
        return sprintf(
            <<<'SQL'
SELECT ENGINE, AUTO_INCREMENT, TABLE_COLLATION, TABLE_COMMENT, CREATE_OPTIONS
FROM information_schema.TABLES
WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = %s AND TABLE_NAME = %s
SQL
            ,
            $database !== null ? $this->quoteStringLiteral($database) : 'DATABASE()',
            $this->quoteStringLiteral($table)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateDatabaseSQL($name)
    {
        return 'CREATE DATABASE ' . $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getDropDatabaseSQL($name)
    {
        return 'DROP DATABASE ' . $name;
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

        $sql    = [$query];
        $engine = 'INNODB';

        if (isset($options['engine'])) {
            $engine = strtoupper(trim($options['engine']));
        }

        // Propagate foreign key constraints only for InnoDB.
        if (isset($options['foreignKeys']) && $engine === 'INNODB') {
            foreach ((array) $options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $name);
            }
        }

        return $sql;
    }

    /**
     * {@inheritdoc}
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
     *
     * @return string
     */
    private function buildTableOptions(array $options)
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

        // Collate
        if (! isset($options['collate'])) {
            $options['collate'] = $options['charset'] . '_unicode_ci';
        }

        $tableOptions[] = $this->getColumnCollationDeclarationSQL($options['collate']);

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
     *
     * @return string
     */
    private function buildPartitionOptions(array $options)
    {
        return isset($options['partition_options'])
            ? ' ' . $options['partition_options']
            : '';
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
            $queryParts[] = 'RENAME TO ' . $newName->getQuotedName($this);
        }

        foreach ($diff->addedColumns as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnArray = array_merge($column->toArray(), [
                'comment' => $this->getColumnComment($column),
            ]);

            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        foreach ($diff->removedColumns as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            $queryParts[] =  'DROP ' . $column->getQuotedName($this);
        }

        foreach ($diff->changedColumns as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            $column      = $columnDiff->column;
            $columnArray = $column->toArray();

            // Don't propagate default value changes for unsupported column types.
            if (
                $columnDiff->hasChanged('default') &&
                count($columnDiff->changedProperties) === 1 &&
                ($columnArray['type'] instanceof TextType || $columnArray['type'] instanceof BlobType)
            ) {
                continue;
            }

            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[]           =  'CHANGE ' . ($columnDiff->getOldColumnName()->getQuotedName($this)) . ' '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            $oldColumnName          = new Identifier($oldColumnName);
            $columnArray            = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[]           =  'CHANGE ' . $oldColumnName->getQuotedName($this) . ' '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        if (isset($diff->addedIndexes['primary'])) {
            $keyColumns   = array_unique(array_values($diff->addedIndexes['primary']->getColumns()));
            $queryParts[] = 'ADD PRIMARY KEY (' . implode(', ', $keyColumns) . ')';
            unset($diff->addedIndexes['primary']);
        } elseif (isset($diff->changedIndexes['primary'])) {
            // Necessary in case the new primary key includes a new auto_increment column
            foreach ($diff->changedIndexes['primary']->getColumns() as $columnName) {
                if (isset($diff->addedColumns[$columnName]) && $diff->addedColumns[$columnName]->getAutoincrement()) {
                    $keyColumns   = array_unique(array_values($diff->changedIndexes['primary']->getColumns()));
                    $queryParts[] = 'DROP PRIMARY KEY';
                    $queryParts[] = 'ADD PRIMARY KEY (' . implode(', ', $keyColumns) . ')';
                    unset($diff->changedIndexes['primary']);
                    break;
                }
            }
        }

        $sql      = [];
        $tableSql = [];

        if (! $this->onSchemaAlterTable($diff, $tableSql)) {
            if (count($queryParts) > 0) {
                $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' '
                    . implode(', ', $queryParts);
            }

            $sql = array_merge(
                $this->getPreAlterTableIndexForeignKeySQL($diff),
                $sql,
                $this->getPostAlterTableIndexForeignKeySQL($diff)
            );
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $sql   = [];
        $table = $diff->getName($this)->getQuotedName($this);

        foreach ($diff->changedIndexes as $changedIndex) {
            $sql = array_merge($sql, $this->getPreAlterTableAlterPrimaryKeySQL($diff, $changedIndex));
        }

        foreach ($diff->removedIndexes as $remKey => $remIndex) {
            $sql = array_merge($sql, $this->getPreAlterTableAlterPrimaryKeySQL($diff, $remIndex));

            foreach ($diff->addedIndexes as $addKey => $addIndex) {
                if ($remIndex->getColumns() !== $addIndex->getColumns()) {
                    continue;
                }

                $indexClause = 'INDEX ' . $addIndex->getName();

                if ($addIndex->isPrimary()) {
                    $indexClause = 'PRIMARY KEY';
                } elseif ($addIndex->isUnique()) {
                    $indexClause = 'UNIQUE INDEX ' . $addIndex->getName();
                }

                $query  = 'ALTER TABLE ' . $table . ' DROP INDEX ' . $remIndex->getName() . ', ';
                $query .= 'ADD ' . $indexClause;
                $query .= ' (' . $this->getIndexFieldDeclarationListSQL($addIndex) . ')';

                $sql[] = $query;

                unset($diff->removedIndexes[$remKey], $diff->addedIndexes[$addKey]);

                break;
            }
        }

        $engine = 'INNODB';

        if ($diff->fromTable instanceof Table && $diff->fromTable->hasOption('engine')) {
            $engine = strtoupper(trim($diff->fromTable->getOption('engine')));
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
            $this->getPreAlterTableRenameIndexForeignKeySQL($diff)
        );

        return $sql;
    }

    /**
     * @return string[]
     *
     * @throws Exception
     */
    private function getPreAlterTableAlterPrimaryKeySQL(TableDiff $diff, Index $index)
    {
        $sql = [];

        if (! $index->isPrimary() || ! $diff->fromTable instanceof Table) {
            return $sql;
        }

        $tableName = $diff->getName($this)->getQuotedName($this);

        // Dropping primary keys requires to unset autoincrement attribute on the particular column first.
        foreach ($index->getColumns() as $columnName) {
            if (! $diff->fromTable->hasColumn($columnName)) {
                continue;
            }

            $column = $diff->fromTable->getColumn($columnName);

            if ($column->getAutoincrement() !== true) {
                continue;
            }

            $column->setAutoincrement(false);

            $sql[] = 'ALTER TABLE ' . $tableName . ' MODIFY ' .
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
    private function getPreAlterTableAlterIndexForeignKeySQL(TableDiff $diff)
    {
        $sql   = [];
        $table = $diff->getName($this)->getQuotedName($this);

        foreach ($diff->changedIndexes as $changedIndex) {
            // Changed primary key
            if (! $changedIndex->isPrimary() || ! ($diff->fromTable instanceof Table)) {
                continue;
            }

            foreach ($diff->fromTable->getPrimaryKeyColumns() as $columnName => $column) {
                // Check if an autoincrement column was dropped from the primary key.
                if (! $column->getAutoincrement() || in_array($columnName, $changedIndex->getColumns(), true)) {
                    continue;
                }

                // The autoincrement attribute needs to be removed from the dropped column
                // before we can drop and recreate the primary key.
                $column->setAutoincrement(false);

                $sql[] = 'ALTER TABLE ' . $table . ' MODIFY ' .
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
        $sql       = [];
        $tableName = $diff->getName($this)->getQuotedName($this);

        foreach ($this->getRemainingForeignKeyConstraintsRequiringRenamedIndexes($diff) as $foreignKey) {
            if (in_array($foreignKey, $diff->changedForeignKeys, true)) {
                continue;
            }

            $sql[] = $this->getDropForeignKeySQL($foreignKey, $tableName);
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
    private function getRemainingForeignKeyConstraintsRequiringRenamedIndexes(TableDiff $diff)
    {
        if (empty($diff->renamedIndexes) || ! $diff->fromTable instanceof Table) {
            return [];
        }

        $foreignKeys = [];
        /** @var ForeignKeyConstraint[] $remainingForeignKeys */
        $remainingForeignKeys = array_diff_key(
            $diff->fromTable->getForeignKeys(),
            $diff->removedForeignKeys
        );

        foreach ($remainingForeignKeys as $foreignKey) {
            foreach ($diff->renamedIndexes as $index) {
                if ($foreignKey->intersectsIndexColumns($index)) {
                    $foreignKeys[] = $foreignKey;

                    break;
                }
            }
        }

        return $foreignKeys;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPostAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        return array_merge(
            parent::getPostAlterTableIndexForeignKeySQL($diff),
            $this->getPostAlterTableRenameIndexForeignKeySQL($diff)
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
            $tableName = $newName->getQuotedName($this);
        } else {
            $tableName = $diff->getName($this)->getQuotedName($this);
        }

        foreach ($this->getRemainingForeignKeyConstraintsRequiringRenamedIndexes($diff) as $foreignKey) {
            if (in_array($foreignKey, $diff->changedForeignKeys, true)) {
                continue;
            }

            $sql[] = $this->getCreateForeignKeySQL($foreignKey, $tableName);
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
     * {@inheritdoc}
     */
    public function getFloatDeclarationSQL(array $column)
    {
        return 'DOUBLE PRECISION' . $this->getUnsignedDeclaration($column);
    }

    /**
     * {@inheritdoc}
     */
    public function getDecimalTypeDeclarationSQL(array $column)
    {
        return parent::getDecimalTypeDeclarationSQL($column) . $this->getUnsignedDeclaration($column);
    }

    /**
     * Get unsigned declaration for a column.
     *
     * @param mixed[] $columnDef
     *
     * @return string
     */
    private function getUnsignedDeclaration(array $columnDef)
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
     */
    public function getColumnCharsetDeclarationSQL($charset)
    {
        return 'CHARACTER SET ' . $charset;
    }

    /**
     * {@inheritDoc}
     */
    public function getColumnCollationDeclarationSQL($collation)
    {
        return 'COLLATE ' . $this->quoteSingleIdentifier($collation);
    }

    /**
     * {@inheritDoc}
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
            $indexName = $index->getQuotedName($this);
        } elseif (is_string($index)) {
            $indexName = $index;
        } else {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $index parameter to be string or ' . Index::class . '.'
            );
        }

        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        } elseif (! is_string($table)) {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $table parameter to be string or ' . Table::class . '.'
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
            'bigint'     => 'bigint',
            'binary'     => 'binary',
            'blob'       => 'blob',
            'char'       => 'string',
            'date'       => 'date',
            'datetime'   => 'datetime',
            'decimal'    => 'decimal',
            'double'     => 'float',
            'float'      => 'float',
            'int'        => 'integer',
            'integer'    => 'integer',
            'longblob'   => 'blob',
            'longtext'   => 'text',
            'mediumblob' => 'blob',
            'mediumint'  => 'integer',
            'mediumtext' => 'text',
            'numeric'    => 'decimal',
            'real'       => 'float',
            'set'        => 'simple_array',
            'smallint'   => 'smallint',
            'string'     => 'string',
            'text'       => 'text',
            'time'       => 'time',
            'timestamp'  => 'datetime',
            'tinyblob'   => 'blob',
            'tinyint'    => 'boolean',
            'tinytext'   => 'text',
            'varbinary'  => 'binary',
            'varchar'    => 'string',
            'year'       => 'date',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getVarcharMaxLength()
    {
        return 65535;
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaryMaxLength()
    {
        return 65535;
    }

    /**
     * {@inheritDoc}
     */
    protected function getReservedKeywordsClass()
    {
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
            $table = $table->getQuotedName($this);
        } elseif (! is_string($table)) {
            throw new InvalidArgumentException(
                __METHOD__ . '() expects $table parameter to be string or ' . Table::class . '.'
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
     * {@inheritdoc}
     */
    public function quoteStringLiteral($str)
    {
        $str = str_replace('\\', '\\\\', $str); // MySQL requires backslashes to be escaped aswell.

        return parent::quoteStringLiteral($str);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTransactionIsolationLevel()
    {
        return TransactionIsolationLevel::REPEATABLE_READ;
    }

    public function supportsColumnLengthIndexes(): bool
    {
        return true;
    }
}
