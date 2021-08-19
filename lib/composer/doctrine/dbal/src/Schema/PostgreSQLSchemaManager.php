<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

use function array_change_key_case;
use function array_filter;
use function array_keys;
use function array_map;
use function array_shift;
use function assert;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function trim;

use const CASE_LOWER;

/**
 * PostgreSQL Schema Manager.
 */
class PostgreSQLSchemaManager extends AbstractSchemaManager
{
    /** @var string[] */
    private $existingSchemaPaths;

    /**
     * Gets all the existing schema names.
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function getSchemaNames()
    {
        return $this->_conn->fetchFirstColumn(
            "SELECT nspname FROM pg_namespace WHERE nspname !~ '^pg_.*' AND nspname != 'information_schema'"
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getSchemaSearchPaths()
    {
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
     * @return string[]
     */
    public function getExistingSchemaSearchPaths()
    {
        if ($this->existingSchemaPaths === null) {
            $this->determineExistingSchemaSearchPaths();
        }

        return $this->existingSchemaPaths;
    }

    /**
     * Sets or resets the order of the existing schemas in the current search path of the user.
     *
     * This is a PostgreSQL only function.
     *
     * @return void
     */
    public function determineExistingSchemaSearchPaths()
    {
        $names = $this->getSchemaNames();
        $paths = $this->getSchemaSearchPaths();

        $this->existingSchemaPaths = array_filter($paths, static function ($v) use ($names): bool {
            return in_array($v, $names, true);
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        $onUpdate       = null;
        $onDelete       = null;
        $localColumns   = [];
        $foreignColumns = [];
        $foreignTable   = null;

        if (
            preg_match(
                '(ON UPDATE ([a-zA-Z0-9]+( (NULL|ACTION|DEFAULT))?))',
                $tableForeignKey['condef'],
                $match
            ) === 1
        ) {
            $onUpdate = $match[1];
        }

        if (
            preg_match(
                '(ON DELETE ([a-zA-Z0-9]+( (NULL|ACTION|DEFAULT))?))',
                $tableForeignKey['condef'],
                $match
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
            ['onUpdate' => $onUpdate, 'onDelete' => $onDelete]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTriggerDefinition($trigger)
    {
        return $trigger['trigger_name'];
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableViewDefinition($view)
    {
        return new View($view['schemaname'] . '.' . $view['viewname'], $view['definition']);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableUserDefinition($user)
    {
        return [
            'user' => $user['usename'],
            'password' => $user['passwd'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableDefinition($table)
    {
        $schemas     = $this->getExistingSchemaSearchPaths();
        $firstSchema = array_shift($schemas);

        if ($table['schema_name'] === $firstSchema) {
            return $table['table_name'];
        }

        return $table['schema_name'] . '.' . $table['table_name'];
    }

    /**
     * {@inheritdoc}
     *
     * @link http://ezcomponents.org/docs/api/trunk/DatabaseSchema/ezcDbSchemaPgsqlReader.html
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        $buffer = [];
        foreach ($tableIndexes as $row) {
            $colNumbers    = array_map('intval', explode(' ', $row['indkey']));
            $columnNameSql = sprintf(
                'SELECT attnum, attname FROM pg_attribute WHERE attrelid=%d AND attnum IN (%s) ORDER BY attnum ASC',
                $row['indrelid'],
                implode(' ,', $colNumbers)
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
     * {@inheritdoc}
     */
    protected function _getPortableDatabaseDefinition($database)
    {
        return $database['datname'];
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableSequencesList($sequences)
    {
        $sequenceDefinitions = [];

        foreach ($sequences as $sequence) {
            if ($sequence['schemaname'] !== 'public') {
                $sequenceName = $sequence['schemaname'] . '.' . $sequence['relname'];
            } else {
                $sequenceName = $sequence['relname'];
            }

            $sequenceDefinitions[$sequenceName] = $sequence;
        }

        $list = [];

        foreach ($this->filterAssetNames(array_keys($sequenceDefinitions)) as $sequenceName) {
            $list[] = $this->_getPortableSequenceDefinition($sequenceDefinitions[$sequenceName]);
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPortableNamespaceDefinition(array $namespace)
    {
        return $namespace['nspname'];
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableSequenceDefinition($sequence)
    {
        if ($sequence['schemaname'] !== 'public') {
            $sequenceName = $sequence['schemaname'] . '.' . $sequence['relname'];
        } else {
            $sequenceName = $sequence['relname'];
        }

        if (! isset($sequence['increment_by'], $sequence['min_value'])) {
            /** @var string[] $data */
            $data = $this->_conn->fetchAssociative(
                'SELECT min_value, increment_by FROM ' . $this->_platform->quoteIdentifier($sequenceName)
            );

            $sequence += $data;
        }

        return new Sequence($sequenceName, (int) $sequence['increment_by'], (int) $sequence['min_value']);
    }

    /**
     * {@inheritdoc}
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
        if (preg_match("/^nextval\('(.*)'(::.*)?\)$/", $tableColumn['default'], $matches) === 1) {
            $tableColumn['sequence'] = $matches[1];
            $tableColumn['default']  = null;
            $autoincrement           = true;
        }

        if (preg_match("/^['(](.*)[')]::/", $tableColumn['default'], $matches) === 1) {
            $tableColumn['default'] = $matches[1];
        } elseif (preg_match('/^NULL::/', $tableColumn['default']) === 1) {
            $tableColumn['default'] = null;
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
            strlen($tableColumn['domain_type']) > 0
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
                        '([A-Za-z]+\(([0-9]+)\,([0-9]+)\))',
                        $tableColumn['complete_type'],
                        $match
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
                $match
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
            'unsigned'      => false,
            'autoincrement' => $autoincrement,
            'comment'       => isset($tableColumn['comment']) && $tableColumn['comment'] !== ''
                ? $tableColumn['comment']
                : null,
        ];

        $column = new Column($tableColumn['field'], Type::getType($type), $options);

        if (isset($tableColumn['collation']) && ! empty($tableColumn['collation'])) {
            $column->setPlatformOption('collation', $tableColumn['collation']);
        }

        if ($column->getType()->getName() === Types::JSON) {
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
        if (strpos($defaultValue, '(') === 0) {
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

    /**
     * {@inheritdoc}
     */
    public function listTableDetails($name): Table
    {
        $table = parent::listTableDetails($name);

        $platform = $this->_platform;
        assert($platform instanceof PostgreSQL94Platform);
        $sql = $platform->getListTableMetadataSQL($name);

        $tableOptions = $this->_conn->fetchAssociative($sql);

        if ($tableOptions !== false) {
            $table->addOption('comment', $tableOptions['table_comment']);
        }

        return $table;
    }
}
