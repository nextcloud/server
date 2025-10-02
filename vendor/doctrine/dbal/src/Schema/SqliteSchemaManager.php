<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\SQLite;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;

use function array_change_key_case;
use function array_map;
use function array_merge;
use function count;
use function explode;
use function file_exists;
use function implode;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function str_replace;
use function strcasecmp;
use function strpos;
use function strtolower;
use function trim;
use function unlink;
use function usort;

use const CASE_LOWER;

/**
 * Sqlite SchemaManager.
 *
 * @extends AbstractSchemaManager<SqlitePlatform>
 */
class SqliteSchemaManager extends AbstractSchemaManager
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
    protected function fetchForeignKeyColumnsByTable(string $databaseName): array
    {
        $columnsByTable = parent::fetchForeignKeyColumnsByTable($databaseName);

        if (count($columnsByTable) > 0) {
            foreach ($columnsByTable as $table => $columns) {
                $columnsByTable[$table] = $this->addDetailsToTableForeignKeyColumns($table, $columns);
            }
        }

        return $columnsByTable;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Delete the database file using the filesystem.
     */
    public function dropDatabase($database)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4963',
            'SqliteSchemaManager::dropDatabase() is deprecated. Delete the database file using the filesystem.',
        );

        if (! file_exists($database)) {
            return;
        }

        unlink($database);
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated The engine will create the database file automatically.
     */
    public function createDatabase($database)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4963',
            'SqliteSchemaManager::createDatabase() is deprecated.'
                . ' The engine will create the database file automatically.',
        );

        $params = $this->_conn->getParams();

        $params['path'] = $database;
        unset($params['memory']);

        $conn = DriverManager::getConnection($params);
        $conn->connect();
        $conn->close();
    }

    /**
     * {@inheritDoc}
     */
    public function createForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        if (! $table instanceof Table) {
            $table = $this->listTableDetails($table);
        }

        $this->alterTable(new TableDiff($table->getName(), [], [], [], [], [], [], $table, [$foreignKey]));
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@see dropForeignKey()} and {@see createForeignKey()} instead.
     */
    public function dropAndCreateForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4897',
            'SqliteSchemaManager::dropAndCreateForeignKey() is deprecated.'
                . ' Use SqliteSchemaManager::dropForeignKey() and SqliteSchemaManager::createForeignKey() instead.',
        );

        if (! $table instanceof Table) {
            $table = $this->listTableDetails($table);
        }

        $this->alterTable(new TableDiff($table->getName(), [], [], [], [], [], [], $table, [], [$foreignKey]));
    }

    /**
     * {@inheritDoc}
     */
    public function dropForeignKey($foreignKey, $table)
    {
        if (! $table instanceof Table) {
            $table = $this->listTableDetails($table);
        }

        $this->alterTable(new TableDiff($table->getName(), [], [], [], [], [], [], $table, [], [], [$foreignKey]));
    }

    /**
     * {@inheritDoc}
     */
    public function listTableForeignKeys($table, $database = null)
    {
        $table = $this->normalizeName($table);

        $columns = $this->selectForeignKeyColumns($database ?? 'main', $table)
            ->fetchAllAssociative();

        if (count($columns) > 0) {
            $columns = $this->addDetailsToTableForeignKeyColumns($table, $columns);
        }

        return $this->_getPortableTableForeignKeysList($columns);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableDefinition($table)
    {
        return $table['table_name'];
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        $indexBuffer = [];

        // fetch primary
        $indexArray = $this->_conn->fetchAllAssociative('SELECT * FROM PRAGMA_TABLE_INFO (?)', [$tableName]);

        usort(
            $indexArray,
            /**
             * @param array<string,mixed> $a
             * @param array<string,mixed> $b
             */
            static function (array $a, array $b): int {
                if ($a['pk'] === $b['pk']) {
                    return $a['cid'] - $b['cid'];
                }

                return $a['pk'] - $b['pk'];
            },
        );

        foreach ($indexArray as $indexColumnRow) {
            if ($indexColumnRow['pk'] === 0 || $indexColumnRow['pk'] === '0') {
                continue;
            }

            $indexBuffer[] = [
                'key_name' => 'primary',
                'primary' => true,
                'non_unique' => false,
                'column_name' => $indexColumnRow['name'],
            ];
        }

        // fetch regular indexes
        foreach ($tableIndexes as $tableIndex) {
            // Ignore indexes with reserved names, e.g. autoindexes
            if (strpos($tableIndex['name'], 'sqlite_') === 0) {
                continue;
            }

            $keyName           = $tableIndex['name'];
            $idx               = [];
            $idx['key_name']   = $keyName;
            $idx['primary']    = false;
            $idx['non_unique'] = ! $tableIndex['unique'];

            $indexArray = $this->_conn->fetchAllAssociative('SELECT * FROM PRAGMA_INDEX_INFO (?)', [$keyName]);

            foreach ($indexArray as $indexColumnRow) {
                $idx['column_name'] = $indexColumnRow['name'];
                $indexBuffer[]      = $idx;
            }
        }

        return parent::_getPortableTableIndexesList($indexBuffer, $tableName);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableColumnList($table, $database, $tableColumns)
    {
        $list = parent::_getPortableTableColumnList($table, $database, $tableColumns);

        // find column with autoincrement
        $autoincrementColumn = null;
        $autoincrementCount  = 0;

        foreach ($tableColumns as $tableColumn) {
            if ($tableColumn['pk'] === 0 || $tableColumn['pk'] === '0') {
                continue;
            }

            $autoincrementCount++;
            if ($autoincrementColumn !== null || strtolower($tableColumn['type']) !== 'integer') {
                continue;
            }

            $autoincrementColumn = $tableColumn['name'];
        }

        if ($autoincrementCount === 1 && $autoincrementColumn !== null) {
            foreach ($list as $column) {
                if ($autoincrementColumn !== $column->getName()) {
                    continue;
                }

                $column->setAutoincrement(true);
            }
        }

        // inspect column collation and comments
        $createSql = $this->getCreateTableSQL($table);

        foreach ($list as $columnName => $column) {
            $type = $column->getType();

            if ($type instanceof StringType || $type instanceof TextType) {
                $column->setPlatformOption(
                    'collation',
                    $this->parseColumnCollationFromSQL($columnName, $createSql) ?? 'BINARY',
                );
            }

            $comment = $this->parseColumnCommentFromSQL($columnName, $createSql);

            if ($comment === null) {
                continue;
            }

            $type = $this->extractDoctrineTypeFromComment($comment, '');

            if ($type !== '') {
                $column->setType(Type::getType($type));

                $comment = $this->removeDoctrineTypeFromComment($comment, $type);
            }

            $column->setComment($comment);
        }

        return $list;
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $parts               = explode('(', $tableColumn['type']);
        $tableColumn['type'] = trim($parts[0]);
        if (isset($parts[1])) {
            $length                = trim($parts[1], ')');
            $tableColumn['length'] = $length;
        }

        $dbType   = strtolower($tableColumn['type']);
        $length   = $tableColumn['length'] ?? null;
        $unsigned = false;

        if (strpos($dbType, ' unsigned') !== false) {
            $dbType   = str_replace(' unsigned', '', $dbType);
            $unsigned = true;
        }

        $fixed   = false;
        $type    = $this->_platform->getDoctrineTypeMapping($dbType);
        $default = $tableColumn['dflt_value'];
        if ($default === 'NULL') {
            $default = null;
        }

        if ($default !== null) {
            // SQLite returns the default value as a literal expression, so we need to parse it
            if (preg_match('/^\'(.*)\'$/s', $default, $matches) === 1) {
                $default = str_replace("''", "'", $matches[1]);
            }
        }

        $notnull = (bool) $tableColumn['notnull'];

        if (! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

        $precision = null;
        $scale     = null;

        switch ($dbType) {
            case 'char':
                $fixed = true;
                break;
            case 'float':
            case 'double':
            case 'real':
            case 'decimal':
            case 'numeric':
                if (isset($tableColumn['length'])) {
                    if (strpos($tableColumn['length'], ',') === false) {
                        $tableColumn['length'] .= ',0';
                    }

                    [$precision, $scale] = array_map('trim', explode(',', $tableColumn['length']));
                }

                $length = null;
                break;
        }

        $options = [
            'length'    => $length,
            'unsigned'  => $unsigned,
            'fixed'     => $fixed,
            'notnull'   => $notnull,
            'default'   => $default,
            'precision' => $precision,
            'scale'     => $scale,
        ];

        return new Column($tableColumn['name'], Type::getType($type), $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableViewDefinition($view)
    {
        return new View($view['name'], $view['sql']);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $list = [];
        foreach ($tableForeignKeys as $value) {
            $value = array_change_key_case($value, CASE_LOWER);
            $id    = $value['id'];
            if (! isset($list[$id])) {
                if (! isset($value['on_delete']) || $value['on_delete'] === 'RESTRICT') {
                    $value['on_delete'] = null;
                }

                if (! isset($value['on_update']) || $value['on_update'] === 'RESTRICT') {
                    $value['on_update'] = null;
                }

                $list[$id] = [
                    'name' => $value['constraint_name'],
                    'local' => [],
                    'foreign' => [],
                    'foreignTable' => $value['table'],
                    'onDelete' => $value['on_delete'],
                    'onUpdate' => $value['on_update'],
                    'deferrable' => $value['deferrable'],
                    'deferred' => $value['deferred'],
                ];
            }

            $list[$id]['local'][] = $value['from'];

            if ($value['to'] === null) {
                // Inferring a shorthand form for the foreign key constraint, where the "to" field is empty.
                // @see https://www.sqlite.org/foreignkeys.html#fk_indexes.
                $foreignTableIndexes = $this->_getPortableTableIndexesList([], $value['table']);

                if (! isset($foreignTableIndexes['primary'])) {
                    continue;
                }

                $list[$id]['foreign'] = [...$list[$id]['foreign'], ...$foreignTableIndexes['primary']->getColumns()];

                continue;
            }

            $list[$id]['foreign'][] = $value['to'];
        }

        return parent::_getPortableTableForeignKeysList($list);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableForeignKeyDefinition($tableForeignKey): ForeignKeyConstraint
    {
        return new ForeignKeyConstraint(
            $tableForeignKey['local'],
            $tableForeignKey['foreignTable'],
            $tableForeignKey['foreign'],
            $tableForeignKey['name'],
            [
                'onDelete' => $tableForeignKey['onDelete'],
                'onUpdate' => $tableForeignKey['onUpdate'],
                'deferrable' => $tableForeignKey['deferrable'],
                'deferred' => $tableForeignKey['deferred'],
            ],
        );
    }

    private function parseColumnCollationFromSQL(string $column, string $sql): ?string
    {
        $pattern = '{' . $this->buildIdentifierPattern($column)
            . '[^,(]+(?:\([^()]+\)[^,]*)?(?:(?:DEFAULT|CHECK)\s*(?:\(.*?\))?[^,]*)*COLLATE\s+["\']?([^\s,"\')]+)}is';

        if (preg_match($pattern, $sql, $match) !== 1) {
            return null;
        }

        return $match[1];
    }

    private function parseTableCommentFromSQL(string $table, string $sql): ?string
    {
        $pattern = '/\s* # Allow whitespace characters at start of line
CREATE\sTABLE' . $this->buildIdentifierPattern($table) . '
( # Start capture
   (?:\s*--[^\n]*\n?)+ # Capture anything that starts with whitespaces followed by -- until the end of the line(s)
)/ix';

        if (preg_match($pattern, $sql, $match) !== 1) {
            return null;
        }

        $comment = preg_replace('{^\s*--}m', '', rtrim($match[1], "\n"));

        return $comment === '' ? null : $comment;
    }

    private function parseColumnCommentFromSQL(string $column, string $sql): ?string
    {
        $pattern = '{[\s(,]' . $this->buildIdentifierPattern($column)
            . '(?:\([^)]*?\)|[^,(])*?,?((?:(?!\n))(?:\s*--[^\n]*\n?)+)}i';

        if (preg_match($pattern, $sql, $match) !== 1) {
            return null;
        }

        $comment = preg_replace('{^\s*--}m', '', rtrim($match[1], "\n"));

        return $comment === '' ? null : $comment;
    }

    /**
     * Returns a regular expression pattern that matches the given unquoted or quoted identifier.
     */
    private function buildIdentifierPattern(string $identifier): string
    {
        return '(?:' . implode('|', array_map(
            static function (string $sql): string {
                return '\W' . preg_quote($sql, '/') . '\W';
            },
            [
                $identifier,
                $this->_platform->quoteSingleIdentifier($identifier),
            ],
        )) . ')';
    }

    /** @throws Exception */
    private function getCreateTableSQL(string $table): string
    {
        $sql = $this->_conn->fetchOne(
            <<<'SQL'
SELECT sql
  FROM (
      SELECT *
        FROM sqlite_master
   UNION ALL
      SELECT *
        FROM sqlite_temp_master
  )
WHERE type = 'table'
AND name = ?
SQL
            ,
            [$table],
        );

        if ($sql !== false) {
            return $sql;
        }

        return '';
    }

    /**
     * @param list<array<string,mixed>> $columns
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    private function addDetailsToTableForeignKeyColumns(string $table, array $columns): array
    {
        $foreignKeyDetails = $this->getForeignKeyDetails($table);
        $foreignKeyCount   = count($foreignKeyDetails);

        foreach ($columns as $i => $column) {
            // SQLite identifies foreign keys in reverse order of appearance in SQL
            $columns[$i] = array_merge($column, $foreignKeyDetails[$foreignKeyCount - $column['id'] - 1]);
        }

        return $columns;
    }

    /**
     * @param string $table
     *
     * @return list<array<string, mixed>>
     *
     * @throws Exception
     */
    private function getForeignKeyDetails($table)
    {
        $createSql = $this->getCreateTableSQL($table);

        if (
            preg_match_all(
                '#
                    (?:CONSTRAINT\s+(\S+)\s+)?
                    (?:FOREIGN\s+KEY[^)]+\)\s*)?
                    REFERENCES\s+\S+\s*(?:\([^)]+\))?
                    (?:
                        [^,]*?
                        (NOT\s+DEFERRABLE|DEFERRABLE)
                        (?:\s+INITIALLY\s+(DEFERRED|IMMEDIATE))?
                    )?#isx',
                $createSql,
                $match,
            ) === 0
        ) {
            return [];
        }

        $names      = $match[1];
        $deferrable = $match[2];
        $deferred   = $match[3];
        $details    = [];

        for ($i = 0, $count = count($match[0]); $i < $count; $i++) {
            $details[] = [
                'constraint_name' => isset($names[$i]) && $names[$i] !== '' ? $names[$i] : null,
                'deferrable'      => isset($deferrable[$i]) && strcasecmp($deferrable[$i], 'deferrable') === 0,
                'deferred'        => isset($deferred[$i]) && strcasecmp($deferred[$i], 'deferred') === 0,
            ];
        }

        return $details;
    }

    public function createComparator(): Comparator
    {
        return new SQLite\Comparator($this->_platform);
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function getSchemaSearchPaths()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4821',
            'SqliteSchemaManager::getSchemaSearchPaths() is deprecated.',
        );

        // SQLite does not support schemas or databases
        return [];
    }

    protected function selectTableNames(string $databaseName): Result
    {
        $sql = <<<'SQL'
SELECT name AS table_name
FROM sqlite_master
WHERE type = 'table'
  AND name != 'sqlite_sequence'
  AND name != 'geometry_columns'
  AND name != 'spatial_ref_sys'
UNION ALL
SELECT name
FROM sqlite_temp_master
WHERE type = 'table'
ORDER BY name
SQL;

        return $this->_conn->executeQuery($sql);
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = <<<'SQL'
            SELECT t.name AS table_name,
                   c.*
              FROM sqlite_master t
              JOIN pragma_table_info(t.name) c
SQL;

        $conditions = [
            "t.type = 'table'",
            "t.name NOT IN ('geometry_columns', 'spatial_ref_sys', 'sqlite_sequence')",
        ];
        $params     = [];

        if ($tableName !== null) {
            $conditions[] = 't.name = ?';
            $params[]     = $this->_platform->canEmulateSchemas()
                ? str_replace('.', '__', $tableName)
                : $tableName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY t.name, c.cid';

        return $this->_conn->executeQuery($sql, $params);
    }

    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = <<<'SQL'
            SELECT t.name AS table_name,
                   i.*
              FROM sqlite_master t
              JOIN pragma_index_list(t.name) i
SQL;

        $conditions = [
            "t.type = 'table'",
            "t.name NOT IN ('geometry_columns', 'spatial_ref_sys', 'sqlite_sequence')",
        ];
        $params     = [];

        if ($tableName !== null) {
            $conditions[] = 't.name = ?';
            $params[]     = $this->_platform->canEmulateSchemas()
                ? str_replace('.', '__', $tableName)
                : $tableName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY t.name, i.seq';

        return $this->_conn->executeQuery($sql, $params);
    }

    protected function selectForeignKeyColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = <<<'SQL'
            SELECT t.name AS table_name,
                   p.*
              FROM sqlite_master t
              JOIN pragma_foreign_key_list(t.name) p
                ON p."seq" != '-1'
SQL;

        $conditions = [
            "t.type = 'table'",
            "t.name NOT IN ('geometry_columns', 'spatial_ref_sys', 'sqlite_sequence')",
        ];
        $params     = [];

        if ($tableName !== null) {
            $conditions[] = 't.name = ?';
            $params[]     = $this->_platform->canEmulateSchemas()
                ? str_replace('.', '__', $tableName)
                : $tableName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY t.name, p.id DESC, p.seq';

        return $this->_conn->executeQuery($sql, $params);
    }

    /**
     * {@inheritDoc}
     */
    protected function fetchTableOptionsByTable(string $databaseName, ?string $tableName = null): array
    {
        if ($tableName === null) {
            $tables = $this->listTableNames();
        } else {
            $tables = [$tableName];
        }

        $tableOptions = [];
        foreach ($tables as $table) {
            $comment = $this->parseTableCommentFromSQL($table, $this->getCreateTableSQL($table));

            if ($comment === null) {
                continue;
            }

            $tableOptions[$table]['comment'] = $comment;
        }

        return $tableOptions;
    }
}
