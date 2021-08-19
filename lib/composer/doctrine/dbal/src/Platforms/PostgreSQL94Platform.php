<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\Type;
use UnexpectedValueException;

use function array_diff;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function sprintf;
use function strpos;
use function strtolower;
use function trim;

/**
 * Provides the behavior, features and SQL dialect of the PostgreSQL 9.4+ database platform.
 */
class PostgreSQL94Platform extends AbstractPlatform
{
    /** @var bool */
    private $useBooleanTrueFalseStrings = true;

    /** @var string[][] PostgreSQL booleans literals */
    private $booleanLiterals = [
        'true' => [
            't',
            'true',
            'y',
            'yes',
            'on',
            '1',
        ],
        'false' => [
            'f',
            'false',
            'n',
            'no',
            'off',
            '0',
        ],
    ];

    /**
     * PostgreSQL has different behavior with some drivers
     * with regard to how booleans have to be handled.
     *
     * Enables use of 'true'/'false' or otherwise 1 and 0 instead.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setUseBooleanTrueFalseStrings($flag)
    {
        $this->useBooleanTrueFalseStrings = (bool) $flag;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubstringExpression($string, $start, $length = null)
    {
        if ($length === null) {
            return 'SUBSTRING(' . $string . ' FROM ' . $start . ')';
        }

        return 'SUBSTRING(' . $string . ' FROM ' . $start . ' FOR ' . $length . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getNowExpression()
    {
        return 'LOCALTIMESTAMP(0)';
    }

    /**
     * {@inheritDoc}
     */
    public function getRegexpExpression()
    {
        return 'SIMILAR TO';
    }

    /**
     * {@inheritDoc}
     */
    public function getLocateExpression($str, $substr, $startPos = false)
    {
        if ($startPos !== false) {
            $str = $this->getSubstringExpression($str, $startPos);

            return 'CASE WHEN (POSITION(' . $substr . ' IN ' . $str . ') = 0) THEN 0'
                . ' ELSE (POSITION(' . $substr . ' IN ' . $str . ') + ' . ($startPos - 1) . ') END';
        }

        return 'POSITION(' . $substr . ' IN ' . $str . ')';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit)
    {
        if ($unit === DateIntervalUnit::QUARTER) {
            $interval *= 3;
            $unit      = DateIntervalUnit::MONTH;
        }

        return '(' . $date . ' ' . $operator . ' (' . $interval . " || ' " . $unit . "')::interval)";
    }

    /**
     * {@inheritDoc}
     */
    public function getDateDiffExpression($date1, $date2)
    {
        return '(DATE(' . $date1 . ')-DATE(' . $date2 . '))';
    }

    public function getCurrentDatabaseExpression(): string
    {
        return 'CURRENT_DATABASE()';
    }

    /**
     * {@inheritDoc}
     */
    public function supportsSequences()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsSchemas()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSchemaName()
    {
        return 'public';
    }

    /**
     * {@inheritDoc}
     */
    public function supportsIdentityColumns()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPartialIndexes()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function usesSequenceEmulatedIdentityColumns()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentitySequenceName($tableName, $columnName)
    {
        return $tableName . '_' . $columnName . '_seq';
    }

    /**
     * {@inheritDoc}
     */
    public function supportsCommentOnStatement()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function hasNativeGuidType()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getListDatabasesSQL()
    {
        return 'SELECT datname FROM pg_database';
    }

    /**
     * {@inheritDoc}
     */
    public function getListNamespacesSQL()
    {
        return "SELECT schema_name AS nspname
                FROM   information_schema.schemata
                WHERE  schema_name NOT LIKE 'pg\_%'
                AND    schema_name != 'information_schema'";
    }

    /**
     * {@inheritDoc}
     */
    public function getListSequencesSQL($database)
    {
        return "SELECT sequence_name AS relname,
                       sequence_schema AS schemaname
                FROM   information_schema.sequences
                WHERE  sequence_schema NOT LIKE 'pg\_%'
                AND    sequence_schema != 'information_schema'";
    }

    /**
     * {@inheritDoc}
     */
    public function getListTablesSQL()
    {
        return "SELECT quote_ident(table_name) AS table_name,
                       table_schema AS schema_name
                FROM   information_schema.tables
                WHERE  table_schema NOT LIKE 'pg\_%'
                AND    table_schema != 'information_schema'
                AND    table_name != 'geometry_columns'
                AND    table_name != 'spatial_ref_sys'
                AND    table_type != 'VIEW'";
    }

    /**
     * {@inheritDoc}
     */
    public function getListViewsSQL($database)
    {
        return 'SELECT quote_ident(table_name) AS viewname,
                       table_schema AS schemaname,
                       view_definition AS definition
                FROM   information_schema.views
                WHERE  view_definition IS NOT NULL';
    }

    /**
     * @param string      $table
     * @param string|null $database
     *
     * @return string
     */
    public function getListTableForeignKeysSQL($table, $database = null)
    {
        return 'SELECT quote_ident(r.conname) as conname, pg_catalog.pg_get_constraintdef(r.oid, true) as condef
                  FROM pg_catalog.pg_constraint r
                  WHERE r.conrelid =
                  (
                      SELECT c.oid
                      FROM pg_catalog.pg_class c, pg_catalog.pg_namespace n
                      WHERE ' . $this->getTableWhereClause($table) . " AND n.oid = c.relnamespace
                  )
                  AND r.contype = 'f'";
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
    public function getListTableConstraintsSQL($table)
    {
        $table = new Identifier($table);
        $table = $this->quoteStringLiteral($table->getName());

        return sprintf(
            <<<'SQL'
SELECT
    quote_ident(relname) as relname
FROM
    pg_class
WHERE oid IN (
    SELECT indexrelid
    FROM pg_index, pg_class
    WHERE pg_class.relname = %s
        AND pg_class.oid = pg_index.indrelid
        AND (indisunique = 't' OR indisprimary = 't')
    )
SQL
            ,
            $table
        );
    }

    /**
     * {@inheritDoc}
     *
     * @link http://ezcomponents.org/docs/api/trunk/DatabaseSchema/ezcDbSchemaPgsqlReader.html
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        return 'SELECT quote_ident(relname) as relname, pg_index.indisunique, pg_index.indisprimary,
                       pg_index.indkey, pg_index.indrelid,
                       pg_get_expr(indpred, indrelid) AS where
                 FROM pg_class, pg_index
                 WHERE oid IN (
                    SELECT indexrelid
                    FROM pg_index si, pg_class sc, pg_namespace sn
                    WHERE ' . $this->getTableWhereClause($table, 'sc', 'sn') . '
                    AND sc.oid=si.indrelid AND sc.relnamespace = sn.oid
                 ) AND pg_index.indexrelid = oid';
    }

    /**
     * @param string $table
     * @param string $classAlias
     * @param string $namespaceAlias
     *
     * @return string
     */
    private function getTableWhereClause($table, $classAlias = 'c', $namespaceAlias = 'n')
    {
        $whereClause = $namespaceAlias . ".nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast') AND ";
        if (strpos($table, '.') !== false) {
            [$schema, $table] = explode('.', $table);
            $schema           = $this->quoteStringLiteral($schema);
        } else {
            $schema = 'ANY(current_schemas(false))';
        }

        $table = new Identifier($table);
        $table = $this->quoteStringLiteral($table->getName());

        return $whereClause . sprintf(
            '%s.relname = %s AND %s.nspname = %s',
            $classAlias,
            $table,
            $namespaceAlias,
            $schema
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        return "SELECT
                    a.attnum,
                    quote_ident(a.attname) AS field,
                    t.typname AS type,
                    format_type(a.atttypid, a.atttypmod) AS complete_type,
                    (SELECT tc.collcollate FROM pg_catalog.pg_collation tc WHERE tc.oid = a.attcollation) AS collation,
                    (SELECT t1.typname FROM pg_catalog.pg_type t1 WHERE t1.oid = t.typbasetype) AS domain_type,
                    (SELECT format_type(t2.typbasetype, t2.typtypmod) FROM
                      pg_catalog.pg_type t2 WHERE t2.typtype = 'd' AND t2.oid = a.atttypid) AS domain_complete_type,
                    a.attnotnull AS isnotnull,
                    (SELECT 't'
                     FROM pg_index
                     WHERE c.oid = pg_index.indrelid
                        AND pg_index.indkey[0] = a.attnum
                        AND pg_index.indisprimary = 't'
                    ) AS pri,
                    (SELECT pg_get_expr(adbin, adrelid)
                     FROM pg_attrdef
                     WHERE c.oid = pg_attrdef.adrelid
                        AND pg_attrdef.adnum=a.attnum
                    ) AS default,
                    (SELECT pg_description.description
                        FROM pg_description WHERE pg_description.objoid = c.oid AND a.attnum = pg_description.objsubid
                    ) AS comment
                    FROM pg_attribute a, pg_class c, pg_type t, pg_namespace n
                    WHERE " . $this->getTableWhereClause($table, 'c', 'n') . '
                        AND a.attnum > 0
                        AND a.attrelid = c.oid
                        AND a.atttypid = t.oid
                        AND n.oid = c.relnamespace
                    ORDER BY a.attnum';
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
    public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey)
    {
        $query = '';

        if ($foreignKey->hasOption('match')) {
            $query .= ' MATCH ' . $foreignKey->getOption('match');
        }

        $query .= parent::getAdvancedForeignKeyOptionsSQL($foreignKey);

        if ($foreignKey->hasOption('deferrable') && $foreignKey->getOption('deferrable') !== false) {
            $query .= ' DEFERRABLE';
        } else {
            $query .= ' NOT DEFERRABLE';
        }

        if (
            ($foreignKey->hasOption('feferred') && $foreignKey->getOption('feferred') !== false)
            || ($foreignKey->hasOption('deferred') && $foreignKey->getOption('deferred') !== false)
        ) {
            $query .= ' INITIALLY DEFERRED';
        } else {
            $query .= ' INITIALLY IMMEDIATE';
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        $sql         = [];
        $commentsSQL = [];
        $columnSql   = [];

        foreach ($diff->addedColumns as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $query = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray());
            $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . $query;

            $comment = $this->getColumnComment($column);

            if ($comment === null || $comment === '') {
                continue;
            }

            $commentsSQL[] = $this->getCommentOnColumnSQL(
                $diff->getName($this)->getQuotedName($this),
                $column->getQuotedName($this),
                $comment
            );
        }

        foreach ($diff->removedColumns as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            $query = 'DROP ' . $column->getQuotedName($this);
            $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . $query;
        }

        foreach ($diff->changedColumns as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            if ($this->isUnchangedBinaryColumn($columnDiff)) {
                continue;
            }

            $oldColumnName = $columnDiff->getOldColumnName()->getQuotedName($this);
            $column        = $columnDiff->column;

            if (
                $columnDiff->hasChanged('type')
                || $columnDiff->hasChanged('precision')
                || $columnDiff->hasChanged('scale')
                || $columnDiff->hasChanged('fixed')
            ) {
                $type = $column->getType();

                // SERIAL/BIGSERIAL are not "real" types and we can't alter a column to that type
                $columnDefinition                  = $column->toArray();
                $columnDefinition['autoincrement'] = false;

                // here was a server version check before, but DBAL API does not support this anymore.
                $query = 'ALTER ' . $oldColumnName . ' TYPE ' . $type->getSQLDeclaration($columnDefinition, $this);
                $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . $query;
            }

            if ($columnDiff->hasChanged('default') || $this->typeChangeBreaksDefaultValue($columnDiff)) {
                $defaultClause = $column->getDefault() === null
                    ? ' DROP DEFAULT'
                    : ' SET' . $this->getDefaultValueDeclarationSQL($column->toArray());
                $query         = 'ALTER ' . $oldColumnName . $defaultClause;
                $sql[]         = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . $query;
            }

            if ($columnDiff->hasChanged('notnull')) {
                $query = 'ALTER ' . $oldColumnName . ' ' . ($column->getNotnull() ? 'SET' : 'DROP') . ' NOT NULL';
                $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . $query;
            }

            if ($columnDiff->hasChanged('autoincrement')) {
                if ($column->getAutoincrement()) {
                    // add autoincrement
                    $seqName = $this->getIdentitySequenceName($diff->name, $oldColumnName);

                    $sql[] = 'CREATE SEQUENCE ' . $seqName;
                    $sql[] = "SELECT setval('" . $seqName . "', (SELECT MAX(" . $oldColumnName . ') FROM '
                        . $diff->getName($this)->getQuotedName($this) . '))';
                    $query = 'ALTER ' . $oldColumnName . " SET DEFAULT nextval('" . $seqName . "')";
                    $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . $query;
                } else {
                    // Drop autoincrement, but do NOT drop the sequence. It might be re-used by other tables or have
                    $query = 'ALTER ' . $oldColumnName . ' DROP DEFAULT';
                    $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . $query;
                }
            }

            $newComment = $this->getColumnComment($column);
            $oldComment = $this->getOldColumnComment($columnDiff);

            if (
                $columnDiff->hasChanged('comment')
                || ($columnDiff->fromColumn !== null && $oldComment !== $newComment)
            ) {
                $commentsSQL[] = $this->getCommentOnColumnSQL(
                    $diff->getName($this)->getQuotedName($this),
                    $column->getQuotedName($this),
                    $newComment
                );
            }

            if (! $columnDiff->hasChanged('length')) {
                continue;
            }

            $query = 'ALTER ' . $oldColumnName . ' TYPE '
                . $column->getType()->getSQLDeclaration($column->toArray(), $this);
            $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . $query;
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            $oldColumnName = new Identifier($oldColumnName);

            $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) .
                ' RENAME COLUMN ' . $oldColumnName->getQuotedName($this) . ' TO ' . $column->getQuotedName($this);
        }

        $tableSql = [];

        if (! $this->onSchemaAlterTable($diff, $tableSql)) {
            $sql = array_merge($sql, $commentsSQL);

            $newName = $diff->getNewName();

            if ($newName !== false) {
                $sql[] = sprintf(
                    'ALTER TABLE %s RENAME TO %s',
                    $diff->getName($this)->getQuotedName($this),
                    $newName->getQuotedName($this)
                );
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
     * Checks whether a given column diff is a logically unchanged binary type column.
     *
     * Used to determine whether a column alteration for a binary type column can be skipped.
     * Doctrine's {@link BinaryType} and {@link BlobType} are mapped to the same database column type on this platform
     * as this platform does not have a native VARBINARY/BINARY column type. Therefore the comparator
     * might detect differences for binary type columns which do not have to be propagated
     * to database as there actually is no difference at database level.
     *
     * @param ColumnDiff $columnDiff The column diff to check against.
     *
     * @return bool True if the given column diff is an unchanged binary type column, false otherwise.
     */
    private function isUnchangedBinaryColumn(ColumnDiff $columnDiff)
    {
        $columnType = $columnDiff->column->getType();

        if (! $columnType instanceof BinaryType && ! $columnType instanceof BlobType) {
            return false;
        }

        $fromColumn = $columnDiff->fromColumn instanceof Column ? $columnDiff->fromColumn : null;

        if ($fromColumn !== null) {
            $fromColumnType = $fromColumn->getType();

            if (! $fromColumnType instanceof BinaryType && ! $fromColumnType instanceof BlobType) {
                return false;
            }

            return count(array_diff($columnDiff->changedProperties, ['type', 'length', 'fixed'])) === 0;
        }

        if ($columnDiff->hasChanged('type')) {
            return false;
        }

        return count(array_diff($columnDiff->changedProperties, ['length', 'fixed'])) === 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName)
    {
        if (strpos($tableName, '.') !== false) {
            [$schema]     = explode('.', $tableName);
            $oldIndexName = $schema . '.' . $oldIndexName;
        }

        return ['ALTER INDEX ' . $oldIndexName . ' RENAME TO ' . $index->getQuotedName($this)];
    }

    /**
     * {@inheritdoc}
     */
    public function getCommentOnColumnSQL($tableName, $columnName, $comment)
    {
        $tableName  = new Identifier($tableName);
        $columnName = new Identifier($columnName);
        $comment    = $comment === null ? 'NULL' : $this->quoteStringLiteral($comment);

        return sprintf(
            'COMMENT ON COLUMN %s.%s IS %s',
            $tableName->getQuotedName($this),
            $columnName->getQuotedName($this),
            $comment
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateSequenceSQL(Sequence $sequence)
    {
        return 'CREATE SEQUENCE ' . $sequence->getQuotedName($this) .
            ' INCREMENT BY ' . $sequence->getAllocationSize() .
            ' MINVALUE ' . $sequence->getInitialValue() .
            ' START ' . $sequence->getInitialValue() .
            $this->getSequenceCacheSQL($sequence);
    }

    /**
     * {@inheritDoc}
     */
    public function getAlterSequenceSQL(Sequence $sequence)
    {
        return 'ALTER SEQUENCE ' . $sequence->getQuotedName($this) .
            ' INCREMENT BY ' . $sequence->getAllocationSize() .
            $this->getSequenceCacheSQL($sequence);
    }

    /**
     * Cache definition for sequences
     *
     * @return string
     */
    private function getSequenceCacheSQL(Sequence $sequence)
    {
        if ($sequence->getCache() > 1) {
            return ' CACHE ' . $sequence->getCache();
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getDropSequenceSQL($sequence)
    {
        if ($sequence instanceof Sequence) {
            $sequence = $sequence->getQuotedName($this);
        }

        return 'DROP SEQUENCE ' . $sequence . ' CASCADE';
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateSchemaSQL($schemaName)
    {
        return 'CREATE SCHEMA ' . $schemaName;
    }

    /**
     * {@inheritDoc}
     */
    public function getDropForeignKeySQL($foreignKey, $table)
    {
        return $this->getDropConstraintSQL($foreignKey, $table);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCreateTableSQL($name, array $columns, array $options = [])
    {
        $queryFields = $this->getColumnDeclarationListSQL($columns);

        if (isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns   = array_unique(array_values($options['primary']));
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE TABLE ' . $name . ' (' . $queryFields . ')';

        $sql = [$query];

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] as $index) {
                $sql[] = $this->getCreateIndexSQL($index, $name);
            }
        }

        if (isset($options['foreignKeys'])) {
            foreach ((array) $options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $name);
            }
        }

        return $sql;
    }

    /**
     * Converts a single boolean value.
     *
     * First converts the value to its native PHP boolean type
     * and passes it to the given callback function to be reconverted
     * into any custom representation.
     *
     * @param mixed    $value    The value to convert.
     * @param callable $callback The callback function to use for converting the real boolean value.
     *
     * @return mixed
     *
     * @throws UnexpectedValueException
     */
    private function convertSingleBooleanValue($value, $callback)
    {
        if ($value === null) {
            return $callback(null);
        }

        if (is_bool($value) || is_numeric($value)) {
            return $callback((bool) $value);
        }

        if (! is_string($value)) {
            return $callback(true);
        }

        /**
         * Better safe than sorry: http://php.net/in_array#106319
         */
        if (in_array(strtolower(trim($value)), $this->booleanLiterals['false'], true)) {
            return $callback(false);
        }

        if (in_array(strtolower(trim($value)), $this->booleanLiterals['true'], true)) {
            return $callback(true);
        }

        throw new UnexpectedValueException("Unrecognized boolean literal '${value}'");
    }

    /**
     * Converts one or multiple boolean values.
     *
     * First converts the value(s) to their native PHP boolean type
     * and passes them to the given callback function to be reconverted
     * into any custom representation.
     *
     * @param mixed    $item     The value(s) to convert.
     * @param callable $callback The callback function to use for converting the real boolean value(s).
     *
     * @return mixed
     */
    private function doConvertBooleans($item, $callback)
    {
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                $item[$key] = $this->convertSingleBooleanValue($value, $callback);
            }

            return $item;
        }

        return $this->convertSingleBooleanValue($item, $callback);
    }

    /**
     * {@inheritDoc}
     *
     * Postgres wants boolean values converted to the strings 'true'/'false'.
     */
    public function convertBooleans($item)
    {
        if (! $this->useBooleanTrueFalseStrings) {
            return parent::convertBooleans($item);
        }

        return $this->doConvertBooleans(
            $item,
            static function ($boolean) {
                if ($boolean === null) {
                    return 'NULL';
                }

                return $boolean === true ? 'true' : 'false';
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function convertBooleansToDatabaseValue($item)
    {
        if (! $this->useBooleanTrueFalseStrings) {
            return parent::convertBooleansToDatabaseValue($item);
        }

        return $this->doConvertBooleans(
            $item,
            static function ($boolean): ?int {
                return $boolean === null ? null : (int) $boolean;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromBoolean($item)
    {
        if (in_array(strtolower($item), $this->booleanLiterals['false'], true)) {
            return false;
        }

        return parent::convertFromBoolean($item);
    }

    /**
     * {@inheritDoc}
     */
    public function getSequenceNextValSQL($sequence)
    {
        return "SELECT NEXTVAL('" . $sequence . "')";
    }

    /**
     * {@inheritDoc}
     */
    public function getSetTransactionIsolationSQL($level)
    {
        return 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL '
            . $this->_getTransactionIsolationLevelSQL($level);
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
        if (! empty($column['autoincrement'])) {
            return 'SERIAL';
        }

        return 'INT';
    }

    /**
     * {@inheritDoc}
     */
    public function getBigIntTypeDeclarationSQL(array $column)
    {
        if (! empty($column['autoincrement'])) {
            return 'BIGSERIAL';
        }

        return 'BIGINT';
    }

    /**
     * {@inheritDoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $column)
    {
        if (! empty($column['autoincrement'])) {
            return 'SMALLSERIAL';
        }

        return 'SMALLINT';
    }

    /**
     * {@inheritDoc}
     */
    public function getGuidTypeDeclarationSQL(array $column)
    {
        return 'UUID';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTypeDeclarationSQL(array $column)
    {
        return 'TIMESTAMP(0) WITHOUT TIME ZONE';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTzTypeDeclarationSQL(array $column)
    {
        return 'TIMESTAMP(0) WITH TIME ZONE';
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
        return 'TIME(0) WITHOUT TIME ZONE';
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $column)
    {
        return '';
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
        return 'BYTEA';
    }

    /**
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $column)
    {
        return 'TEXT';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'postgresql';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTzFormatString()
    {
        return 'Y-m-d H:i:sO';
    }

    /**
     * {@inheritDoc}
     */
    public function getEmptyIdentityInsertSQL($quotedTableName, $quotedIdentifierColumnName)
    {
        return 'INSERT INTO ' . $quotedTableName . ' (' . $quotedIdentifierColumnName . ') VALUES (DEFAULT)';
    }

    /**
     * {@inheritDoc}
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        $tableIdentifier = new Identifier($tableName);
        $sql             = 'TRUNCATE ' . $tableIdentifier->getQuotedName($this);

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function getReadLockSQL()
    {
        return 'FOR SHARE';
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = [
            'bigint'           => 'bigint',
            'bigserial'        => 'bigint',
            'bool'             => 'boolean',
            'boolean'          => 'boolean',
            'bpchar'           => 'string',
            'bytea'            => 'blob',
            'char'             => 'string',
            'date'             => 'date',
            'datetime'         => 'datetime',
            'decimal'          => 'decimal',
            'double'           => 'float',
            'double precision' => 'float',
            'float'            => 'float',
            'float4'           => 'float',
            'float8'           => 'float',
            'inet'             => 'string',
            'int'              => 'integer',
            'int2'             => 'smallint',
            'int4'             => 'integer',
            'int8'             => 'bigint',
            'integer'          => 'integer',
            'interval'         => 'string',
            'json'             => 'json',
            'jsonb'            => 'json',
            'money'            => 'decimal',
            'numeric'          => 'decimal',
            'serial'           => 'integer',
            'serial4'          => 'integer',
            'serial8'          => 'bigint',
            'real'             => 'float',
            'smallint'         => 'smallint',
            'text'             => 'text',
            'time'             => 'time',
            'timestamp'        => 'datetime',
            'timestamptz'      => 'datetimetz',
            'timetz'           => 'time',
            'tsvector'         => 'text',
            'uuid'             => 'guid',
            'varchar'          => 'string',
            'year'             => 'date',
            '_varchar'         => 'string',
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
     * {@inheritdoc}
     */
    public function hasNativeJsonType()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getReservedKeywordsClass()
    {
        return Keywords\PostgreSQL94Keywords::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlobTypeDeclarationSQL(array $column)
    {
        return 'BYTEA';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValueDeclarationSQL($column)
    {
        if ($this->isSerialColumn($column)) {
            return '';
        }

        return parent::getDefaultValueDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsColumnCollation()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnCollationDeclarationSQL($collation)
    {
        return 'COLLATE ' . $this->quoteSingleIdentifier($collation);
    }

    /**
     * {@inheritdoc}
     */
    public function getJsonTypeDeclarationSQL(array $column)
    {
        if (! empty($column['jsonb'])) {
            return 'JSONB';
        }

        return 'JSON';
    }

    /**
     * @param mixed[] $column
     */
    private function isSerialColumn(array $column): bool
    {
        return isset($column['type'], $column['autoincrement'])
            && $column['autoincrement'] === true
            && $this->isNumericType($column['type']);
    }

    /**
     * Check whether the type of a column is changed in a way that invalidates the default value for the column
     */
    private function typeChangeBreaksDefaultValue(ColumnDiff $columnDiff): bool
    {
        if ($columnDiff->fromColumn === null) {
            return $columnDiff->hasChanged('type');
        }

        $oldTypeIsNumeric = $this->isNumericType($columnDiff->fromColumn->getType());
        $newTypeIsNumeric = $this->isNumericType($columnDiff->column->getType());

        // default should not be changed when switching between numeric types and the default comes from a sequence
        return $columnDiff->hasChanged('type')
            && ! ($oldTypeIsNumeric && $newTypeIsNumeric && $columnDiff->column->getAutoincrement());
    }

    private function isNumericType(Type $type): bool
    {
        return $type instanceof IntegerType || $type instanceof BigIntType;
    }

    private function getOldColumnComment(ColumnDiff $columnDiff): ?string
    {
        return $columnDiff->fromColumn !== null ? $this->getColumnComment($columnDiff->fromColumn) : null;
    }

    public function getListTableMetadataSQL(string $table, ?string $schema = null): string
    {
        if ($schema !== null) {
            $table = $schema . '.' . $table;
        }

        return sprintf(
            <<<'SQL'
SELECT obj_description(%s::regclass) AS table_comment;
SQL
            ,
            $this->quoteStringLiteral($table)
        );
    }
}
