<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;

use function array_change_key_case;
use function array_values;
use function implode;
use function is_string;
use function preg_match;
use function str_replace;
use function strpos;
use function strtolower;
use function strtoupper;
use function trim;

use const CASE_LOWER;

/**
 * Oracle Schema Manager.
 *
 * @extends AbstractSchemaManager<OraclePlatform>
 */
class OracleSchemaManager extends AbstractSchemaManager
{
    /**
     * {@inheritDoc}
     */
    public function listTableNames()
    {
        return $this->doListTableNames();
    }

    /**
     * {@inheritDoc}
     */
    public function listTables()
    {
        return $this->doListTables();
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@see introspectTable()} instead.
     */
    public function listTableDetails($name)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5595',
            '%s is deprecated. Use introspectTable() instead.',
            __METHOD__,
        );

        return $this->doListTableDetails($name);
    }

    /**
     * {@inheritDoc}
     */
    public function listTableColumns($table, $database = null)
    {
        return $this->doListTableColumns($table, $database);
    }

    /**
     * {@inheritDoc}
     */
    public function listTableIndexes($table)
    {
        return $this->doListTableIndexes($table);
    }

    /**
     * {@inheritDoc}
     */
    public function listTableForeignKeys($table, $database = null)
    {
        return $this->doListTableForeignKeys($table, $database);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableViewDefinition($view)
    {
        $view = array_change_key_case($view, CASE_LOWER);

        return new View($this->getQuotedIdentifierName($view['view_name']), $view['text']);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableDefinition($table)
    {
        $table = array_change_key_case($table, CASE_LOWER);

        return $this->getQuotedIdentifierName($table['table_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        $indexBuffer = [];
        foreach ($tableIndexes as $tableIndex) {
            $tableIndex = array_change_key_case($tableIndex, CASE_LOWER);

            $keyName = strtolower($tableIndex['name']);
            $buffer  = [];

            if ($tableIndex['is_primary'] === 'P') {
                $keyName              = 'primary';
                $buffer['primary']    = true;
                $buffer['non_unique'] = false;
            } else {
                $buffer['primary']    = false;
                $buffer['non_unique'] = ! $tableIndex['is_unique'];
            }

            $buffer['key_name']    = $keyName;
            $buffer['column_name'] = $this->getQuotedIdentifierName($tableIndex['column_name']);
            $indexBuffer[]         = $buffer;
        }

        return parent::_getPortableTableIndexesList($indexBuffer, $tableName);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $dbType = strtolower($tableColumn['data_type']);
        if (strpos($dbType, 'timestamp(') === 0) {
            if (strpos($dbType, 'with time zone') !== false) {
                $dbType = 'timestamptz';
            } else {
                $dbType = 'timestamp';
            }
        }

        $unsigned = $fixed = $precision = $scale = $length = null;

        if (! isset($tableColumn['column_name'])) {
            $tableColumn['column_name'] = '';
        }

        // Default values returned from database sometimes have trailing spaces.
        if (is_string($tableColumn['data_default'])) {
            $tableColumn['data_default'] = trim($tableColumn['data_default']);
        }

        if ($tableColumn['data_default'] === '' || $tableColumn['data_default'] === 'NULL') {
            $tableColumn['data_default'] = null;
        }

        if ($tableColumn['data_default'] !== null) {
            // Default values returned from database are represented as literal expressions
            if (preg_match('/^\'(.*)\'$/s', $tableColumn['data_default'], $matches) === 1) {
                $tableColumn['data_default'] = str_replace("''", "'", $matches[1]);
            }
        }

        if ($tableColumn['data_precision'] !== null) {
            $precision = (int) $tableColumn['data_precision'];
        }

        if ($tableColumn['data_scale'] !== null) {
            $scale = (int) $tableColumn['data_scale'];
        }

        $type                    = $this->_platform->getDoctrineTypeMapping($dbType);
        $type                    = $this->extractDoctrineTypeFromComment($tableColumn['comments'], $type);
        $tableColumn['comments'] = $this->removeDoctrineTypeFromComment($tableColumn['comments'], $type);

        switch ($dbType) {
            case 'number':
                if ($precision === 20 && $scale === 0) {
                    $type = 'bigint';
                } elseif ($precision === 5 && $scale === 0) {
                    $type = 'smallint';
                } elseif ($precision === 1 && $scale === 0) {
                    $type = 'boolean';
                } elseif ($scale > 0) {
                    $type = 'decimal';
                }

                break;

            case 'varchar':
            case 'varchar2':
            case 'nvarchar2':
                $length = $tableColumn['char_length'];
                $fixed  = false;
                break;

            case 'raw':
                $length = $tableColumn['data_length'];
                $fixed  = true;
                break;

            case 'char':
            case 'nchar':
                $length = $tableColumn['char_length'];
                $fixed  = true;
                break;
        }

        $options = [
            'notnull'    => $tableColumn['nullable'] === 'N',
            'fixed'      => (bool) $fixed,
            'unsigned'   => (bool) $unsigned,
            'default'    => $tableColumn['data_default'],
            'length'     => $length,
            'precision'  => $precision,
            'scale'      => $scale,
            'comment'    => isset($tableColumn['comments']) && $tableColumn['comments'] !== ''
                ? $tableColumn['comments']
                : null,
        ];

        return new Column($this->getQuotedIdentifierName($tableColumn['column_name']), Type::getType($type), $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $list = [];
        foreach ($tableForeignKeys as $value) {
            $value = array_change_key_case($value, CASE_LOWER);
            if (! isset($list[$value['constraint_name']])) {
                if ($value['delete_rule'] === 'NO ACTION') {
                    $value['delete_rule'] = null;
                }

                $list[$value['constraint_name']] = [
                    'name' => $this->getQuotedIdentifierName($value['constraint_name']),
                    'local' => [],
                    'foreign' => [],
                    'foreignTable' => $value['references_table'],
                    'onDelete' => $value['delete_rule'],
                ];
            }

            $localColumn   = $this->getQuotedIdentifierName($value['local_column']);
            $foreignColumn = $this->getQuotedIdentifierName($value['foreign_column']);

            $list[$value['constraint_name']]['local'][$value['position']]   = $localColumn;
            $list[$value['constraint_name']]['foreign'][$value['position']] = $foreignColumn;
        }

        return parent::_getPortableTableForeignKeysList($list);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableForeignKeyDefinition($tableForeignKey): ForeignKeyConstraint
    {
        return new ForeignKeyConstraint(
            array_values($tableForeignKey['local']),
            $this->getQuotedIdentifierName($tableForeignKey['foreignTable']),
            array_values($tableForeignKey['foreign']),
            $this->getQuotedIdentifierName($tableForeignKey['name']),
            ['onDelete' => $tableForeignKey['onDelete']],
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableSequenceDefinition($sequence)
    {
        $sequence = array_change_key_case($sequence, CASE_LOWER);

        return new Sequence(
            $this->getQuotedIdentifierName($sequence['sequence_name']),
            (int) $sequence['increment_by'],
            (int) $sequence['min_value'],
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableDatabaseDefinition($database)
    {
        $database = array_change_key_case($database, CASE_LOWER);

        return $database['username'];
    }

    /**
     * {@inheritDoc}
     */
    public function createDatabase($database)
    {
        $statement = $this->_platform->getCreateDatabaseSQL($database);

        $params = $this->_conn->getParams();

        if (isset($params['password'])) {
            $statement .= ' IDENTIFIED BY ' . $params['password'];
        }

        $this->_conn->executeStatement($statement);

        $statement = 'GRANT DBA TO ' . $database;
        $this->_conn->executeStatement($statement);
    }

    /**
     * @internal The method should be only used from within the OracleSchemaManager class hierarchy.
     *
     * @param string $table
     *
     * @return bool
     *
     * @throws Exception
     */
    public function dropAutoincrement($table)
    {
        $sql = $this->_platform->getDropAutoincrementSql($table);
        foreach ($sql as $query) {
            $this->_conn->executeStatement($query);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function dropTable($name)
    {
        $this->tryMethod('dropAutoincrement', $name);

        parent::dropTable($name);
    }

    /**
     * Returns the quoted representation of the given identifier name.
     *
     * Quotes non-uppercase identifiers explicitly to preserve case
     * and thus make references to the particular identifier work.
     *
     * @param string $identifier The identifier to quote.
     */
    private function getQuotedIdentifierName($identifier): string
    {
        if (preg_match('/[a-z]/', $identifier) === 1) {
            return $this->_platform->quoteIdentifier($identifier);
        }

        return $identifier;
    }

    protected function selectTableNames(string $databaseName): Result
    {
        $sql = <<<'SQL'
SELECT TABLE_NAME
FROM ALL_TABLES
WHERE OWNER = :OWNER
ORDER BY TABLE_NAME
SQL;

        return $this->_conn->executeQuery($sql, ['OWNER' => $databaseName]);
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' C.TABLE_NAME,';
        }

        $sql .= <<<'SQL'
                 C.COLUMN_NAME,
                 C.DATA_TYPE,
                 C.DATA_DEFAULT,
                 C.DATA_PRECISION,
                 C.DATA_SCALE,
                 C.CHAR_LENGTH,
                 C.DATA_LENGTH,
                 C.NULLABLE,
                 D.COMMENTS
            FROM ALL_TAB_COLUMNS C
        INNER JOIN ALL_TABLES T
            ON T.OWNER = C.OWNER
            AND T.TABLE_NAME = C.TABLE_NAME
       LEFT JOIN ALL_COL_COMMENTS D
           ON D.OWNER = C.OWNER
                  AND D.TABLE_NAME = C.TABLE_NAME
                  AND D.COLUMN_NAME = C.COLUMN_NAME
SQL;

        $conditions = ['C.OWNER = :OWNER'];
        $params     = ['OWNER' => $databaseName];

        if ($tableName !== null) {
            $conditions[]         = 'C.TABLE_NAME = :TABLE_NAME';
            $params['TABLE_NAME'] = $tableName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY C.COLUMN_ID';

        return $this->_conn->executeQuery($sql, $params);
    }

    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' IND_COL.TABLE_NAME,';
        }

        $sql .= <<<'SQL'
                 IND_COL.INDEX_NAME AS NAME,
                 IND.INDEX_TYPE AS TYPE,
                 DECODE(IND.UNIQUENESS, 'NONUNIQUE', 0, 'UNIQUE', 1) AS IS_UNIQUE,
                 IND_COL.COLUMN_NAME,
                 IND_COL.COLUMN_POSITION AS COLUMN_POS,
                 CON.CONSTRAINT_TYPE AS IS_PRIMARY
            FROM ALL_IND_COLUMNS IND_COL
       LEFT JOIN ALL_INDEXES IND
              ON IND.OWNER = IND_COL.INDEX_OWNER
             AND IND.INDEX_NAME = IND_COL.INDEX_NAME
       LEFT JOIN ALL_CONSTRAINTS CON
              ON CON.OWNER = IND_COL.INDEX_OWNER
             AND CON.INDEX_NAME = IND_COL.INDEX_NAME
SQL;

        $conditions = ['IND_COL.INDEX_OWNER = :OWNER'];
        $params     = ['OWNER' => $databaseName];

        if ($tableName !== null) {
            $conditions[]         = 'IND_COL.TABLE_NAME = :TABLE_NAME';
            $params['TABLE_NAME'] = $tableName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY IND_COL.TABLE_NAME, IND_COL.INDEX_NAME'
            . ', IND_COL.COLUMN_POSITION';

        return $this->_conn->executeQuery($sql, $params);
    }

    protected function selectForeignKeyColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' COLS.TABLE_NAME,';
        }

        $sql .= <<<'SQL'
                 ALC.CONSTRAINT_NAME,
                 ALC.DELETE_RULE,
                 COLS.COLUMN_NAME LOCAL_COLUMN,
                 COLS.POSITION,
                 R_COLS.TABLE_NAME REFERENCES_TABLE,
                 R_COLS.COLUMN_NAME FOREIGN_COLUMN
            FROM ALL_CONS_COLUMNS COLS
       LEFT JOIN ALL_CONSTRAINTS ALC ON ALC.OWNER = COLS.OWNER AND ALC.CONSTRAINT_NAME = COLS.CONSTRAINT_NAME
       LEFT JOIN ALL_CONS_COLUMNS R_COLS ON R_COLS.OWNER = ALC.R_OWNER AND
                 R_COLS.CONSTRAINT_NAME = ALC.R_CONSTRAINT_NAME AND
                 R_COLS.POSITION = COLS.POSITION
SQL;

        $conditions = ["ALC.CONSTRAINT_TYPE = 'R'", 'COLS.OWNER = :OWNER'];
        $params     = ['OWNER' => $databaseName];

        if ($tableName !== null) {
            $conditions[]         = 'COLS.TABLE_NAME = :TABLE_NAME';
            $params['TABLE_NAME'] = $tableName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY COLS.TABLE_NAME, COLS.CONSTRAINT_NAME'
            . ', COLS.POSITION';

        return $this->_conn->executeQuery($sql, $params);
    }

    /**
     * {@inheritDoc}
     */
    protected function fetchTableOptionsByTable(string $databaseName, ?string $tableName = null): array
    {
        $sql = 'SELECT TABLE_NAME, COMMENTS';

        $conditions = ['OWNER = :OWNER'];
        $params     = ['OWNER' => $databaseName];

        if ($tableName !== null) {
            $conditions[]         = 'TABLE_NAME = :TABLE_NAME';
            $params['TABLE_NAME'] = $tableName;
        }

        $sql .= ' FROM ALL_TAB_COMMENTS WHERE ' . implode(' AND ', $conditions);

        /** @var array<string,array<string,mixed>> $metadata */
        $metadata = $this->_conn->executeQuery($sql, $params)
            ->fetchAllAssociativeIndexed();

        $tableOptions = [];
        foreach ($metadata as $table => $data) {
            $data = array_change_key_case($data, CASE_LOWER);

            $tableOptions[$table] = [
                'comment' => $data['comments'],
            ];
        }

        return $tableOptions;
    }

    protected function normalizeName(string $name): string
    {
        $identifier = new Identifier($name);

        return $identifier->isQuoted() ? $identifier->getName() : strtoupper($name);
    }
}
