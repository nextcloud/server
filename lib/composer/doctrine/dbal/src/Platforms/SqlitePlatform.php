<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\DBAL\Types;

use function array_merge;
use function array_unique;
use function array_values;
use function implode;
use function is_numeric;
use function sprintf;
use function sqrt;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function trim;

/**
 * The SqlitePlatform class describes the specifics and dialects of the SQLite
 * database platform.
 *
 * @todo   Rename: SQLitePlatform
 */
class SqlitePlatform extends AbstractPlatform
{
    /**
     * {@inheritDoc}
     */
    public function getRegexpExpression()
    {
        return 'REGEXP';
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getNowExpression($type = 'timestamp')
    {
        switch ($type) {
            case 'time':
                return 'time(\'now\')';

            case 'date':
                return 'date(\'now\')';

            case 'timestamp':
            default:
                return 'datetime(\'now\')';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTrimExpression($str, $mode = TrimMode::UNSPECIFIED, $char = false)
    {
        $trimChar = $char !== false ? ', ' . $char : '';

        switch ($mode) {
            case TrimMode::LEADING:
                $trimFn = 'LTRIM';
                break;

            case TrimMode::TRAILING:
                $trimFn = 'RTRIM';
                break;

            default:
                $trimFn = 'TRIM';
        }

        return $trimFn . '(' . $str . $trimChar . ')';
    }

    /**
     * {@inheritDoc}
     *
     * SQLite only supports the 2 parameter variant of this function
     */
    public function getSubstringExpression($string, $start, $length = null)
    {
        if ($length !== null) {
            return 'SUBSTR(' . $string . ', ' . $start . ', ' . $length . ')';
        }

        return 'SUBSTR(' . $string . ', ' . $start . ', LENGTH(' . $string . '))';
    }

    /**
     * {@inheritDoc}
     */
    public function getLocateExpression($str, $substr, $startPos = false)
    {
        if ($startPos === false) {
            return 'LOCATE(' . $str . ', ' . $substr . ')';
        }

        return 'LOCATE(' . $str . ', ' . $substr . ', ' . $startPos . ')';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit)
    {
        switch ($unit) {
            case DateIntervalUnit::SECOND:
            case DateIntervalUnit::MINUTE:
            case DateIntervalUnit::HOUR:
                return 'DATETIME(' . $date . ",'" . $operator . $interval . ' ' . $unit . "')";
        }

        switch ($unit) {
            case DateIntervalUnit::WEEK:
                $interval *= 7;
                $unit      = DateIntervalUnit::DAY;
                break;

            case DateIntervalUnit::QUARTER:
                $interval *= 3;
                $unit      = DateIntervalUnit::MONTH;
                break;
        }

        if (! is_numeric($interval)) {
            $interval = "' || " . $interval . " || '";
        }

        return 'DATE(' . $date . ",'" . $operator . $interval . ' ' . $unit . "')";
    }

    /**
     * {@inheritDoc}
     */
    public function getDateDiffExpression($date1, $date2)
    {
        return sprintf("JULIANDAY(%s, 'start of day') - JULIANDAY(%s, 'start of day')", $date1, $date2);
    }

    /**
     * {@inheritDoc}
     *
     * The SQLite platform doesn't support the concept of a database, therefore, it always returns an empty string
     * as an indicator of an implicitly selected database.
     *
     * @see \Doctrine\DBAL\Connection::getDatabase()
     */
    public function getCurrentDatabaseExpression(): string
    {
        return "''";
    }

    /**
     * {@inheritDoc}
     */
    protected function _getTransactionIsolationLevelSQL($level)
    {
        switch ($level) {
            case TransactionIsolationLevel::READ_UNCOMMITTED:
                return '0';

            case TransactionIsolationLevel::READ_COMMITTED:
            case TransactionIsolationLevel::REPEATABLE_READ:
            case TransactionIsolationLevel::SERIALIZABLE:
                return '1';

            default:
                return parent::_getTransactionIsolationLevelSQL($level);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSetTransactionIsolationSQL($level)
    {
        return 'PRAGMA read_uncommitted = ' . $this->_getTransactionIsolationLevelSQL($level);
    }

    /**
     * {@inheritDoc}
     */
    public function prefersIdentityColumns()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getBooleanTypeDeclarationSQL(array $column)
    {
        return 'BOOLEAN';
    }

    /**
     * {@inheritDoc}
     */
    public function getIntegerTypeDeclarationSQL(array $column)
    {
        return 'INTEGER' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getBigIntTypeDeclarationSQL(array $column)
    {
        // SQLite autoincrement is implicit for INTEGER PKs, but not for BIGINT columns
        if (! empty($column['autoincrement'])) {
            return $this->getIntegerTypeDeclarationSQL($column);
        }

        return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * @param array<string, mixed> $column
     *
     * @return string
     */
    public function getTinyIntTypeDeclarationSQL(array $column)
    {
        // SQLite autoincrement is implicit for INTEGER PKs, but not for TINYINT columns
        if (! empty($column['autoincrement'])) {
            return $this->getIntegerTypeDeclarationSQL($column);
        }

        return 'TINYINT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $column)
    {
        // SQLite autoincrement is implicit for INTEGER PKs, but not for SMALLINT columns
        if (! empty($column['autoincrement'])) {
            return $this->getIntegerTypeDeclarationSQL($column);
        }

        return 'SMALLINT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * @param array<string, mixed> $column
     *
     * @return string
     */
    public function getMediumIntTypeDeclarationSQL(array $column)
    {
        // SQLite autoincrement is implicit for INTEGER PKs, but not for MEDIUMINT columns
        if (! empty($column['autoincrement'])) {
            return $this->getIntegerTypeDeclarationSQL($column);
        }

        return 'MEDIUMINT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTypeDeclarationSQL(array $column)
    {
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
    protected function _getCommonIntegerTypeDeclarationSQL(array $column)
    {
        // sqlite autoincrement is only possible for the primary key
        if (! empty($column['autoincrement'])) {
            return ' PRIMARY KEY AUTOINCREMENT';
        }

        return ! empty($column['unsigned']) ? ' UNSIGNED' : '';
    }

    /**
     * {@inheritDoc}
     */
    public function getForeignKeyDeclarationSQL(ForeignKeyConstraint $foreignKey)
    {
        return parent::getForeignKeyDeclarationSQL(new ForeignKeyConstraint(
            $foreignKey->getQuotedLocalColumns($this),
            str_replace('.', '__', $foreignKey->getQuotedForeignTableName($this)),
            $foreignKey->getQuotedForeignColumns($this),
            $foreignKey->getName(),
            $foreignKey->getOptions()
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCreateTableSQL($name, array $columns, array $options = [])
    {
        $name        = str_replace('.', '__', $name);
        $queryFields = $this->getColumnDeclarationListSQL($columns);

        if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $constraintName => $definition) {
                $queryFields .= ', ' . $this->getUniqueConstraintDeclarationSQL($constraintName, $definition);
            }
        }

        $queryFields .= $this->getNonAutoincrementPrimaryKeyDefinition($columns, $options);

        if (isset($options['foreignKeys'])) {
            foreach ($options['foreignKeys'] as $foreignKey) {
                $queryFields .= ', ' . $this->getForeignKeyDeclarationSQL($foreignKey);
            }
        }

        $tableComment = '';
        if (isset($options['comment'])) {
            $comment = trim($options['comment'], " '");

            $tableComment = $this->getInlineTableCommentSQL($comment);
        }

        $query = ['CREATE TABLE ' . $name . ' ' . $tableComment . '(' . $queryFields . ')'];

        if (isset($options['alter']) && $options['alter'] === true) {
            return $query;
        }

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] as $indexDef) {
                $query[] = $this->getCreateIndexSQL($indexDef, $name);
            }
        }

        if (isset($options['unique']) && ! empty($options['unique'])) {
            foreach ($options['unique'] as $indexDef) {
                $query[] = $this->getCreateIndexSQL($indexDef, $name);
            }
        }

        return $query;
    }

    /**
     * Generate a PRIMARY KEY definition if no autoincrement value is used
     *
     * @param mixed[][] $columns
     * @param mixed[]   $options
     */
    private function getNonAutoincrementPrimaryKeyDefinition(array $columns, array $options): string
    {
        if (empty($options['primary'])) {
            return '';
        }

        $keyColumns = array_unique(array_values($options['primary']));

        foreach ($keyColumns as $keyColumn) {
            if (! empty($columns[$keyColumn]['autoincrement'])) {
                return '';
            }
        }

        return ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
    }

    /**
     * {@inheritDoc}
     */
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed)
    {
        return $fixed ? ($length > 0 ? 'CHAR(' . $length . ')' : 'CHAR(255)')
            : ($length > 0 ? 'VARCHAR(' . $length . ')' : 'TEXT');
    }

    /**
     * {@inheritdoc}
     */
    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed)
    {
        return 'BLOB';
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaryMaxLength()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaryDefaultLength()
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $column)
    {
        return 'CLOB';
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableConstraintsSQL($table)
    {
        $table = str_replace('.', '__', $table);

        return sprintf(
            "SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name = %s AND sql NOT NULL ORDER BY name",
            $this->quoteStringLiteral($table)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        $table = str_replace('.', '__', $table);

        return sprintf('PRAGMA table_info(%s)', $this->quoteStringLiteral($table));
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        $table = str_replace('.', '__', $table);

        return sprintf('PRAGMA index_list(%s)', $this->quoteStringLiteral($table));
    }

    /**
     * {@inheritDoc}
     */
    public function getListTablesSQL()
    {
        return 'SELECT name FROM sqlite_master'
            . " WHERE type = 'table'"
            . " AND name != 'sqlite_sequence'"
            . " AND name != 'geometry_columns'"
            . " AND name != 'spatial_ref_sys'"
            . ' UNION ALL SELECT name FROM sqlite_temp_master'
            . " WHERE type = 'table' ORDER BY name";
    }

    /**
     * {@inheritDoc}
     */
    public function getListViewsSQL($database)
    {
        return "SELECT name, sql FROM sqlite_master WHERE type='view' AND sql NOT NULL";
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
    public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey)
    {
        $query = parent::getAdvancedForeignKeyOptionsSQL($foreignKey);

        if (! $foreignKey->hasOption('deferrable') || $foreignKey->getOption('deferrable') === false) {
            $query .= ' NOT';
        }

        $query .= ' DEFERRABLE';
        $query .= ' INITIALLY';

        if ($foreignKey->hasOption('deferred') && $foreignKey->getOption('deferred') !== false) {
            $query .= ' DEFERRED';
        } else {
            $query .= ' IMMEDIATE';
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsIdentityColumns()
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
    public function supportsInlineColumnComments()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'sqlite';
    }

    /**
     * {@inheritDoc}
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        $tableIdentifier = new Identifier($tableName);
        $tableName       = str_replace('.', '__', $tableIdentifier->getQuotedName($this));

        return 'DELETE FROM ' . $tableName;
    }

    /**
     * User-defined function for Sqlite that is used with PDO::sqliteCreateFunction().
     *
     * @param int|float $value
     *
     * @return float
     */
    public static function udfSqrt($value)
    {
        return sqrt($value);
    }

    /**
     * User-defined function for Sqlite that implements MOD(a, b).
     *
     * @param int $a
     * @param int $b
     *
     * @return int
     */
    public static function udfMod($a, $b)
    {
        return $a % $b;
    }

    /**
     * @param string $str
     * @param string $substr
     * @param int    $offset
     *
     * @return int
     */
    public static function udfLocate($str, $substr, $offset = 0)
    {
        // SQL's LOCATE function works on 1-based positions, while PHP's strpos works on 0-based positions.
        // So we have to make them compatible if an offset is given.
        if ($offset > 0) {
            $offset -= 1;
        }

        $pos = strpos($str, $substr, $offset);

        if ($pos !== false) {
            return $pos + 1;
        }

        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getForUpdateSQL()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getInlineColumnCommentSQL($comment)
    {
        return '--' . str_replace("\n", "\n--", $comment) . "\n";
    }

    private function getInlineTableCommentSQL(string $comment): string
    {
        return $this->getInlineColumnCommentSQL($comment);
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = [
            'bigint'           => 'bigint',
            'bigserial'        => 'bigint',
            'blob'             => 'blob',
            'boolean'          => 'boolean',
            'char'             => 'string',
            'clob'             => 'text',
            'date'             => 'date',
            'datetime'         => 'datetime',
            'decimal'          => 'decimal',
            'double'           => 'float',
            'double precision' => 'float',
            'float'            => 'float',
            'image'            => 'string',
            'int'              => 'integer',
            'integer'          => 'integer',
            'longtext'         => 'text',
            'longvarchar'      => 'string',
            'mediumint'        => 'integer',
            'mediumtext'       => 'text',
            'ntext'            => 'string',
            'numeric'          => 'decimal',
            'nvarchar'         => 'string',
            'real'             => 'float',
            'serial'           => 'integer',
            'smallint'         => 'smallint',
            'text'             => 'text',
            'time'             => 'time',
            'timestamp'        => 'datetime',
            'tinyint'          => 'boolean',
            'tinytext'         => 'text',
            'varchar'          => 'string',
            'varchar2'         => 'string',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getReservedKeywordsClass()
    {
        return Keywords\SQLiteKeywords::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        if (! $diff->fromTable instanceof Table) {
            throw new Exception(
                'Sqlite platform requires for alter table the table diff with reference to original table schema'
            );
        }

        $sql = [];
        foreach ($diff->fromTable->getIndexes() as $index) {
            if ($index->isPrimary()) {
                continue;
            }

            $sql[] = $this->getDropIndexSQL($index, $diff->name);
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPostAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $fromTable = $diff->fromTable;

        if (! $fromTable instanceof Table) {
            throw new Exception(
                'Sqlite platform requires for alter table the table diff with reference to original table schema'
            );
        }

        $sql       = [];
        $tableName = $diff->getNewName();

        if ($tableName === false) {
            $tableName = $diff->getName($this);
        }

        foreach ($this->getIndexesInAlteredTable($diff, $fromTable) as $index) {
            if ($index->isPrimary()) {
                continue;
            }

            $sql[] = $this->getCreateIndexSQL($index, $tableName->getQuotedName($this));
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        if ($limit === null && $offset > 0) {
            return $query . ' LIMIT -1 OFFSET ' . $offset;
        }

        return parent::doModifyLimitQuery($query, $limit, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlobTypeDeclarationSQL(array $column)
    {
        return 'BLOB';
    }

    /**
     * {@inheritDoc}
     */
    public function getTemporaryTableName($tableName)
    {
        $tableName = str_replace('.', '__', $tableName);

        return $tableName;
    }

    /**
     * {@inheritDoc}
     *
     * Sqlite Platform emulates schema by underscoring each dot and generating tables
     * into the default database.
     *
     * This hack is implemented to be able to use SQLite as testdriver when
     * using schema supporting databases.
     */
    public function canEmulateSchemas()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsForeignKeyConstraints()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatePrimaryKeySQL(Index $index, $table)
    {
        throw new Exception('Sqlite platform does not support alter primary key.');
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateForeignKeySQL(ForeignKeyConstraint $foreignKey, $table)
    {
        throw new Exception('Sqlite platform does not support alter foreign key.');
    }

    /**
     * {@inheritdoc}
     */
    public function getDropForeignKeySQL($foreignKey, $table)
    {
        throw new Exception('Sqlite platform does not support alter foreign key.');
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateConstraintSQL(Constraint $constraint, $table)
    {
        throw new Exception('Sqlite platform does not support alter constraint.');
    }

    /**
     * {@inheritDoc}
     *
     * @param int|null $createFlags
     */
    public function getCreateTableSQL(Table $table, $createFlags = null)
    {
        $createFlags = $createFlags ?? self::CREATE_INDEXES | self::CREATE_FOREIGNKEYS;

        return parent::getCreateTableSQL($table, $createFlags);
    }

    /**
     * @param string      $table
     * @param string|null $database
     *
     * @return string
     */
    public function getListTableForeignKeysSQL($table, $database = null)
    {
        $table = str_replace('.', '__', $table);

        return sprintf('PRAGMA foreign_key_list(%s)', $this->quoteStringLiteral($table));
    }

    /**
     * {@inheritDoc}
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        $sql = $this->getSimpleAlterTableSQL($diff);
        if ($sql !== false) {
            return $sql;
        }

        $fromTable = $diff->fromTable;
        if (! $fromTable instanceof Table) {
            throw new Exception(
                'Sqlite platform requires for alter table the table diff with reference to original table schema'
            );
        }

        $table = clone $fromTable;

        $columns        = [];
        $oldColumnNames = [];
        $newColumnNames = [];
        $columnSql      = [];

        foreach ($table->getColumns() as $columnName => $column) {
            $columnName                  = strtolower($columnName);
            $columns[$columnName]        = $column;
            $oldColumnNames[$columnName] = $newColumnNames[$columnName] = $column->getQuotedName($this);
        }

        foreach ($diff->removedColumns as $columnName => $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnName = strtolower($columnName);
            if (! isset($columns[$columnName])) {
                continue;
            }

            unset(
                $columns[$columnName],
                $oldColumnNames[$columnName],
                $newColumnNames[$columnName]
            );
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            $oldColumnName = strtolower($oldColumnName);
            if (isset($columns[$oldColumnName])) {
                unset($columns[$oldColumnName]);
            }

            $columns[strtolower($column->getName())] = $column;

            if (! isset($newColumnNames[$oldColumnName])) {
                continue;
            }

            $newColumnNames[$oldColumnName] = $column->getQuotedName($this);
        }

        foreach ($diff->changedColumns as $oldColumnName => $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            if (isset($columns[$oldColumnName])) {
                unset($columns[$oldColumnName]);
            }

            $columns[strtolower($columnDiff->column->getName())] = $columnDiff->column;

            if (! isset($newColumnNames[$oldColumnName])) {
                continue;
            }

            $newColumnNames[$oldColumnName] = $columnDiff->column->getQuotedName($this);
        }

        foreach ($diff->addedColumns as $columnName => $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columns[strtolower($columnName)] = $column;
        }

        $sql      = [];
        $tableSql = [];
        if (! $this->onSchemaAlterTable($diff, $tableSql)) {
            $dataTable = new Table('__temp__' . $table->getName());

            $newTable = new Table(
                $table->getQuotedName($this),
                $columns,
                $this->getPrimaryIndexInAlteredTable($diff, $fromTable),
                [],
                $this->getForeignKeysInAlteredTable($diff, $fromTable),
                $table->getOptions()
            );
            $newTable->addOption('alter', true);

            $sql = $this->getPreAlterTableIndexForeignKeySQL($diff);

            $sql[] = sprintf(
                'CREATE TEMPORARY TABLE %s AS SELECT %s FROM %s',
                $dataTable->getQuotedName($this),
                implode(', ', $oldColumnNames),
                $table->getQuotedName($this)
            );
            $sql[] = $this->getDropTableSQL($fromTable);

            $sql   = array_merge($sql, $this->getCreateTableSQL($newTable));
            $sql[] = sprintf(
                'INSERT INTO %s (%s) SELECT %s FROM %s',
                $newTable->getQuotedName($this),
                implode(', ', $newColumnNames),
                implode(', ', $oldColumnNames),
                $dataTable->getQuotedName($this)
            );
            $sql[] = $this->getDropTableSQL($dataTable);

            $newName = $diff->getNewName();

            if ($newName !== false) {
                $sql[] = sprintf(
                    'ALTER TABLE %s RENAME TO %s',
                    $newTable->getQuotedName($this),
                    $newName->getQuotedName($this)
                );
            }

            $sql = array_merge($sql, $this->getPostAlterTableIndexForeignKeySQL($diff));
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    /**
     * @return string[]|false
     *
     * @throws Exception
     */
    private function getSimpleAlterTableSQL(TableDiff $diff)
    {
        // Suppress changes on integer type autoincrement columns.
        foreach ($diff->changedColumns as $oldColumnName => $columnDiff) {
            if (
                $columnDiff->fromColumn === null ||
                ! $columnDiff->column->getAutoincrement() ||
                ! $columnDiff->column->getType() instanceof Types\IntegerType
            ) {
                continue;
            }

            if (! $columnDiff->hasChanged('type') && $columnDiff->hasChanged('unsigned')) {
                unset($diff->changedColumns[$oldColumnName]);

                continue;
            }

            $fromColumnType = $columnDiff->fromColumn->getType();

            if (! ($fromColumnType instanceof Types\SmallIntType) && ! ($fromColumnType instanceof Types\BigIntType)) {
                continue;
            }

            unset($diff->changedColumns[$oldColumnName]);
        }

        if (
            ! empty($diff->renamedColumns)
            || ! empty($diff->addedForeignKeys)
            || ! empty($diff->addedIndexes)
            || ! empty($diff->changedColumns)
            || ! empty($diff->changedForeignKeys)
            || ! empty($diff->changedIndexes)
            || ! empty($diff->removedColumns)
            || ! empty($diff->removedForeignKeys)
            || ! empty($diff->removedIndexes)
            || ! empty($diff->renamedIndexes)
        ) {
            return false;
        }

        $table = new Table($diff->name);

        $sql       = [];
        $tableSql  = [];
        $columnSql = [];

        foreach ($diff->addedColumns as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $definition = array_merge([
                'unique' => null,
                'autoincrement' => null,
                'default' => null,
            ], $column->toArray());

            $type = $definition['type'];

            switch (true) {
                case isset($definition['columnDefinition']) || $definition['autoincrement'] || $definition['unique']:
                case $type instanceof Types\DateTimeType && $definition['default'] === $this->getCurrentTimestampSQL():
                case $type instanceof Types\DateType && $definition['default'] === $this->getCurrentDateSQL():
                case $type instanceof Types\TimeType && $definition['default'] === $this->getCurrentTimeSQL():
                    return false;
            }

            $definition['name'] = $column->getQuotedName($this);
            if ($type instanceof Types\StringType && $definition['length'] === null) {
                $definition['length'] = 255;
            }

            $sql[] = 'ALTER TABLE ' . $table->getQuotedName($this) . ' ADD COLUMN '
                . $this->getColumnDeclarationSQL($definition['name'], $definition);
        }

        if (! $this->onSchemaAlterTable($diff, $tableSql)) {
            if ($diff->newName !== false) {
                $newTable = new Identifier($diff->newName);

                $sql[] = 'ALTER TABLE ' . $table->getQuotedName($this) . ' RENAME TO '
                    . $newTable->getQuotedName($this);
            }
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    /**
     * @return string[]
     */
    private function getColumnNamesInAlteredTable(TableDiff $diff, Table $fromTable)
    {
        $columns = [];

        foreach ($fromTable->getColumns() as $columnName => $column) {
            $columns[strtolower($columnName)] = $column->getName();
        }

        foreach ($diff->removedColumns as $columnName => $column) {
            $columnName = strtolower($columnName);
            if (! isset($columns[$columnName])) {
                continue;
            }

            unset($columns[$columnName]);
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            $columnName                          = $column->getName();
            $columns[strtolower($oldColumnName)] = $columnName;
            $columns[strtolower($columnName)]    = $columnName;
        }

        foreach ($diff->changedColumns as $oldColumnName => $columnDiff) {
            $columnName                          = $columnDiff->column->getName();
            $columns[strtolower($oldColumnName)] = $columnName;
            $columns[strtolower($columnName)]    = $columnName;
        }

        foreach ($diff->addedColumns as $column) {
            $columnName                       = $column->getName();
            $columns[strtolower($columnName)] = $columnName;
        }

        return $columns;
    }

    /**
     * @return Index[]
     */
    private function getIndexesInAlteredTable(TableDiff $diff, Table $fromTable)
    {
        $indexes     = $fromTable->getIndexes();
        $columnNames = $this->getColumnNamesInAlteredTable($diff, $fromTable);

        foreach ($indexes as $key => $index) {
            foreach ($diff->renamedIndexes as $oldIndexName => $renamedIndex) {
                if (strtolower($key) !== strtolower($oldIndexName)) {
                    continue;
                }

                unset($indexes[$key]);
            }

            $changed      = false;
            $indexColumns = [];
            foreach ($index->getColumns() as $columnName) {
                $normalizedColumnName = strtolower($columnName);
                if (! isset($columnNames[$normalizedColumnName])) {
                    unset($indexes[$key]);
                    continue 2;
                }

                $indexColumns[] = $columnNames[$normalizedColumnName];
                if ($columnName === $columnNames[$normalizedColumnName]) {
                    continue;
                }

                $changed = true;
            }

            if (! $changed) {
                continue;
            }

            $indexes[$key] = new Index(
                $index->getName(),
                $indexColumns,
                $index->isUnique(),
                $index->isPrimary(),
                $index->getFlags()
            );
        }

        foreach ($diff->removedIndexes as $index) {
            $indexName = strtolower($index->getName());
            if (strlen($indexName) === 0 || ! isset($indexes[$indexName])) {
                continue;
            }

            unset($indexes[$indexName]);
        }

        foreach (array_merge($diff->changedIndexes, $diff->addedIndexes, $diff->renamedIndexes) as $index) {
            $indexName = strtolower($index->getName());
            if (strlen($indexName) > 0) {
                $indexes[$indexName] = $index;
            } else {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    /**
     * @return ForeignKeyConstraint[]
     */
    private function getForeignKeysInAlteredTable(TableDiff $diff, Table $fromTable)
    {
        $foreignKeys = $fromTable->getForeignKeys();
        $columnNames = $this->getColumnNamesInAlteredTable($diff, $fromTable);

        foreach ($foreignKeys as $key => $constraint) {
            $changed      = false;
            $localColumns = [];
            foreach ($constraint->getLocalColumns() as $columnName) {
                $normalizedColumnName = strtolower($columnName);
                if (! isset($columnNames[$normalizedColumnName])) {
                    unset($foreignKeys[$key]);
                    continue 2;
                }

                $localColumns[] = $columnNames[$normalizedColumnName];
                if ($columnName === $columnNames[$normalizedColumnName]) {
                    continue;
                }

                $changed = true;
            }

            if (! $changed) {
                continue;
            }

            $foreignKeys[$key] = new ForeignKeyConstraint(
                $localColumns,
                $constraint->getForeignTableName(),
                $constraint->getForeignColumns(),
                $constraint->getName(),
                $constraint->getOptions()
            );
        }

        foreach ($diff->removedForeignKeys as $constraint) {
            if (! $constraint instanceof ForeignKeyConstraint) {
                $constraint = new Identifier($constraint);
            }

            $constraintName = strtolower($constraint->getName());
            if (strlen($constraintName) === 0 || ! isset($foreignKeys[$constraintName])) {
                continue;
            }

            unset($foreignKeys[$constraintName]);
        }

        foreach (array_merge($diff->changedForeignKeys, $diff->addedForeignKeys) as $constraint) {
            $constraintName = strtolower($constraint->getName());
            if (strlen($constraintName) > 0) {
                $foreignKeys[$constraintName] = $constraint;
            } else {
                $foreignKeys[] = $constraint;
            }
        }

        return $foreignKeys;
    }

    /**
     * @return Index[]
     */
    private function getPrimaryIndexInAlteredTable(TableDiff $diff, Table $fromTable)
    {
        $primaryIndex = [];

        foreach ($this->getIndexesInAlteredTable($diff, $fromTable) as $index) {
            if (! $index->isPrimary()) {
                continue;
            }

            $primaryIndex = [$index->getName() => $index];
        }

        return $primaryIndex;
    }
}
