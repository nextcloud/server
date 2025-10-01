<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\DB2SchemaManager;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\SQL\Builder\DefaultSelectSQLBuilder;
use Doctrine\DBAL\SQL\Builder\SelectSQLBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;

use function array_merge;
use function count;
use function current;
use function explode;
use function func_get_arg;
use function func_num_args;
use function implode;
use function sprintf;
use function strpos;

/**
 * Provides the behavior, features and SQL dialect of the IBM DB2 database platform of the oldest supported version.
 */
class DB2Platform extends AbstractPlatform
{
    /** @see https://www.ibm.com/docs/en/db2/11.5?topic=views-syscatcolumns */
    private const SYSCAT_COLUMNS_GENERATED_DEFAULT = 'D';

    /** @see https://www.ibm.com/docs/en/db2/11.5?topic=views-syscatindexes */
    private const SYSCAT_INDEXES_UNIQUERULE_PERMITS_DUPLICATES     = 'D';
    private const SYSCAT_INDEXES_UNIQUERULE_IMPLEMENTS_PRIMARY_KEY = 'P';

    /** @see https://www.ibm.com/docs/en/db2/11.5?topic=views-syscattabconst */
    private const SYSCAT_TABCONST_TYPE_PRIMARY_KEY = 'P';

    /** @see https://www.ibm.com/docs/en/db2/11.5?topic=views-syscatreferences */
    private const SYSCAT_REFERENCES_UPDATERULE_RESTRICT = 'R';
    private const SYSCAT_REFERENCES_DELETERULE_CASCADE  = 'C';
    private const SYSCAT_REFERENCES_DELETERULE_SET_NULL = 'N';
    private const SYSCAT_REFERENCES_DELETERULE_RESTRICT = 'R';

    /** @see https://www.ibm.com/docs/en/db2-for-zos/11?topic=tables-systables */
    private const SYSIBM_SYSTABLES_TYPE_TABLE = 'T';

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function getCharMaxLength(): int
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3263',
            '%s() is deprecated.',
            __METHOD__,
        );

        return 254;
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
            '%s() is deprecated.',
            __METHOD__,
        );

        return 32704;
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

        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function getVarcharTypeDeclarationSQL(array $column)
    {
        // for IBM DB2, the CHAR max length is less than VARCHAR default length
        if (! isset($column['length']) && ! empty($column['fixed'])) {
            $column['length'] = $this->getCharMaxLength();
        }

        return parent::getVarcharTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlobTypeDeclarationSQL(array $column)
    {
        // todo blob(n) with $column['length'];
        return 'BLOB(1M)';
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = [
            'bigint'    => Types::BIGINT,
            'binary'    => Types::BINARY,
            'blob'      => Types::BLOB,
            'character' => Types::STRING,
            'clob'      => Types::TEXT,
            'date'      => Types::DATE_MUTABLE,
            'decimal'   => Types::DECIMAL,
            'double'    => Types::FLOAT,
            'integer'   => Types::INTEGER,
            'real'      => Types::FLOAT,
            'smallint'  => Types::SMALLINT,
            'time'      => Types::TIME_MUTABLE,
            'timestamp' => Types::DATETIME_MUTABLE,
            'varbinary' => Types::BINARY,
            'varchar'   => Types::STRING,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function isCommentedDoctrineType(Type $doctrineType)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5058',
            '%s() is deprecated and will be removed in Doctrine DBAL 4.0. Use Type::requiresSQLCommentHint() instead.',
            __METHOD__,
        );

        if ($doctrineType->getName() === Types::BOOLEAN) {
            // We require a commented boolean type in order to distinguish between boolean and smallint
            // as both (have to) map to the same native type.
            return true;
        }

        return parent::isCommentedDoctrineType($doctrineType);
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
                'Relying on the default string column length on IBM DB2 is deprecated'
                    . ', specify the length explicitly.',
            );
        }

        return $fixed ? ($length > 0 ? 'CHAR(' . $length . ')' : 'CHAR(254)')
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
                'Relying on the default binary column length on IBM DB2 is deprecated'
                . ', specify the length explicitly.',
            );
        }

        return $this->getVarcharTypeDeclarationSQLSnippet($length, $fixed) . ' FOR BIT DATA';
    }

    /**
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $column)
    {
        // todo clob(n) with $column['length'];
        return 'CLOB(1M)';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4749',
            '%s() is deprecated. Identify platforms by their class.',
            __METHOD__,
        );

        return 'db2';
    }

    /**
     * {@inheritDoc}
     */
    public function getBooleanTypeDeclarationSQL(array $column)
    {
        return 'SMALLINT';
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
    protected function _getCommonIntegerTypeDeclarationSQL(array $column)
    {
        $autoinc = '';
        if (! empty($column['autoincrement'])) {
            $autoinc = ' GENERATED BY DEFAULT AS IDENTITY';
        }

        return $autoinc;
    }

    /**
     * {@inheritDoc}
     */
    public function getBitAndComparisonExpression($value1, $value2)
    {
        return 'BITAND(' . $value1 . ', ' . $value2 . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getBitOrComparisonExpression($value1, $value2)
    {
        return 'BITOR(' . $value1 . ', ' . $value2 . ')';
    }

    /**
     * {@inheritDoc}
     */
    protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit)
    {
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

        return $date . ' ' . $operator . ' ' . $interval . ' ' . $unit;
    }

    /**
     * {@inheritDoc}
     */
    public function getDateDiffExpression($date1, $date2)
    {
        return 'DAYS(' . $date1 . ') - DAYS(' . $date2 . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTypeDeclarationSQL(array $column)
    {
        if (isset($column['version']) && $column['version'] === true) {
            return 'TIMESTAMP(0) WITH DEFAULT';
        }

        return 'TIMESTAMP(0)';
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
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        $tableIdentifier = new Identifier($tableName);

        return 'TRUNCATE ' . $tableIdentifier->getQuotedName($this) . ' IMMEDIATE';
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * This code fragment is originally from the Zend_Db_Adapter_Db2 class, but has been edited.
     *
     * @param string $table
     * @param string $database
     *
     * @return string
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        $table = $this->quoteStringLiteral($table);

        // We do the funky subquery and join syscat.columns.default this crazy way because
        // as of db2 v10, the column is CLOB(64k) and the distinct operator won't allow a CLOB,
        // it wants shorter stuff like a varchar.
        return "
        SELECT
          cols.default,
          subq.*
        FROM (
               SELECT DISTINCT
                 c.tabschema,
                 c.tabname,
                 c.colname,
                 c.colno,
                 c.typename,
                 c.codepage,
                 c.nulls,
                 c.length,
                 c.scale,
                 c.identity,
                 tc.type AS tabconsttype,
                 c.remarks AS comment,
                 k.colseq,
                 CASE
                 WHEN c.generated = '" . self::SYSCAT_COLUMNS_GENERATED_DEFAULT . "' THEN 1
                 ELSE 0
                 END     AS autoincrement
               FROM syscat.columns c
                 LEFT JOIN (syscat.keycoluse k JOIN syscat.tabconst tc
                     ON (k.tabschema = tc.tabschema
                         AND k.tabname = tc.tabname
                         AND tc.type = '" . self::SYSCAT_TABCONST_TYPE_PRIMARY_KEY . "'))
                   ON (c.tabschema = k.tabschema
                       AND c.tabname = k.tabname
                       AND c.colname = k.colname)
               WHERE UPPER(c.tabname) = UPPER(" . $table . ')
               ORDER BY c.colno
             ) subq
          JOIN syscat.columns cols
            ON subq.tabschema = cols.tabschema
               AND subq.tabname = cols.tabname
               AND subq.colno = cols.colno
        ORDER BY subq.colno
        ';
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTablesSQL()
    {
        return "SELECT NAME FROM SYSIBM.SYSTABLES WHERE TYPE = '" . self::SYSIBM_SYSTABLES_TYPE_TABLE . "'"
            . ' AND CREATOR = CURRENT_USER';
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy.
     */
    public function getListViewsSQL($database)
    {
        return 'SELECT NAME, TEXT FROM SYSIBM.SYSVIEWS';
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTableIndexesSQL($table, $database = null)
    {
        $table = $this->quoteStringLiteral($table);

        return "SELECT   idx.INDNAME AS key_name,
                         idxcol.COLNAME AS column_name,
                         CASE
                             WHEN idx.UNIQUERULE = '" . self::SYSCAT_INDEXES_UNIQUERULE_IMPLEMENTS_PRIMARY_KEY . "'
                             THEN 1
                             ELSE 0
                         END AS primary,
                         CASE
                             WHEN idx.UNIQUERULE = '" . self::SYSCAT_INDEXES_UNIQUERULE_PERMITS_DUPLICATES . "'
                             THEN 1
                             ELSE 0
                         END AS non_unique
                FROM     SYSCAT.INDEXES AS idx
                JOIN     SYSCAT.INDEXCOLUSE AS idxcol
                ON       idx.INDSCHEMA = idxcol.INDSCHEMA AND idx.INDNAME = idxcol.INDNAME
                WHERE    idx.TABNAME = UPPER(" . $table . ')
                ORDER BY idxcol.COLSEQ ASC';
    }

    /**
     * @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon.
     *
     * {@inheritDoc}
     */
    public function getListTableForeignKeysSQL($table)
    {
        $table = $this->quoteStringLiteral($table);

        return "SELECT   fkcol.COLNAME AS local_column,
                         fk.REFTABNAME AS foreign_table,
                         pkcol.COLNAME AS foreign_column,
                         fk.CONSTNAME AS index_name,
                         CASE
                             WHEN fk.UPDATERULE = '" . self::SYSCAT_REFERENCES_UPDATERULE_RESTRICT . "' THEN 'RESTRICT'
                             ELSE NULL
                         END AS on_update,
                         CASE
                             WHEN fk.DELETERULE = '" . self::SYSCAT_REFERENCES_DELETERULE_CASCADE . "' THEN 'CASCADE'
                             WHEN fk.DELETERULE = '" . self::SYSCAT_REFERENCES_DELETERULE_SET_NULL . "' THEN 'SET NULL'
                             WHEN fk.DELETERULE = '" . self::SYSCAT_REFERENCES_DELETERULE_RESTRICT . "' THEN 'RESTRICT'
                             ELSE NULL
                         END AS on_delete
                FROM     SYSCAT.REFERENCES AS fk
                JOIN     SYSCAT.KEYCOLUSE AS fkcol
                ON       fk.CONSTNAME = fkcol.CONSTNAME
                AND      fk.TABSCHEMA = fkcol.TABSCHEMA
                AND      fk.TABNAME = fkcol.TABNAME
                JOIN     SYSCAT.KEYCOLUSE AS pkcol
                ON       fk.REFKEYNAME = pkcol.CONSTNAME
                AND      fk.REFTABSCHEMA = pkcol.TABSCHEMA
                AND      fk.REFTABNAME = pkcol.TABNAME
                WHERE    fk.TABNAME = UPPER(" . $table . ')
                ORDER BY fkcol.COLSEQ ASC';
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
            '%s() is deprecated.',
            __METHOD__,
        );

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function supportsCommentOnStatement()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentDateSQL()
    {
        return 'CURRENT DATE';
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentTimeSQL()
    {
        return 'CURRENT TIME';
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentTimestampSQL()
    {
        return 'CURRENT TIMESTAMP';
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function getIndexDeclarationSQL($name, Index $index)
    {
        // Index declaration in statements like CREATE TABLE is not supported.
        throw Exception::notSupported(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCreateTableSQL($name, array $columns, array $options = [])
    {
        $indexes = [];
        if (isset($options['indexes'])) {
            $indexes = $options['indexes'];
        }

        $options['indexes'] = [];

        $sqls = parent::_getCreateTableSQL($name, $columns, $options);

        foreach ($indexes as $definition) {
            $sqls[] = $this->getCreateIndexSQL($definition, $name);
        }

        return $sqls;
    }

    /**
     * {@inheritDoc}
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        $sql         = [];
        $columnSql   = [];
        $commentsSQL = [];

        $tableNameSQL = ($diff->getOldTable() ?? $diff->getName($this))->getQuotedName($this);

        $queryParts = [];
        foreach ($diff->getAddedColumns() as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnDef = $column->toArray();
            $queryPart = 'ADD COLUMN ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnDef);

            // Adding non-nullable columns to a table requires a default value to be specified.
            if (
                ! empty($columnDef['notnull']) &&
                ! isset($columnDef['default']) &&
                empty($columnDef['autoincrement'])
            ) {
                $queryPart .= ' WITH DEFAULT';
            }

            $queryParts[] = $queryPart;

            $comment = $this->getColumnComment($column);

            if ($comment === null || $comment === '') {
                continue;
            }

            $commentsSQL[] = $this->getCommentOnColumnSQL(
                $tableNameSQL,
                $column->getQuotedName($this),
                $comment,
            );
        }

        foreach ($diff->getDroppedColumns() as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            $queryParts[] =  'DROP COLUMN ' . $column->getQuotedName($this);
        }

        foreach ($diff->getModifiedColumns() as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            if ($columnDiff->hasCommentChanged()) {
                $commentsSQL[] = $this->getCommentOnColumnSQL(
                    $tableNameSQL,
                    $columnDiff->getNewColumn()->getQuotedName($this),
                    $this->getColumnComment($columnDiff->getNewColumn()),
                );
            }

            $this->gatherAlterColumnSQL(
                $tableNameSQL,
                $columnDiff,
                $sql,
                $queryParts,
            );
        }

        foreach ($diff->getRenamedColumns() as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            $oldColumnName = new Identifier($oldColumnName);

            $queryParts[] =  'RENAME COLUMN ' . $oldColumnName->getQuotedName($this) .
                ' TO ' . $column->getQuotedName($this);
        }

        $tableSql = [];

        if (! $this->onSchemaAlterTable($diff, $tableSql)) {
            if (count($queryParts) > 0) {
                $sql[] = 'ALTER TABLE ' . $tableNameSQL . ' ' . implode(' ', $queryParts);
            }

            // Some table alteration operations require a table reorganization.
            if (count($diff->getDroppedColumns()) > 0 || count($diff->getModifiedColumns()) > 0) {
                $sql[] = "CALL SYSPROC.ADMIN_CMD ('REORG TABLE " . $tableNameSQL . "')";
            }

            $sql = array_merge($sql, $commentsSQL);

            $newName = $diff->getNewName();

            if ($newName !== false) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5663',
                    'Generation of "rename table" SQL using %s() is deprecated. Use getRenameTableSQL() instead.',
                    __METHOD__,
                );

                $sql[] = sprintf(
                    'RENAME TABLE %s TO %s',
                    $tableNameSQL,
                    $newName->getQuotedName($this),
                );
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
    public function getRenameTableSQL(string $oldName, string $newName): array
    {
        return [
            sprintf('RENAME TABLE %s TO %s', $oldName, $newName),
        ];
    }

    /**
     * Gathers the table alteration SQL for a given column diff.
     *
     * @param string     $table      The table to gather the SQL for.
     * @param ColumnDiff $columnDiff The column diff to evaluate.
     * @param string[]   $sql        The sequence of table alteration statements to fill.
     * @param mixed[]    $queryParts The sequence of column alteration clauses to fill.
     */
    private function gatherAlterColumnSQL(
        string $table,
        ColumnDiff $columnDiff,
        array &$sql,
        array &$queryParts
    ): void {
        $alterColumnClauses = $this->getAlterColumnClausesSQL($columnDiff);

        if (empty($alterColumnClauses)) {
            return;
        }

        // If we have a single column alteration, we can append the clause to the main query.
        if (count($alterColumnClauses) === 1) {
            $queryParts[] = current($alterColumnClauses);

            return;
        }

        // We have multiple alterations for the same column,
        // so we need to trigger a complete ALTER TABLE statement
        // for each ALTER COLUMN clause.
        foreach ($alterColumnClauses as $alterColumnClause) {
            $sql[] = 'ALTER TABLE ' . $table . ' ' . $alterColumnClause;
        }
    }

    /**
     * Returns the ALTER COLUMN SQL clauses for altering a column described by the given column diff.
     *
     * @return string[]
     */
    private function getAlterColumnClausesSQL(ColumnDiff $columnDiff): array
    {
        $newColumn = $columnDiff->getNewColumn()->toArray();

        $alterClause = 'ALTER COLUMN ' . $columnDiff->getNewColumn()->getQuotedName($this);

        if ($newColumn['columnDefinition'] !== null) {
            return [$alterClause . ' ' . $newColumn['columnDefinition']];
        }

        $clauses = [];

        if (
            $columnDiff->hasTypeChanged() ||
            $columnDiff->hasLengthChanged() ||
            $columnDiff->hasPrecisionChanged() ||
            $columnDiff->hasScaleChanged() ||
            $columnDiff->hasFixedChanged()
        ) {
            $clauses[] = $alterClause . ' SET DATA TYPE ' . $newColumn['type']->getSQLDeclaration($newColumn, $this);
        }

        if ($columnDiff->hasNotNullChanged()) {
            $clauses[] = $newColumn['notnull'] ? $alterClause . ' SET NOT NULL' : $alterClause . ' DROP NOT NULL';
        }

        if ($columnDiff->hasDefaultChanged()) {
            if (isset($newColumn['default'])) {
                $defaultClause = $this->getDefaultValueDeclarationSQL($newColumn);

                if ($defaultClause !== '') {
                    $clauses[] = $alterClause . ' SET' . $defaultClause;
                }
            } else {
                $clauses[] = $alterClause . ' DROP DEFAULT';
            }
        }

        return $clauses;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $sql = [];

        $tableNameSQL = ($diff->getOldTable() ?? $diff->getName($this))->getQuotedName($this);

        foreach ($diff->getDroppedIndexes() as $droppedIndex) {
            foreach ($diff->getAddedIndexes() as $addedIndex) {
                if ($droppedIndex->getColumns() !== $addedIndex->getColumns()) {
                    continue;
                }

                if ($droppedIndex->isPrimary()) {
                    $sql[] = 'ALTER TABLE ' . $tableNameSQL . ' DROP PRIMARY KEY';
                } elseif ($droppedIndex->isUnique()) {
                    $sql[] = 'ALTER TABLE ' . $tableNameSQL . ' DROP UNIQUE ' . $droppedIndex->getQuotedName($this);
                } else {
                    $sql[] = $this->getDropIndexSQL($droppedIndex, $tableNameSQL);
                }

                $sql[] = $this->getCreateIndexSQL($addedIndex, $tableNameSQL);

                $diff->unsetAddedIndex($addedIndex);
                $diff->unsetDroppedIndex($droppedIndex);

                break;
            }
        }

        return array_merge($sql, parent::getPreAlterTableIndexForeignKeySQL($diff));
    }

    /**
     * {@inheritDoc}
     */
    protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName)
    {
        if (strpos($tableName, '.') !== false) {
            [$schema]     = explode('.', $tableName);
            $oldIndexName = $schema . '.' . $oldIndexName;
        }

        return ['RENAME INDEX ' . $oldIndexName . ' TO ' . $index->getQuotedName($this)];
    }

    /**
     * {@inheritDoc}
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     */
    public function getDefaultValueDeclarationSQL($column)
    {
        if (! empty($column['autoincrement'])) {
            return '';
        }

        if (! empty($column['version'])) {
            if ((string) $column['type'] !== 'DateTime') {
                $column['default'] = '1';
            }
        }

        return parent::getDefaultValueDeclarationSQL($column);
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
    public function getCreateTemporaryTableSnippetSQL()
    {
        return 'DECLARE GLOBAL TEMPORARY TABLE';
    }

    /**
     * {@inheritDoc}
     */
    public function getTemporaryTableName($tableName)
    {
        return 'SESSION.' . $tableName;
    }

    /**
     * {@inheritDoc}
     */
    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        $where = [];

        if ($offset > 0) {
            $where[] = sprintf('db22.DC_ROWNUM >= %d', $offset + 1);
        }

        if ($limit !== null) {
            $where[] = sprintf('db22.DC_ROWNUM <= %d', $offset + $limit);
        }

        if (empty($where)) {
            return $query;
        }

        // Todo OVER() needs ORDER BY data!
        return sprintf(
            'SELECT db22.* FROM (SELECT db21.*, ROW_NUMBER() OVER() AS DC_ROWNUM FROM (%s) db21) db22 WHERE %s',
            $query,
            implode(' AND ', $where),
        );
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
    public function getSubstringExpression($string, $start, $length = null)
    {
        if ($length === null) {
            return 'SUBSTR(' . $string . ', ' . $start . ')';
        }

        return 'SUBSTR(' . $string . ', ' . $start . ', ' . $length . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getLengthExpression($column)
    {
        return 'LENGTH(' . $column . ', CODEUNITS32)';
    }

    public function getCurrentDatabaseExpression(): string
    {
        return 'CURRENT_USER';
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
     * @deprecated
     */
    public function prefersIdentityColumns()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/1519',
            '%s() is deprecated.',
            __METHOD__,
        );

        return true;
    }

    public function createSelectSQLBuilder(): SelectSQLBuilder
    {
        return new DefaultSelectSQLBuilder($this, 'WITH RR USE AND KEEP UPDATE LOCKS', null);
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This API is not portable.
     */
    public function getForUpdateSQL()
    {
        return ' WITH RR USE AND KEEP UPDATE LOCKS';
    }

    /**
     * {@inheritDoc}
     */
    public function getDummySelectSQL()
    {
        $expression = func_num_args() > 0 ? func_get_arg(0) : '1';

        return sprintf('SELECT %s FROM sysibm.sysdummy1', $expression);
    }

    /**
     * {@inheritDoc}
     *
     * DB2 supports savepoints, but they work semantically different than on other vendor platforms.
     *
     * TODO: We have to investigate how to get DB2 up and running with savepoints.
     */
    public function supportsSavepoints()
    {
        return false;
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
            '%s() is deprecated,'
                . ' use %s::createReservedKeywordsList() instead.',
            __METHOD__,
            static::class,
        );

        return Keywords\DB2Keywords::class;
    }

    /** @deprecated The SQL used for schema introspection is an implementation detail and should not be relied upon. */
    public function getListTableCommentsSQL(string $table): string
    {
        return sprintf(
            <<<'SQL'
SELECT REMARKS
  FROM SYSIBM.SYSTABLES
  WHERE NAME = UPPER( %s )
SQL
            ,
            $this->quoteStringLiteral($table),
        );
    }

    public function createSchemaManager(Connection $connection): DB2SchemaManager
    {
        return new DB2SchemaManager($connection, $this);
    }
}
