<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;

use function array_change_key_case;
use function array_filter;
use function array_map;
use function array_merge;
use function array_shift;
use function assert;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strpos;
use function strtolower;
use function trim;

use const CASE_LOWER;

/**
 * PostgreSQL Schema Manager.
 *
 * @extends AbstractSchemaManager<PostgreSQLPlatform>
 */
class PostgreSQLSchemaManager extends AbstractSchemaManager
{
    /** @var string[]|null */
    private ?array $existingSchemaPaths = null;

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
     * Gets all the existing schema names.
     *
     * @deprecated Use {@see listSchemaNames()} instead.
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function getSchemaNames()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4503',
            'PostgreSQLSchemaManager::getSchemaNames() is deprecated,'
                . ' use PostgreSQLSchemaManager::listSchemaNames() instead.',
        );

        return $this->listNamespaceNames();
    }

    /**
     * {@inheritDoc}
     */
    public function listSchemaNames(): array
    {
        return $this->_conn->fetchFirstColumn(
            <<<'SQL'
SELECT schema_name
FROM   information_schema.schemata
WHERE  schema_name NOT LIKE 'pg\_%'
AND    schema_name != 'information_schema'
SQL,
        );
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
            'PostgreSQLSchemaManager::getSchemaSearchPaths() is deprecated.',
        );

        $params = $this->_conn->getParams();

        $searchPaths = $this->_conn->fetchOne('SHOW search_path');
        assert($searchPaths !== false);

        $schema = explode(',', $searchPaths);

        if (isset($params['user'])) {
            $schema = str_replace('"$user"', $params['user'], $schema);
        }

        return array_map('trim', $schema);
    }

    /**
     * Gets names of all existing schemas in the current users search path.
     *
     * This is a PostgreSQL only function.
     *
     * @internal The method should be only used from within the PostgreSQLSchemaManager class hierarchy.
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function getExistingSchemaSearchPaths()
    {
        if ($this->existingSchemaPaths === null) {
            $this->determineExistingSchemaSearchPaths();
        }

        assert($this->existingSchemaPaths !== null);

        return $this->existingSchemaPaths;
    }

    /**
     * Returns the name of the current schema.
     *
     * @return string|null
     *
     * @throws Exception
     */
    protected function getCurrentSchema()
    {
        $schemas = $this->getExistingSchemaSearchPaths();

        return array_shift($schemas);
    }

    /**
     * Sets or resets the order of the existing schemas in the current search path of the user.
     *
     * This is a PostgreSQL only function.
     *
     * @internal The method should be only used from within the PostgreSQLSchemaManager class hierarchy.
     *
     * @return void
     *
     * @throws Exception
     */
    public function determineExistingSchemaSearchPaths()
    {
        $names = $this->listSchemaNames();
        $paths = $this->getSchemaSearchPaths();

        $this->existingSchemaPaths = array_filter($paths, static function ($v) use ($names): bool {
            return in_array($v, $names, true);
        });
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        $onUpdate = null;
        $onDelete = null;

        if (
            preg_match(
                '(ON UPDATE ([a-zA-Z0-9]+( (NULL|ACTION|DEFAULT))?))',
                $tableForeignKey['condef'],
                $match,
            ) === 1
        ) {
            $onUpdate = $match[1];
        }

        if (
            preg_match(
                '(ON DELETE ([a-zA-Z0-9]+( (NULL|ACTION|DEFAULT))?))',
                $tableForeignKey['condef'],
                $match,
            ) === 1
        ) {
            $onDelete = $match[1];
        }

        $result = preg_match('/FOREIGN KEY \((.+)\) REFERENCES (.+)\((.+)\)/', $tableForeignKey['condef'], $values);
        assert($result === 1);

        // PostgreSQL returns identifiers that are keywords with quotes, we need them later, don't get
        // the idea to trim them here.
        $localColumns   = array_map('trim', explode(',', $values[1]));
        $foreignColumns = array_map('trim', explode(',', $values[3]));
        $foreignTable   = $values[2];

        return new ForeignKeyConstraint(
            $localColumns,
            $foreignTable,
            $foreignColumns,
            $tableForeignKey['conname'],
            ['onUpdate' => $onUpdate, 'onDelete' => $onDelete],
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableViewDefinition($view)
    {
        return new View($view['schemaname'] . '.' . $view['viewname'], $view['definition']);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableDefinition($table)
    {
        $currentSchema = $this->getCurrentSchema();

        if ($table['schema_name'] === $currentSchema) {
            return $table['table_name'];
        }

        return $table['schema_name'] . '.' . $table['table_name'];
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        $buffer = [];
        foreach ($tableIndexes as $row) {
            $colNumbers    = array_map('intval', explode(' ', $row['indkey']));
            $columnNameSql = sprintf(
                <<<'SQL'
                SELECT attnum,
                       quote_ident(attname) AS attname
                FROM pg_attribute
                WHERE attrelid = %d
                  AND attnum IN (%s)
                ORDER BY attnum
                SQL,
                $row['indrelid'],
                implode(', ', $colNumbers),
            );

            $indexColumns = $this->_conn->fetchAllAssociative($columnNameSql);

            // required for getting the order of the columns right.
            foreach ($colNumbers as $colNum) {
                foreach ($indexColumns as $colRow) {
                    if ($colNum !== $colRow['attnum']) {
                        continue;
                    }

                    $buffer[] = [
                        'key_name' => $row['relname'],
                        'column_name' => trim($colRow['attname']),
                        'non_unique' => ! $row['indisunique'],
                        'primary' => $row['indisprimary'],
                        'where' => $row['where'],
                    ];
                }
            }
        }

        return parent::_getPortableTableIndexesList($buffer, $tableName);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableDatabaseDefinition($database)
    {
        return $database['datname'];
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
            'PostgreSQLSchemaManager::getPortableNamespaceDefinition() is deprecated,'
                . ' use PostgreSQLSchemaManager::listSchemaNames() instead.',
        );

        return $namespace['nspname'];
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableSequenceDefinition($sequence)
    {
        if ($sequence['schemaname'] !== 'public') {
            $sequenceName = $sequence['schemaname'] . '.' . $sequence['relname'];
        } else {
            $sequenceName = $sequence['relname'];
        }

        return new Sequence($sequenceName, (int) $sequence['increment_by'], (int) $sequence['min_value']);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        if (strtolower($tableColumn['type']) === 'varchar' || strtolower($tableColumn['type']) === 'bpchar') {
            // get length from varchar definition
            $length                = preg_replace('~.*\(([0-9]*)\).*~', '$1', $tableColumn['complete_type']);
            $tableColumn['length'] = $length;
        }

        $matches = [];

        $autoincrement = false;

        if (
            $tableColumn['default'] !== null
            && preg_match("/^nextval\('(.*)'(::.*)?\)$/", $tableColumn['default'], $matches) === 1
        ) {
            $tableColumn['sequence'] = $matches[1];
            $tableColumn['default']  = null;
            $autoincrement           = true;
        }

        if ($tableColumn['default'] !== null) {
            if (preg_match("/^['(](.*)[')]::/", $tableColumn['default'], $matches) === 1) {
                $tableColumn['default'] = $matches[1];
            } elseif (preg_match('/^NULL::/', $tableColumn['default']) === 1) {
                $tableColumn['default'] = null;
            }
        }

        $length = $tableColumn['length'] ?? null;
        if ($length === '-1' && isset($tableColumn['atttypmod'])) {
            $length = $tableColumn['atttypmod'] - 4;
        }

        if ((int) $length <= 0) {
            $length = null;
        }

        $fixed = null;

        if (! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

        $precision = null;
        $scale     = null;
        $jsonb     = null;

        $dbType = strtolower($tableColumn['type']);
        if (
            $tableColumn['domain_type'] !== null
            && $tableColumn['domain_type'] !== ''
            && ! $this->_platform->hasDoctrineTypeMappingFor($tableColumn['type'])
        ) {
            $dbType                       = strtolower($tableColumn['domain_type']);
            $tableColumn['complete_type'] = $tableColumn['domain_complete_type'];
        }

        $type                   = $this->_platform->getDoctrineTypeMapping($dbType);
        $type                   = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type);
        $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type);

        switch ($dbType) {
            case 'smallint':
            case 'int2':
                $tableColumn['default'] = $this->fixVersion94NegativeNumericDefaultValue($tableColumn['default']);
                $length                 = null;
                break;

            case 'int':
            case 'int4':
            case 'integer':
                $tableColumn['default'] = $this->fixVersion94NegativeNumericDefaultValue($tableColumn['default']);
                $length                 = null;
                break;

            case 'bigint':
            case 'int8':
                $tableColumn['default'] = $this->fixVersion94NegativeNumericDefaultValue($tableColumn['default']);
                $length                 = null;
                break;

            case 'bool':
            case 'boolean':
                if ($tableColumn['default'] === 'true') {
                    $tableColumn['default'] = true;
                }

                if ($tableColumn['default'] === 'false') {
                    $tableColumn['default'] = false;
                }

                $length = null;
                break;

            case 'json':
            case 'text':
            case '_varchar':
            case 'varchar':
                $tableColumn['default'] = $this->parseDefaultExpression($tableColumn['default']);
                $fixed                  = false;
                break;
            case 'interval':
                $fixed = false;
                break;

            case 'char':
            case 'bpchar':
                $fixed = true;
                break;

            case 'float':
            case 'float4':
            case 'float8':
            case 'double':
            case 'double precision':
            case 'real':
            case 'decimal':
            case 'money':
            case 'numeric':
                $tableColumn['default'] = $this->fixVersion94NegativeNumericDefaultValue($tableColumn['default']);

                if (
                    preg_match(
                        '([A-Za-z]+\(([0-9]+),([0-9]+)\))',
                        $tableColumn['complete_type'],
                        $match,
                    ) === 1
                ) {
                    $precision = $match[1];
                    $scale     = $match[2];
                    $length    = null;
                }

                break;

            case 'year':
                $length = null;
                break;

            // PostgreSQL 9.4+ only
            case 'jsonb':
                $jsonb = true;
                break;
        }

        if (
            $tableColumn['default'] !== null && preg_match(
                "('([^']+)'::)",
                $tableColumn['default'],
                $match,
            ) === 1
        ) {
            $tableColumn['default'] = $match[1];
        }

        $options = [
            'length'        => $length,
            'notnull'       => (bool) $tableColumn['isnotnull'],
            'default'       => $tableColumn['default'],
            'precision'     => $precision,
            'scale'         => $scale,
            'fixed'         => $fixed,
            'autoincrement' => $autoincrement,
            'comment'       => isset($tableColumn['comment']) && $tableColumn['comment'] !== ''
                ? $tableColumn['comment']
                : null,
        ];

        $column = new Column($tableColumn['field'], Type::getType($type), $options);

        if (! empty($tableColumn['collation'])) {
            $column->setPlatformOption('collation', $tableColumn['collation']);
        }

        if ($column->getType()->getName() === Types::JSON) {
            if (! $column->getType() instanceof JsonType) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5049',
                    <<<'DEPRECATION'
                    %s not extending %s while being named %s is deprecated,
                    and will lead to jsonb never to being used in 4.0.,
                    DEPRECATION,
                    get_class($column->getType()),
                    JsonType::class,
                    Types::JSON,
                );
            }

            $column->setPlatformOption('jsonb', $jsonb);
        }

        return $column;
    }

    /**
     * PostgreSQL 9.4 puts parentheses around negative numeric default values that need to be stripped eventually.
     *
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    private function fixVersion94NegativeNumericDefaultValue($defaultValue)
    {
        if ($defaultValue !== null && strpos($defaultValue, '(') === 0) {
            return trim($defaultValue, '()');
        }

        return $defaultValue;
    }

    /**
     * Parses a default value expression as given by PostgreSQL
     */
    private function parseDefaultExpression(?string $default): ?string
    {
        if ($default === null) {
            return $default;
        }

        return str_replace("''", "'", $default);
    }

    protected function selectTableNames(string $databaseName): Result
    {
        $sql = <<<'SQL'
SELECT quote_ident(table_name) AS table_name,
       table_schema AS schema_name
FROM information_schema.tables
WHERE table_catalog = ?
  AND table_schema NOT LIKE 'pg\_%'
  AND table_schema != 'information_schema'
  AND table_name != 'geometry_columns'
  AND table_name != 'spatial_ref_sys'
  AND table_type = 'BASE TABLE'
ORDER BY
  quote_ident(table_name)
SQL;

        return $this->_conn->executeQuery($sql, [$databaseName]);
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' quote_ident(c.relname) AS table_name, quote_ident(n.nspname) AS schema_name,';
        }

        $sql .= sprintf(<<<'SQL'
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
            (%s) AS default,
            (SELECT pg_description.description
                FROM pg_description WHERE pg_description.objoid = c.oid AND a.attnum = pg_description.objsubid
            ) AS comment
            FROM pg_attribute a
                INNER JOIN pg_class c
                    ON c.oid = a.attrelid
                INNER JOIN pg_type t
                    ON t.oid = a.atttypid
                INNER JOIN pg_namespace n
                    ON n.oid = c.relnamespace
                LEFT JOIN pg_depend d
                    ON d.objid = c.oid
                        AND d.deptype = 'e'
                        AND d.classid = (SELECT oid FROM pg_class WHERE relname = 'pg_class')
SQL, $this->_platform->getDefaultColumnValueSQLSnippet());

        $conditions = array_merge([
            'a.attnum > 0',
            "c.relkind = 'r'",
            'd.refobjid IS NULL',
        ], $this->buildQueryConditions($tableName));

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY a.attnum';

        return $this->_conn->executeQuery($sql);
    }

    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' quote_ident(tc.relname) AS table_name, quote_ident(tn.nspname) AS schema_name,';
        }

        $sql .= <<<'SQL'
                   quote_ident(ic.relname) AS relname,
                   i.indisunique,
                   i.indisprimary,
                   i.indkey,
                   i.indrelid,
                   pg_get_expr(indpred, indrelid) AS "where"
              FROM pg_index i
                   JOIN pg_class AS tc ON tc.oid = i.indrelid
                   JOIN pg_namespace tn ON tn.oid = tc.relnamespace
                   JOIN pg_class AS ic ON ic.oid = i.indexrelid
             WHERE ic.oid IN (
                SELECT indexrelid
                FROM pg_index i, pg_class c, pg_namespace n
SQL;

        $conditions = array_merge([
            'c.oid = i.indrelid',
            'c.relnamespace = n.oid',
        ], $this->buildQueryConditions($tableName));

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ') ORDER BY quote_ident(ic.relname)';

        return $this->_conn->executeQuery($sql);
    }

    protected function selectForeignKeyColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' quote_ident(tc.relname) AS table_name, quote_ident(tn.nspname) AS schema_name,';
        }

        $sql .= <<<'SQL'
                  quote_ident(r.conname) as conname,
                  pg_get_constraintdef(r.oid, true) as condef
                  FROM pg_constraint r
                      JOIN pg_class AS tc ON tc.oid = r.conrelid
                      JOIN pg_namespace tn ON tn.oid = tc.relnamespace
                  WHERE r.conrelid IN
                  (
                      SELECT c.oid
                      FROM pg_class c, pg_namespace n
SQL;

        $conditions = array_merge(['n.oid = c.relnamespace'], $this->buildQueryConditions($tableName));

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ") AND r.contype = 'f' ORDER BY quote_ident(r.conname)";

        return $this->_conn->executeQuery($sql);
    }

    /**
     * {@inheritDoc}
     */
    protected function fetchTableOptionsByTable(string $databaseName, ?string $tableName = null): array
    {
        $sql = <<<'SQL'
SELECT n.nspname AS schema_name,
       c.relname AS table_name,
       CASE c.relpersistence WHEN 'u' THEN true ELSE false END as unlogged,
       obj_description(c.oid, 'pg_class') AS comment
FROM pg_class c
     INNER JOIN pg_namespace n
         ON n.oid = c.relnamespace
SQL;

        $conditions = array_merge(["c.relkind = 'r'"], $this->buildQueryConditions($tableName));

        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        $tableOptions = [];
        foreach ($this->_conn->iterateAssociative($sql) as $row) {
            $tableOptions[$this->_getPortableTableDefinition($row)] = $row;
        }

        return $tableOptions;
    }

    /**
     * @param string|null $tableName
     *
     * @return list<string>
     */
    private function buildQueryConditions($tableName): array
    {
        $conditions = [];

        if ($tableName !== null) {
            if (strpos($tableName, '.') !== false) {
                [$schemaName, $tableName] = explode('.', $tableName);
                $conditions[]             = 'n.nspname = ' . $this->_platform->quoteStringLiteral($schemaName);
            } else {
                $conditions[] = 'n.nspname = ANY(current_schemas(false))';
            }

            $identifier   = new Identifier($tableName);
            $conditions[] = 'c.relname = ' . $this->_platform->quoteStringLiteral($identifier->getName());
        }

        $conditions[] = "n.nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')";

        return $conditions;
    }
}
