<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;

use function array_change_key_case;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_values;
use function explode;
use function file_exists;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_replace;
use function strpos;
use function strtolower;
use function trim;
use function unlink;
use function usort;

use const CASE_LOWER;

/**
 * Sqlite SchemaManager.
 */
class SqliteSchemaManager extends AbstractSchemaManager
{
    /**
     * {@inheritdoc}
     */
    public function dropDatabase($database)
    {
        if (! file_exists($database)) {
            return;
        }

        unlink($database);
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabase($database)
    {
        $params  = $this->_conn->getParams();
        $driver  = $params['driver'];
        $options = [
            'driver' => $driver,
            'path' => $database,
        ];
        $conn    = DriverManager::getConnection($options);
        $conn->connect();
        $conn->close();
    }

    /**
     * {@inheritdoc}
     */
    public function renameTable($name, $newName)
    {
        $tableDiff            = new TableDiff($name);
        $tableDiff->fromTable = $this->listTableDetails($name);
        $tableDiff->newName   = $newName;
        $this->alterTable($tableDiff);
    }

    /**
     * {@inheritdoc}
     */
    public function createForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        $tableDiff                     = $this->getTableDiffForAlterForeignKey($table);
        $tableDiff->addedForeignKeys[] = $foreignKey;

        $this->alterTable($tableDiff);
    }

    /**
     * {@inheritdoc}
     */
    public function dropAndCreateForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        $tableDiff                       = $this->getTableDiffForAlterForeignKey($table);
        $tableDiff->changedForeignKeys[] = $foreignKey;

        $this->alterTable($tableDiff);
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeignKey($foreignKey, $table)
    {
        $tableDiff                       = $this->getTableDiffForAlterForeignKey($table);
        $tableDiff->removedForeignKeys[] = $foreignKey;

        $this->alterTable($tableDiff);
    }

    /**
     * {@inheritdoc}
     */
    public function listTableForeignKeys($table, $database = null)
    {
        if ($database === null) {
            $database = $this->_conn->getDatabase();
        }

        $sql              = $this->_platform->getListTableForeignKeysSQL($table, $database);
        $tableForeignKeys = $this->_conn->fetchAllAssociative($sql);

        if (! empty($tableForeignKeys)) {
            $createSql = $this->getCreateTableSQL($table);

            if (
                preg_match_all(
                    '#
                    (?:CONSTRAINT\s+([^\s]+)\s+)?
                    (?:FOREIGN\s+KEY[^\)]+\)\s*)?
                    REFERENCES\s+[^\s]+\s+(?:\([^\)]+\))?
                    (?:
                        [^,]*?
                        (NOT\s+DEFERRABLE|DEFERRABLE)
                        (?:\s+INITIALLY\s+(DEFERRED|IMMEDIATE))?
                    )?#isx',
                    $createSql,
                    $match
                ) > 0
            ) {
                $names      = array_reverse($match[1]);
                $deferrable = array_reverse($match[2]);
                $deferred   = array_reverse($match[3]);
            } else {
                $names = $deferrable = $deferred = [];
            }

            foreach ($tableForeignKeys as $key => $value) {
                $id = $value['id'];

                $tableForeignKeys[$key] = array_merge($tableForeignKeys[$key], [
                    'constraint_name' => isset($names[$id]) && $names[$id] !== '' ? $names[$id] : $id,
                    'deferrable'      => isset($deferrable[$id]) && strtolower($deferrable[$id]) === 'deferrable',
                    'deferred'        => isset($deferred[$id]) && strtolower($deferred[$id]) === 'deferred',
                ]);
            }
        }

        return $this->_getPortableTableForeignKeysList($tableForeignKeys);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableDefinition($table)
    {
        return $table['name'];
    }

    /**
     * {@inheritdoc}
     *
     * @link http://ezcomponents.org/docs/api/trunk/DatabaseSchema/ezcDbSchemaPgsqlReader.html
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        $indexBuffer = [];

        // fetch primary
        $indexArray = $this->_conn->fetchAllAssociative(sprintf(
            'PRAGMA TABLE_INFO (%s)',
            $this->_conn->quote($tableName)
        ));

        usort($indexArray, static function ($a, $b) {
            if ($a['pk'] === $b['pk']) {
                return $a['cid'] - $b['cid'];
            }

            return $a['pk'] - $b['pk'];
        });
        foreach ($indexArray as $indexColumnRow) {
            if ($indexColumnRow['pk'] === '0') {
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

            $indexArray = $this->_conn->fetchAllAssociative(sprintf(
                'PRAGMA INDEX_INFO (%s)',
                $this->_conn->quote($keyName)
            ));

            foreach ($indexArray as $indexColumnRow) {
                $idx['column_name'] = $indexColumnRow['name'];
                $indexBuffer[]      = $idx;
            }
        }

        return parent::_getPortableTableIndexesList($indexBuffer, $tableName);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableColumnList($table, $database, $tableColumns)
    {
        $list = parent::_getPortableTableColumnList($table, $database, $tableColumns);

        // find column with autoincrement
        $autoincrementColumn = null;
        $autoincrementCount  = 0;

        foreach ($tableColumns as $tableColumn) {
            if ($tableColumn['pk'] === '0') {
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
                    $this->parseColumnCollationFromSQL($columnName, $createSql) ?? 'BINARY'
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
     * {@inheritdoc}
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
            'length'   => $length,
            'unsigned' => (bool) $unsigned,
            'fixed'    => $fixed,
            'notnull'  => $notnull,
            'default'  => $default,
            'precision' => $precision,
            'scale'     => $scale,
            'autoincrement' => false,
        ];

        return new Column($tableColumn['name'], Type::getType($type), $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableViewDefinition($view)
    {
        return new View($view['name'], $view['sql']);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $list = [];
        foreach ($tableForeignKeys as $value) {
            $value = array_change_key_case($value, CASE_LOWER);
            $name  = $value['constraint_name'];
            if (! isset($list[$name])) {
                if (! isset($value['on_delete']) || $value['on_delete'] === 'RESTRICT') {
                    $value['on_delete'] = null;
                }

                if (! isset($value['on_update']) || $value['on_update'] === 'RESTRICT') {
                    $value['on_update'] = null;
                }

                $list[$name] = [
                    'name' => $name,
                    'local' => [],
                    'foreign' => [],
                    'foreignTable' => $value['table'],
                    'onDelete' => $value['on_delete'],
                    'onUpdate' => $value['on_update'],
                    'deferrable' => $value['deferrable'],
                    'deferred' => $value['deferred'],
                ];
            }

            $list[$name]['local'][]   = $value['from'];
            $list[$name]['foreign'][] = $value['to'];
        }

        $result = [];
        foreach ($list as $constraint) {
            $result[] = new ForeignKeyConstraint(
                array_values($constraint['local']),
                $constraint['foreignTable'],
                array_values($constraint['foreign']),
                $constraint['name'],
                [
                    'onDelete' => $constraint['onDelete'],
                    'onUpdate' => $constraint['onUpdate'],
                    'deferrable' => $constraint['deferrable'],
                    'deferred' => $constraint['deferred'],
                ]
            );
        }

        return $result;
    }

    /**
     * @param Table|string $table
     *
     * @return TableDiff
     *
     * @throws Exception
     */
    private function getTableDiffForAlterForeignKey($table)
    {
        if (! $table instanceof Table) {
            $tableDetails = $this->tryMethod('listTableDetails', $table);

            if ($tableDetails === false) {
                throw new Exception(
                    sprintf('Sqlite schema manager requires to modify foreign keys table definition "%s".', $table)
                );
            }

            $table = $tableDetails;
        }

        $tableDiff            = new TableDiff($table->getName());
        $tableDiff->fromTable = $table;

        return $tableDiff;
    }

    private function parseColumnCollationFromSQL(string $column, string $sql): ?string
    {
        $pattern = '{(?:\W' . preg_quote($column) . '\W|\W'
            . preg_quote($this->_platform->quoteSingleIdentifier($column))
            . '\W)[^,(]+(?:\([^()]+\)[^,]*)?(?:(?:DEFAULT|CHECK)\s*(?:\(.*?\))?[^,]*)*COLLATE\s+["\']?([^\s,"\')]+)}is';

        if (preg_match($pattern, $sql, $match) !== 1) {
            return null;
        }

        return $match[1];
    }

    private function parseTableCommentFromSQL(string $table, string $sql): ?string
    {
        $pattern = '/\s* # Allow whitespace characters at start of line
CREATE\sTABLE # Match "CREATE TABLE"
(?:\W"' . preg_quote($this->_platform->quoteSingleIdentifier($table), '/') . '"\W|\W' . preg_quote($table, '/')
            . '\W) # Match table name (quoted and unquoted)
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
        $pattern = '{[\s(,](?:\W' . preg_quote($this->_platform->quoteSingleIdentifier($column))
            . '\W|\W' . preg_quote($column) . '\W)(?:\([^)]*?\)|[^,(])*?,?((?:(?!\n))(?:\s*--[^\n]*\n?)+)}i';

        if (preg_match($pattern, $sql, $match) !== 1) {
            return null;
        }

        $comment = preg_replace('{^\s*--}m', '', rtrim($match[1], "\n"));

        return $comment === '' ? null : $comment;
    }

    /**
     * @throws Exception
     */
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
            [$table]
        );

        if ($sql !== false) {
            return $sql;
        }

        return '';
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     */
    public function listTableDetails($name): Table
    {
        $table = parent::listTableDetails($name);

        $tableCreateSql = $this->getCreateTableSQL($name);

        $comment = $this->parseTableCommentFromSQL($name, $tableCreateSql);

        if ($comment !== null) {
            $table->addOption('comment', $comment);
        }

        return $table;
    }
}
