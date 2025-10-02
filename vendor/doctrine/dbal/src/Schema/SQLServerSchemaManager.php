<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\SQLServer;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;

use function array_change_key_case;
use function assert;
use function count;
use function explode;
use function implode;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function strpos;
use function strtok;

use const CASE_LOWER;

/**
 * SQL Server Schema Manager.
 *
 * @extends AbstractSchemaManager<SQLServerPlatform>
 */
class SQLServerSchemaManager extends AbstractSchemaManager
{
    private ?string $databaseCollation = null;

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
    public function listSchemaNames(): array
    {
        return $this->_conn->fetchFirstColumn(
            <<<'SQL'
SELECT name
FROM   sys.schemas
WHERE  name NOT IN('guest', 'INFORMATION_SCHEMA', 'sys')
SQL,
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableSequenceDefinition($sequence)
    {
        return new Sequence($sequence['name'], (int) $sequence['increment'], (int) $sequence['start_value']);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $dbType = strtok($tableColumn['type'], '(), ');
        assert(is_string($dbType));

        $fixed   = null;
        $length  = (int) $tableColumn['length'];
        $default = $tableColumn['default'];

        if (! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

        if ($default !== null) {
            $default = $this->parseDefaultExpression($default);
        }

        switch ($dbType) {
            case 'nchar':
            case 'ntext':
                // Unicode data requires 2 bytes per character
                $length /= 2;
                break;

            case 'nvarchar':
                if ($length === -1) {
                    break;
                }

                // Unicode data requires 2 bytes per character
                $length /= 2;
                break;

            case 'varchar':
                // TEXT type is returned as VARCHAR(MAX) with a length of -1
                if ($length === -1) {
                    $dbType = 'text';
                }

                break;

            case 'varbinary':
                if ($length === -1) {
                    $dbType = 'blob';
                }

                break;
        }

        if ($dbType === 'char' || $dbType === 'nchar' || $dbType === 'binary') {
            $fixed = true;
        }

        $type                   = $this->_platform->getDoctrineTypeMapping($dbType);
        $type                   = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type);
        $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type);

        $options = [
            'unsigned'      => false,
            'fixed'         => (bool) $fixed,
            'default'       => $default,
            'notnull'       => (bool) $tableColumn['notnull'],
            'scale'         => $tableColumn['scale'],
            'precision'     => $tableColumn['precision'],
            'autoincrement' => (bool) $tableColumn['autoincrement'],
            'comment'       => $tableColumn['comment'] !== '' ? $tableColumn['comment'] : null,
        ];

        if ($length !== 0 && ($type === 'text' || $type === 'string' || $type === 'binary')) {
            $options['length'] = $length;
        }

        $column = new Column($tableColumn['name'], Type::getType($type), $options);

        if (isset($tableColumn['collation']) && $tableColumn['collation'] !== 'NULL') {
            $column->setPlatformOption('collation', $tableColumn['collation']);
        }

        return $column;
    }

    private function parseDefaultExpression(string $value): ?string
    {
        while (preg_match('/^\((.*)\)$/s', $value, $matches) === 1) {
            $value = $matches[1];
        }

        if ($value === 'NULL') {
            return null;
        }

        if (preg_match('/^\'(.*)\'$/s', $value, $matches) === 1) {
            $value = str_replace("''", "'", $matches[1]);
        }

        if ($value === 'getdate()') {
            return $this->_platform->getCurrentTimestampSQL();
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $foreignKeys = [];

        foreach ($tableForeignKeys as $tableForeignKey) {
            $name = $tableForeignKey['ForeignKey'];

            if (! isset($foreignKeys[$name])) {
                $referencedTableName = $tableForeignKey['ReferenceTableName'];

                if ($tableForeignKey['ReferenceSchemaName'] !== 'dbo') {
                    $referencedTableName = $tableForeignKey['ReferenceSchemaName'] . '.' . $referencedTableName;
                }

                $foreignKeys[$name] = [
                    'local_columns' => [$tableForeignKey['ColumnName']],
                    'foreign_table' => $referencedTableName,
                    'foreign_columns' => [$tableForeignKey['ReferenceColumnName']],
                    'name' => $name,
                    'options' => [
                        'onUpdate' => str_replace('_', ' ', $tableForeignKey['update_referential_action_desc']),
                        'onDelete' => str_replace('_', ' ', $tableForeignKey['delete_referential_action_desc']),
                    ],
                ];
            } else {
                $foreignKeys[$name]['local_columns'][]   = $tableForeignKey['ColumnName'];
                $foreignKeys[$name]['foreign_columns'][] = $tableForeignKey['ReferenceColumnName'];
            }
        }

        return parent::_getPortableTableForeignKeysList($foreignKeys);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        foreach ($tableIndexes as &$tableIndex) {
            $tableIndex['non_unique'] = (bool) $tableIndex['non_unique'];
            $tableIndex['primary']    = (bool) $tableIndex['primary'];
            $tableIndex['flags']      = $tableIndex['flags'] ? [$tableIndex['flags']] : null;
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
    protected function _getPortableTableDefinition($table)
    {
        if ($table['schema_name'] !== 'dbo') {
            return $table['schema_name'] . '.' . $table['table_name'];
        }

        return $table['table_name'];
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableDatabaseDefinition($database)
    {
        return $database['name'];
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@see listSchemaNames()} instead.
     */
    protected function getPortableNamespaceDefinition(array $namespace)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4503',
            'SQLServerSchemaManager::getPortableNamespaceDefinition() is deprecated,'
                . ' use SQLServerSchemaManager::listSchemaNames() instead.',
        );

        return $namespace['name'];
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableViewDefinition($view)
    {
        // @todo
        return new View($view['name'], $view['definition']);
    }

    /**
     * {@inheritDoc}
     */
    public function alterTable(TableDiff $tableDiff)
    {
        $droppedColumns = $tableDiff->getDroppedColumns();

        if (count($droppedColumns) > 0) {
            $tableName = ($tableDiff->getOldTable() ?? $tableDiff->getName($this->_platform))->getName();

            foreach ($droppedColumns as $col) {
                foreach ($this->getColumnConstraints($tableName, $col->getName()) as $constraint) {
                    $this->_conn->executeStatement(
                        sprintf(
                            'ALTER TABLE %s DROP CONSTRAINT %s',
                            $tableName,
                            $constraint,
                        ),
                    );
                }
            }
        }

        parent::alterTable($tableDiff);
    }

    /**
     * Returns the names of the constraints for a given column.
     *
     * @return iterable<string>
     *
     * @throws Exception
     */
    private function getColumnConstraints(string $table, string $column): iterable
    {
        return $this->_conn->iterateColumn(
            <<<'SQL'
SELECT o.name
FROM sys.objects o
         INNER JOIN sys.objects t
                    ON t.object_id = o.parent_object_id
                        AND t.type = 'U'
         INNER JOIN sys.default_constraints dc
                    ON dc.object_id = o.object_id
         INNER JOIN sys.columns c
                    ON c.column_id = dc.parent_column_id
                        AND c.object_id = t.object_id
WHERE t.name = ?
  AND c.name = ?
SQL
            ,
            [$table, $column],
        );
    }

    /** @throws Exception */
    public function createComparator(): Comparator
    {
        return new SQLServer\Comparator($this->_platform, $this->getDatabaseCollation());
    }

    /** @throws Exception */
    private function getDatabaseCollation(): string
    {
        if ($this->databaseCollation === null) {
            $databaseCollation = $this->_conn->fetchOne(
                'SELECT collation_name FROM sys.databases WHERE name = '
                . $this->_platform->getCurrentDatabaseExpression(),
            );

            // a database is always selected, even if omitted in the connection parameters
            assert(is_string($databaseCollation));

            $this->databaseCollation = $databaseCollation;
        }

        return $this->databaseCollation;
    }

    protected function selectTableNames(string $databaseName): Result
    {
        // The "sysdiagrams" table must be ignored as it's internal SQL Server table for Database Diagrams
        $sql = <<<'SQL'
SELECT name AS table_name,
       SCHEMA_NAME(schema_id) AS schema_name
FROM sys.objects
WHERE type = 'U'
  AND name != 'sysdiagrams'
ORDER BY name
SQL;

        return $this->_conn->executeQuery($sql);
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' obj.name AS table_name, scm.name AS schema_name,';
        }

        $sql .= <<<'SQL'
                          col.name,
                          type.name AS type,
                          col.max_length AS length,
                          ~col.is_nullable AS notnull,
                          def.definition AS [default],
                          col.scale,
                          col.precision,
                          col.is_identity AS autoincrement,
                          col.collation_name AS collation,
                          -- CAST avoids driver error for sql_variant type
                          CAST(prop.value AS NVARCHAR(MAX)) AS comment
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
SQL;

        // The "sysdiagrams" table must be ignored as it's internal SQL Server table for Database Diagrams
        $conditions = ["obj.type = 'U'", "obj.name != 'sysdiagrams'"];
        $params     = [];

        if ($tableName !== null) {
            $conditions[] = $this->getTableWhereClause($tableName, 'scm.name', 'obj.name');
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        return $this->_conn->executeQuery($sql, $params);
    }

    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' tbl.name AS table_name, scm.name AS schema_name,';
        }

        $sql .= <<<'SQL'
                       idx.name AS key_name,
                       col.name AS column_name,
                       ~idx.is_unique AS non_unique,
                       idx.is_primary_key AS [primary],
                       CASE idx.type
                           WHEN '1' THEN 'clustered'
                           WHEN '2' THEN 'nonclustered'
                           ELSE NULL
                       END AS flags
                FROM sys.tables AS tbl
                JOIN sys.schemas AS scm
                  ON tbl.schema_id = scm.schema_id
                JOIN sys.indexes AS idx
                  ON tbl.object_id = idx.object_id
                JOIN sys.index_columns AS idxcol
                  ON idx.object_id = idxcol.object_id
                 AND idx.index_id = idxcol.index_id
                JOIN sys.columns AS col
                  ON idxcol.object_id = col.object_id
                 AND idxcol.column_id = col.column_id
SQL;

        $conditions = [];
        $params     = [];

        if ($tableName !== null) {
            $conditions[] = $this->getTableWhereClause($tableName, 'scm.name', 'tbl.name');
            $sql         .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY idx.index_id, idxcol.key_ordinal';

        return $this->_conn->executeQuery($sql, $params);
    }

    protected function selectForeignKeyColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' OBJECT_NAME (f.parent_object_id) AS table_name, SCHEMA_NAME(f.schema_id) AS schema_name,';
        }

        $sql .= <<<'SQL'
                f.name AS ForeignKey,
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
SQL;

        $conditions = [];
        $params     = [];

        if ($tableName !== null) {
            $conditions[] = $this->getTableWhereClause(
                $tableName,
                'SCHEMA_NAME(f.schema_id)',
                'OBJECT_NAME(f.parent_object_id)',
            );

            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY fc.constraint_column_id';

        return $this->_conn->executeQuery($sql, $params);
    }

    /**
     * {@inheritDoc}
     */
    protected function fetchTableOptionsByTable(string $databaseName, ?string $tableName = null): array
    {
        $sql = <<<'SQL'
          SELECT
            scm.name AS schema_name,
            tbl.name AS table_name,
            p.value AS [table_comment]
          FROM
            sys.tables AS tbl
            JOIN sys.schemas AS scm
              ON tbl.schema_id = scm.schema_id
            INNER JOIN sys.extended_properties AS p ON p.major_id=tbl.object_id AND p.minor_id=0 AND p.class=1
SQL;

        $conditions = ["p.name = N'MS_Description'"];

        if ($tableName !== null) {
            $conditions[] = $this->getTableWhereClause($tableName, 'scm.name', 'tbl.name');
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        $tableOptions = [];
        foreach ($this->_conn->iterateAssociative($sql) as $data) {
            $data = array_change_key_case($data, CASE_LOWER);

            $tableOptions[$this->_getPortableTableDefinition($data)] = [
                'comment' => $data['table_comment'],
            ];
        }

        return $tableOptions;
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
            $schema           = $this->_platform->quoteStringLiteral($schema);
            $table            = $this->_platform->quoteStringLiteral($table);
        } else {
            $schema = 'SCHEMA_NAME()';
            $table  = $this->_platform->quoteStringLiteral($table);
        }

        return sprintf('(%s = %s AND %s = %s)', $tableColumn, $table, $schemaColumn, $schema);
    }
}
