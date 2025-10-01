<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\API\SQLite\UserDefinedFunctions;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\SqliteSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\SQL\Builder\DefaultSelectSQLBuilder;
use Doctrine\DBAL\SQL\Builder\SelectSQLBuilder;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\DBAL\Types;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\Deprecations\Deprecation;
use InvalidArgumentException;

use function array_combine;
use function array_keys;
use function array_merge;
use function array_search;
use function array_unique;
use function array_values;
use function count;
use function explode;
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
    private bool $schemaEmulationEnabled = true;

    /**
     * {@inheritDoc}
     */
    public function getRegexpExpression()
    {
        return 'REGEXP';
    }

    /**
     * @deprecated Generate dates within the application.
     *
     * @param string $type
     *
     * @return string
     */
    public function getNowExpression($type = 'timestamp')
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4753',
            'SqlitePlatform::getNowExpression() is deprecated. Generate dates within the application.',
        );

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
    public function getModExpression($expression1, $expression2)
    {
        return $expression1 . ' % ' . $expression2;
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
        if ($startPos === false || $startPos === 1 || $startPos === '1') {
            return 'INSTR(' . $str . ', ' . $substr . ')';
        }

        return 'CASE WHEN INSTR(SUBSTR(' . $str . ', ' . $startPos . '), ' . $substr
            . ') > 0 THEN INSTR(SUBSTR(' . $str . ', ' . $startPos . '), ' . $substr . ') + ' . $startPos
            . ' - 1 ELSE 0 END';
    }

    /**
     * {@inheritDoc}
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
                $interval = $this->multiplyInterval((string) $interval, 7);
                $unit     = DateIntervalUnit::DAY;
                break;

            case DateIntervalUnit::QUARTER:
                $interval = $this->multiplyInterval((string) $interval, 3);
                $unit     = DateIntervalUnit::MONTH;
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
     * The DBAL doesn't support databases on the SQLite platform. The expression here always returns a fixed string
     * as an indicator of an implicitly selected database.
     *
     * @link https://www.sqlite.org/lang_select.html
     * @see Connection::getDatabase()
     */
    public function getCurrentDatabaseExpression(): string
    {
        return "'main'";
    }

    /** @link https://www2.sqlite.org/cvstrac/wiki?p=UnsupportedSql */
    public function createSelectSQLBuilder(): SelectSQLBuilder
    {
        return new DefaultSelectSQLBuilder($this, null, null);
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
     *
     * @deprecated
     */
    public function prefersIdentityColumns()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/1519',
            'SqlitePlatform::prefersIdentityColumns() is deprecated.',
        );

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
     * @deprecated Use {@see getSmallIntTypeDeclarationSQL()} instead.
     *
     * @param array<string, mixed> $column
     *
     * @return string
     */
    public function getTinyIntTypeDeclarationSQL(array $column)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5511',
            '%s is deprecated. Use getSmallIntTypeDeclarationSQL() instead.',
            __METHOD__,
        );

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
     * @deprecated Use {@see getIntegerTypeDeclarationSQL()} instead.
     *
     * @param array<string, mixed> $column
     *
     * @return string
     */
    public function getMediumIntTypeDeclarationSQL(array $column)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5511',
            '%s is deprecated. Use getIntegerTypeDeclarationSQL() instead.',
            __METHOD__,
        );

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
     * Disables schema emulation.
     *
     * Schema emulation is enabled by default to maintain backwards compatibility.
     * Disable it to opt-in to the behavior of DBAL 4.
     *
     * @deprecated Will be removed in DBAL 4.0.
     */
    public function disableSchemaEmulation(): void
    {
        $this->schemaEmulationEnabled = false;
    }

    private function emulateSchemaNamespacing(string $tableName): string
    {
        return $this->schemaEmulationEnabled
            ? str_replace('.', '__', $tableName)
            : $tableName;
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function getForeignKeyDeclarationSQL(ForeignKeyConstraint $foreignKey)
    {
        return parent::getForeignKeyDeclarationSQL(new ForeignKeyConstraint(
            $foreignKey->getQuotedLocalColumns($this),
            $this->emulateSchemaNamespacing($foreignKey->getQuotedForeignTableName($this)),
            $foreignKey->getQuotedForeignColumns($this),
            $foreignKey->getName(),
            $foreignKey->getOptions(),
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCreateTableSQL($name, array $columns, array $options = [])
    {
        $name        = $this->emulateSchemaNamespacing($name);
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
     * {@inheritDoc}
     */
    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed)
    {
        return 'BLOB';
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
            'SqlitePlatform::getBinaryMaxLength() is deprecated.',
        );

        return 0;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function getBinaryDefaultLength()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3263',
            'Relying on the default binary column length is deprecated, specify the length explicitly.',
        );

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
     * @deprecated
     *
     * {@inheritDoc}
     */
    public function getListTableConstraintsSQL($table)
    {
        $table = $this->emulateSchemaNamespacing($table);

        return sprintf(
            "SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name = %s AND sql NOT NULL ORDER BY name",
            $this->quoteStringLiteral($table),
        );
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        $table = $this->emulateSchemaNamespacing($table);

        return sprintf('PRAGMA table_info(%s)', $this->quoteStringLiteral($table));
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        $table = $this->emulateSchemaNamespacing($table);

        return sprintf('PRAGMA index_list(%s)', $this->quoteStringLiteral($table));
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
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
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     */
    public function getListViewsSQL($database)
    {
        return "SELECT name, sql FROM sqlite_master WHERE type='view' AND sql NOT NULL";
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
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
     *
     * @deprecated
     */
    public function supportsCreateDropDatabase()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5513',
            '%s is deprecated.',
            __METHOD__,
        );

        return false;
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
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function supportsColumnCollation()
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
     */
    public function getName()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4749',
            'SqlitePlatform::getName() is deprecated. Identify platforms by their class.',
        );

        return 'sqlite';
    }

    /**
     * {@inheritDoc}
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        $tableIdentifier = new Identifier($tableName);
        $tableName       = $this->emulateSchemaNamespacing($tableIdentifier->getQuotedName($this));

        return 'DELETE FROM ' . $tableName;
    }

    /**
     * User-defined function for Sqlite that is used with PDO::sqliteCreateFunction().
     *
     * @deprecated The driver will use {@see sqrt()} in the next major release.
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
     * @deprecated The driver will use {@see UserDefinedFunctions::mod()} in the next major release.
     *
     * @param int $a
     * @param int $b
     *
     * @return int
     */
    public static function udfMod($a, $b)
    {
        return UserDefinedFunctions::mod($a, $b);
    }

    /**
     * @deprecated The driver will use {@see UserDefinedFunctions::locate()} in the next major release.
     *
     * @param string $str
     * @param string $substr
     * @param int    $offset
     *
     * @return int
     */
    public static function udfLocate($str, $substr, $offset = 0)
    {
        return UserDefinedFunctions::locate($str, $substr, $offset);
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This API is not portable.
     */
    public function getForUpdateSQL()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
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
            'bigint'           => Types\Types::BIGINT,
            'bigserial'        => Types\Types::BIGINT,
            'blob'             => Types\Types::BLOB,
            'boolean'          => Types\Types::BOOLEAN,
            'char'             => Types\Types::STRING,
            'clob'             => Types\Types::TEXT,
            'date'             => Types\Types::DATE_MUTABLE,
            'datetime'         => Types\Types::DATETIME_MUTABLE,
            'decimal'          => Types\Types::DECIMAL,
            'double'           => Types\Types::FLOAT,
            'double precision' => Types\Types::FLOAT,
            'float'            => Types\Types::FLOAT,
            'image'            => Types\Types::STRING,
            'int'              => Types\Types::INTEGER,
            'integer'          => Types\Types::INTEGER,
            'longtext'         => Types\Types::TEXT,
            'longvarchar'      => Types\Types::STRING,
            'mediumint'        => Types\Types::INTEGER,
            'mediumtext'       => Types\Types::TEXT,
            'ntext'            => Types\Types::STRING,
            'numeric'          => Types\Types::DECIMAL,
            'nvarchar'         => Types\Types::STRING,
            'real'             => Types\Types::FLOAT,
            'serial'           => Types\Types::INTEGER,
            'smallint'         => Types\Types::SMALLINT,
            'text'             => Types\Types::TEXT,
            'time'             => Types\Types::TIME_MUTABLE,
            'timestamp'        => Types\Types::DATETIME_MUTABLE,
            'tinyint'          => Types\Types::BOOLEAN,
            'tinytext'         => Types\Types::TEXT,
            'varchar'          => Types\Types::STRING,
            'varchar2'         => Types\Types::STRING,
        ];
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
            'SqlitePlatform::getReservedKeywordsClass() is deprecated,'
                . ' use SqlitePlatform::createReservedKeywordsList() instead.',
        );

        return Keywords\SQLiteKeywords::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getPostAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $table = $diff->getOldTable();

        if (! $table instanceof Table) {
            throw new Exception(
                'Sqlite platform requires for alter table the table diff with reference to original table schema',
            );
        }

        $sql       = [];
        $tableName = $diff->getNewName();

        if ($tableName === false) {
            $tableName = $diff->getName($this);
        }

        foreach ($this->getIndexesInAlteredTable($diff, $table) as $index) {
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
            return sprintf('%s LIMIT -1 OFFSET %d', $query, $offset);
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
        $tableName = $this->emulateSchemaNamespacing($tableName);

        return $tableName;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     *
     * Sqlite Platform emulates schema by underscoring each dot and generating tables
     * into the default database.
     *
     * This hack is implemented to be able to use SQLite as testdriver when
     * using schema supporting databases.
     */
    public function canEmulateSchemas()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4805',
            'SqlitePlatform::canEmulateSchemas() is deprecated.',
        );

        return $this->schemaEmulationEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateTablesSQL(array $tables): array
    {
        $sql = [];

        foreach ($tables as $table) {
            $sql = array_merge($sql, $this->getCreateTableSQL($table));
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateIndexSQL(Index $index, $table)
    {
        if ($table instanceof Table) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4798',
                'Passing $table as a Table object to %s is deprecated. Pass it as a quoted name instead.',
                __METHOD__,
            );

            $table = $table->getQuotedName($this);
        }

        $name    = $index->getQuotedName($this);
        $columns = $index->getColumns();

        if (count($columns) === 0) {
            throw new InvalidArgumentException(sprintf(
                'Incomplete or invalid index definition %s on table %s',
                $name,
                $table,
            ));
        }

        if ($index->isPrimary()) {
            return $this->getCreatePrimaryKeySQL($index, $table);
        }

        if (strpos($table, '.') !== false) {
            [$schema, $table] = explode('.', $table, 2);
            $name             = $schema . '.' . $name;
        }

        $query  = 'CREATE ' . $this->getCreateIndexSQLFlags($index) . 'INDEX ' . $name . ' ON ' . $table;
        $query .= ' (' . $this->getIndexFieldDeclarationListSQL($index) . ')' . $this->getPartialIndexSQL($index);

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getDropTablesSQL(array $tables): array
    {
        $sql = [];

        foreach ($tables as $table) {
            $sql[] = $this->getDropTableSQL($table->getQuotedName($this));
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatePrimaryKeySQL(Index $index, $table)
    {
        throw new Exception('Sqlite platform does not support alter primary key.');
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateForeignKeySQL(ForeignKeyConstraint $foreignKey, $table)
    {
        throw new Exception('Sqlite platform does not support alter foreign key.');
    }

    /**
     * {@inheritDoc}
     */
    public function getDropForeignKeySQL($foreignKey, $table)
    {
        throw new Exception('Sqlite platform does not support alter foreign key.');
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function getCreateConstraintSQL(Constraint $constraint, $table)
    {
        throw new Exception('Sqlite platform does not support alter constraint.');
    }

    /**
     * {@inheritDoc}
     *
     * @param int|null $createFlags
     * @phpstan-param int-mask-of<AbstractPlatform::CREATE_*>|null $createFlags
     */
    public function getCreateTableSQL(Table $table, $createFlags = null)
    {
        $createFlags = $createFlags ?? self::CREATE_INDEXES | self::CREATE_FOREIGNKEYS;

        return parent::getCreateTableSQL($table, $createFlags);
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
        $table = $this->emulateSchemaNamespacing($table);

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

        $table = $diff->getOldTable();

        if (! $table instanceof Table) {
            throw new Exception(
                'Sqlite platform requires for alter table the table diff with reference to original table schema',
            );
        }

        $columns        = [];
        $oldColumnNames = [];
        $newColumnNames = [];
        $columnSql      = [];

        foreach ($table->getColumns() as $columnName => $column) {
            $columnName                  = strtolower($columnName);
            $columns[$columnName]        = $column;
            $oldColumnNames[$columnName] = $newColumnNames[$columnName] = $column->getQuotedName($this);
        }

        foreach ($diff->getDroppedColumns() as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnName = strtolower($column->getName());
            if (! isset($columns[$columnName])) {
                continue;
            }

            unset(
                $columns[$columnName],
                $oldColumnNames[$columnName],
                $newColumnNames[$columnName],
            );
        }

        foreach ($diff->getRenamedColumns() as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            $oldColumnName = strtolower($oldColumnName);

            $columns = $this->replaceColumn(
                $table->getName(),
                $columns,
                $oldColumnName,
                $column,
            );

            if (! isset($newColumnNames[$oldColumnName])) {
                continue;
            }

            $newColumnNames[$oldColumnName] = $column->getQuotedName($this);
        }

        foreach ($diff->getModifiedColumns() as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            $oldColumn = $columnDiff->getOldColumn() ?? $columnDiff->getOldColumnName();

            $oldColumnName = strtolower($oldColumn->getName());

            $columns = $this->replaceColumn(
                $table->getName(),
                $columns,
                $oldColumnName,
                $columnDiff->getNewColumn(),
            );

            if (! isset($newColumnNames[$oldColumnName])) {
                continue;
            }

            $newColumnNames[$oldColumnName] = $columnDiff->getNewColumn()->getQuotedName($this);
        }

        foreach ($diff->getAddedColumns() as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columns[strtolower($column->getName())] = $column;
        }

        $sql      = [];
        $tableSql = [];
        if (! $this->onSchemaAlterTable($diff, $tableSql)) {
            $tableName = $table->getName();
            if (strpos($tableName, '.') !== false) {
                [, $tableName] = explode('.', $tableName, 2);
            }

            $dataTable = new Table('__temp__' . $tableName);

            $newTable = new Table(
                $table->getQuotedName($this),
                $columns,
                $this->getPrimaryIndexInAlteredTable($diff, $table),
                [],
                $this->getForeignKeysInAlteredTable($diff, $table),
                $table->getOptions(),
            );
            $newTable->addOption('alter', true);

            $sql = $this->getPreAlterTableIndexForeignKeySQL($diff);

            $sql[] = sprintf(
                'CREATE TEMPORARY TABLE %s AS SELECT %s FROM %s',
                $dataTable->getQuotedName($this),
                implode(', ', $oldColumnNames),
                $table->getQuotedName($this),
            );
            $sql[] = $this->getDropTableSQL($table);

            $sql   = array_merge($sql, $this->getCreateTableSQL($newTable));
            $sql[] = sprintf(
                'INSERT INTO %s (%s) SELECT %s FROM %s',
                $newTable->getQuotedName($this),
                implode(', ', $newColumnNames),
                implode(', ', $oldColumnNames),
                $dataTable->getQuotedName($this),
            );
            $sql[] = $this->getDropTableSQL($dataTable->getQuotedName($this));

            $newName = $diff->getNewName();

            if ($newName !== false) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5663',
                    'Generation of "rename table" SQL using %s is deprecated. Use getRenameTableSQL() instead.',
                    __METHOD__,
                );

                $sql[] = sprintf(
                    'ALTER TABLE %s RENAME TO %s',
                    $newTable->getQuotedName($this),
                    $newName->getQuotedName($this),
                );
            }

            $sql = array_merge($sql, $this->getPostAlterTableIndexForeignKeySQL($diff));
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    /**
     * Replace the column with the given name with the new column.
     *
     * @param string               $tableName
     * @param array<string,Column> $columns
     * @param string               $columnName
     *
     * @return array<string,Column>
     *
     * @throws Exception
     */
    private function replaceColumn($tableName, array $columns, $columnName, Column $column): array
    {
        $keys  = array_keys($columns);
        $index = array_search($columnName, $keys, true);

        if ($index === false) {
            throw SchemaException::columnDoesNotExist($columnName, $tableName);
        }

        $values = array_values($columns);

        $keys[$index]   = strtolower($column->getName());
        $values[$index] = $column;

        return array_combine($keys, $values);
    }

    /**
     * @return string[]|false
     *
     * @throws Exception
     */
    private function getSimpleAlterTableSQL(TableDiff $diff)
    {
        // Suppress changes on integer type autoincrement columns.
        foreach ($diff->getModifiedColumns() as $columnDiff) {
            $oldColumn = $columnDiff->getOldColumn();

            if ($oldColumn === null) {
                continue;
            }

            $newColumn = $columnDiff->getNewColumn();

            if (! $newColumn->getAutoincrement() || ! $newColumn->getType() instanceof IntegerType) {
                continue;
            }

            $oldColumnName = $oldColumn->getName();

            if (! $columnDiff->hasTypeChanged() && $columnDiff->hasUnsignedChanged()) {
                unset($diff->changedColumns[$oldColumnName]);

                continue;
            }

            $fromColumnType = $oldColumn->getType();

            if (! ($fromColumnType instanceof Types\SmallIntType) && ! ($fromColumnType instanceof Types\BigIntType)) {
                continue;
            }

            unset($diff->changedColumns[$oldColumnName]);
        }

        if (
            count($diff->getModifiedColumns()) > 0
            || count($diff->getDroppedColumns()) > 0
            || count($diff->getRenamedColumns()) > 0
            || count($diff->getAddedIndexes()) > 0
            || count($diff->getModifiedIndexes()) > 0
            || count($diff->getDroppedIndexes()) > 0
            || count($diff->getRenamedIndexes()) > 0
            || count($diff->getAddedForeignKeys()) > 0
            || count($diff->getModifiedForeignKeys()) > 0
            || count($diff->getDroppedForeignKeys()) > 0
        ) {
            return false;
        }

        $table = $diff->getOldTable() ?? $diff->getName($this);

        $sql       = [];
        $tableSql  = [];
        $columnSql = [];

        foreach ($diff->getAddedColumns() as $column) {
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
            if ($type instanceof Types\StringType) {
                $definition['length'] ??= 255;
            }

            $sql[] = 'ALTER TABLE ' . $table->getQuotedName($this) . ' ADD COLUMN '
                . $this->getColumnDeclarationSQL($definition['name'], $definition);
        }

        if (! $this->onSchemaAlterTable($diff, $tableSql)) {
            if ($diff->newName !== false) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5663',
                    'Generation of SQL that renames a table using %s is deprecated.'
                        . ' Use getRenameTableSQL() instead.',
                    __METHOD__,
                );

                $newTable = new Identifier($diff->newName);

                $sql[] = 'ALTER TABLE ' . $table->getQuotedName($this) . ' RENAME TO '
                    . $newTable->getQuotedName($this);
            }
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    /** @return string[] */
    private function getColumnNamesInAlteredTable(TableDiff $diff, Table $fromTable): array
    {
        $columns = [];

        foreach ($fromTable->getColumns() as $columnName => $column) {
            $columns[strtolower($columnName)] = $column->getName();
        }

        foreach ($diff->getDroppedColumns() as $column) {
            $columnName = strtolower($column->getName());
            if (! isset($columns[$columnName])) {
                continue;
            }

            unset($columns[$columnName]);
        }

        foreach ($diff->getRenamedColumns() as $oldColumnName => $column) {
            $columnName                          = $column->getName();
            $columns[strtolower($oldColumnName)] = $columnName;
            $columns[strtolower($columnName)]    = $columnName;
        }

        foreach ($diff->getModifiedColumns() as $columnDiff) {
            $oldColumn = $columnDiff->getOldColumn() ?? $columnDiff->getOldColumnName();

            $oldColumnName                       = $oldColumn->getName();
            $newColumnName                       = $columnDiff->getNewColumn()->getName();
            $columns[strtolower($oldColumnName)] = $newColumnName;
            $columns[strtolower($newColumnName)] = $newColumnName;
        }

        foreach ($diff->getAddedColumns() as $column) {
            $columnName                       = $column->getName();
            $columns[strtolower($columnName)] = $columnName;
        }

        return $columns;
    }

    /** @return Index[] */
    private function getIndexesInAlteredTable(TableDiff $diff, Table $fromTable): array
    {
        $indexes     = $fromTable->getIndexes();
        $columnNames = $this->getColumnNamesInAlteredTable($diff, $fromTable);

        foreach ($indexes as $key => $index) {
            foreach ($diff->getRenamedIndexes() as $oldIndexName => $renamedIndex) {
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
                $index->getFlags(),
            );
        }

        foreach ($diff->getDroppedIndexes() as $index) {
            $indexName = strtolower($index->getName());
            if (strlen($indexName) === 0 || ! isset($indexes[$indexName])) {
                continue;
            }

            unset($indexes[$indexName]);
        }

        foreach (
            array_merge(
                $diff->getModifiedIndexes(),
                $diff->getAddedIndexes(),
                $diff->getRenamedIndexes(),
            ) as $index
        ) {
            $indexName = strtolower($index->getName());
            if (strlen($indexName) > 0) {
                $indexes[$indexName] = $index;
            } else {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    /** @return ForeignKeyConstraint[] */
    private function getForeignKeysInAlteredTable(TableDiff $diff, Table $fromTable): array
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
                $constraint->getOptions(),
            );
        }

        foreach ($diff->getDroppedForeignKeys() as $constraint) {
            if (! $constraint instanceof ForeignKeyConstraint) {
                $constraint = new Identifier($constraint);
            }

            $constraintName = strtolower($constraint->getName());
            if (strlen($constraintName) === 0 || ! isset($foreignKeys[$constraintName])) {
                continue;
            }

            unset($foreignKeys[$constraintName]);
        }

        foreach (array_merge($diff->getModifiedForeignKeys(), $diff->getAddedForeignKeys()) as $constraint) {
            $constraintName = strtolower($constraint->getName());
            if (strlen($constraintName) > 0) {
                $foreignKeys[$constraintName] = $constraint;
            } else {
                $foreignKeys[] = $constraint;
            }
        }

        return $foreignKeys;
    }

    /** @return Index[] */
    private function getPrimaryIndexInAlteredTable(TableDiff $diff, Table $fromTable): array
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

    public function createSchemaManager(Connection $connection): SqliteSchemaManager
    {
        return new SqliteSchemaManager($connection, $this);
    }
}
