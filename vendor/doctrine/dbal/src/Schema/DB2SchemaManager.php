<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;

use function array_change_key_case;
use function implode;
use function preg_match;
use function str_replace;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;

use const CASE_LOWER;

/**
 * IBM Db2 Schema Manager.
 *
 * @extends AbstractSchemaManager<DB2Platform>
 */
class DB2SchemaManager extends AbstractSchemaManager
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
     *
     * @throws Exception
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $length    = null;
        $fixed     = null;
        $scale     = false;
        $precision = false;

        $default = null;

        if ($tableColumn['default'] !== null && $tableColumn['default'] !== 'NULL') {
            $default = $tableColumn['default'];

            if (preg_match('/^\'(.*)\'$/s', $default, $matches) === 1) {
                $default = str_replace("''", "'", $matches[1]);
            }
        }

        $type = $this->_platform->getDoctrineTypeMapping($tableColumn['typename']);

        if (isset($tableColumn['comment'])) {
            $type                   = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type);
            $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type);
        }

        switch (strtolower($tableColumn['typename'])) {
            case 'varchar':
                if ($tableColumn['codepage'] === 0) {
                    $type = Types::BINARY;
                }

                $length = $tableColumn['length'];
                $fixed  = false;
                break;

            case 'character':
                if ($tableColumn['codepage'] === 0) {
                    $type = Types::BINARY;
                }

                $length = $tableColumn['length'];
                $fixed  = true;
                break;

            case 'clob':
                $length = $tableColumn['length'];
                break;

            case 'decimal':
            case 'double':
            case 'real':
                $scale     = $tableColumn['scale'];
                $precision = $tableColumn['length'];
                break;
        }

        $options = [
            'length'        => $length,
            'fixed'         => (bool) $fixed,
            'default'       => $default,
            'autoincrement' => (bool) $tableColumn['autoincrement'],
            'notnull'       => $tableColumn['nulls'] === 'N',
            'comment'       => isset($tableColumn['comment']) && $tableColumn['comment'] !== ''
                ? $tableColumn['comment']
                : null,
        ];

        if ($scale !== null && $precision !== null) {
            $options['scale']     = $scale;
            $options['precision'] = $precision;
        }

        return new Column($tableColumn['colname'], Type::getType($type), $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableDefinition($table)
    {
        $table = array_change_key_case($table, CASE_LOWER);

        return $table['name'];
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        foreach ($tableIndexes as &$tableIndexRow) {
            $tableIndexRow            = array_change_key_case($tableIndexRow, CASE_LOWER);
            $tableIndexRow['primary'] = (bool) $tableIndexRow['primary'];
        }

        return parent::_getPortableTableIndexesList($tableIndexes, $tableName);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        return new ForeignKeyConstraint(
            $tableForeignKey['local_columns'],
            $tableForeignKey['foreign_table'],
            $tableForeignKey['foreign_columns'],
            $tableForeignKey['name'],
            $tableForeignKey['options'],
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $foreignKeys = [];

        foreach ($tableForeignKeys as $tableForeignKey) {
            $tableForeignKey = array_change_key_case($tableForeignKey, CASE_LOWER);

            if (! isset($foreignKeys[$tableForeignKey['index_name']])) {
                $foreignKeys[$tableForeignKey['index_name']] = [
                    'local_columns'   => [$tableForeignKey['local_column']],
                    'foreign_table'   => $tableForeignKey['foreign_table'],
                    'foreign_columns' => [$tableForeignKey['foreign_column']],
                    'name'            => $tableForeignKey['index_name'],
                    'options'         => [
                        'onUpdate' => $tableForeignKey['on_update'],
                        'onDelete' => $tableForeignKey['on_delete'],
                    ],
                ];
            } else {
                $foreignKeys[$tableForeignKey['index_name']]['local_columns'][]   = $tableForeignKey['local_column'];
                $foreignKeys[$tableForeignKey['index_name']]['foreign_columns'][] = $tableForeignKey['foreign_column'];
            }
        }

        return parent::_getPortableTableForeignKeysList($foreignKeys);
    }

    /**
     * @param string $def
     *
     * @return string|null
     */
    protected function _getPortableForeignKeyRuleDef($def)
    {
        if ($def === 'C') {
            return 'CASCADE';
        }

        if ($def === 'N') {
            return 'SET NULL';
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableViewDefinition($view)
    {
        $view = array_change_key_case($view, CASE_LOWER);

        $sql = '';
        $pos = strpos($view['text'], ' AS ');

        if ($pos !== false) {
            $sql = substr($view['text'], $pos + 4);
        }

        return new View($view['name'], $sql);
    }

    protected function normalizeName(string $name): string
    {
        $identifier = new Identifier($name);

        return $identifier->isQuoted() ? $identifier->getName() : strtoupper($name);
    }

    protected function selectTableNames(string $databaseName): Result
    {
        $sql = <<<'SQL'
SELECT NAME
FROM SYSIBM.SYSTABLES
WHERE TYPE = 'T'
  AND CREATOR = ?
SQL;

        return $this->_conn->executeQuery($sql, [$databaseName]);
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' C.TABNAME AS NAME,';
        }

        $sql .= <<<'SQL'
       C.COLNAME,
       C.TYPENAME,
       C.CODEPAGE,
       C.NULLS,
       C.LENGTH,
       C.SCALE,
       C.REMARKS AS COMMENT,
       CASE
           WHEN C.GENERATED = 'D' THEN 1
           ELSE 0
           END   AS AUTOINCREMENT,
       C.DEFAULT
FROM SYSCAT.COLUMNS C
         JOIN SYSCAT.TABLES AS T
              ON T.TABSCHEMA = C.TABSCHEMA
                  AND T.TABNAME = C.TABNAME
SQL;

        $conditions = ['C.TABSCHEMA = ?', "T.TYPE = 'T'"];
        $params     = [$databaseName];

        if ($tableName !== null) {
            $conditions[] = 'C.TABNAME = ?';
            $params[]     = $tableName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY C.TABNAME, C.COLNO';

        return $this->_conn->executeQuery($sql, $params);
    }

    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' IDX.TABNAME AS NAME,';
        }

        $sql .= <<<'SQL'
             IDX.INDNAME AS KEY_NAME,
             IDXCOL.COLNAME AS COLUMN_NAME,
             CASE
                 WHEN IDX.UNIQUERULE = 'P' THEN 1
                 ELSE 0
             END AS PRIMARY,
             CASE
                 WHEN IDX.UNIQUERULE = 'D' THEN 1
                 ELSE 0
             END AS NON_UNIQUE
        FROM SYSCAT.INDEXES AS IDX
        JOIN SYSCAT.TABLES AS T
          ON IDX.TABSCHEMA = T.TABSCHEMA AND IDX.TABNAME = T.TABNAME
        JOIN SYSCAT.INDEXCOLUSE AS IDXCOL
          ON IDX.INDSCHEMA = IDXCOL.INDSCHEMA AND IDX.INDNAME = IDXCOL.INDNAME
SQL;

        $conditions = ['IDX.TABSCHEMA = ?', "T.TYPE = 'T'"];
        $params     = [$databaseName];

        if ($tableName !== null) {
            $conditions[] = 'IDX.TABNAME = ?';
            $params[]     = $tableName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY IDX.INDNAME, IDXCOL.COLSEQ';

        return $this->_conn->executeQuery($sql, $params);
    }

    protected function selectForeignKeyColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' R.TABNAME AS NAME,';
        }

        $sql .= <<<'SQL'
             FKCOL.COLNAME AS LOCAL_COLUMN,
             R.REFTABNAME AS FOREIGN_TABLE,
             PKCOL.COLNAME AS FOREIGN_COLUMN,
             R.CONSTNAME AS INDEX_NAME,
             CASE
                 WHEN R.UPDATERULE = 'R' THEN 'RESTRICT'
             END AS ON_UPDATE,
             CASE
                 WHEN R.DELETERULE = 'C' THEN 'CASCADE'
                 WHEN R.DELETERULE = 'N' THEN 'SET NULL'
                 WHEN R.DELETERULE = 'R' THEN 'RESTRICT'
             END AS ON_DELETE
        FROM SYSCAT.REFERENCES AS R
         JOIN SYSCAT.TABLES AS T
              ON T.TABSCHEMA = R.TABSCHEMA
                  AND T.TABNAME = R.TABNAME
         JOIN SYSCAT.KEYCOLUSE AS FKCOL
              ON FKCOL.CONSTNAME = R.CONSTNAME
                  AND FKCOL.TABSCHEMA = R.TABSCHEMA
                  AND FKCOL.TABNAME = R.TABNAME
         JOIN SYSCAT.KEYCOLUSE AS PKCOL
              ON PKCOL.CONSTNAME = R.REFKEYNAME
                  AND PKCOL.TABSCHEMA = R.REFTABSCHEMA
                  AND PKCOL.TABNAME = R.REFTABNAME
                  AND PKCOL.COLSEQ = FKCOL.COLSEQ
SQL;

        $conditions = ['R.TABSCHEMA = ?', "T.TYPE = 'T'"];
        $params     = [$databaseName];

        if ($tableName !== null) {
            $conditions[] = 'R.TABNAME = ?';
            $params[]     = $tableName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY R.CONSTNAME, FKCOL.COLSEQ';

        return $this->_conn->executeQuery($sql, $params);
    }

    /**
     * {@inheritDoc}
     */
    protected function fetchTableOptionsByTable(string $databaseName, ?string $tableName = null): array
    {
        $sql = 'SELECT NAME, REMARKS';

        $conditions = [];
        $params     = [];

        if ($tableName !== null) {
            $conditions[] = 'NAME = ?';
            $params[]     = $tableName;
        }

        $sql .= ' FROM SYSIBM.SYSTABLES';

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        /** @var array<string,array<string,mixed>> $metadata */
        $metadata = $this->_conn->executeQuery($sql, $params)
            ->fetchAllAssociativeIndexed();

        $tableOptions = [];
        foreach ($metadata as $table => $data) {
            $data = array_change_key_case($data, CASE_LOWER);

            $tableOptions[$table] = ['comment' => $data['remarks']];
        }

        return $tableOptions;
    }
}
