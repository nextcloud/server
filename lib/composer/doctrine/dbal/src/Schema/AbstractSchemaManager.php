<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Throwable;

use function array_filter;
use function array_intersect;
use function array_map;
use function array_values;
use function assert;
use function call_user_func_array;
use function count;
use function func_get_args;
use function is_callable;
use function preg_match;
use function str_replace;
use function strtolower;

/**
 * Base class for schema managers. Schema managers are used to inspect and/or
 * modify the database schema/structure.
 */
abstract class AbstractSchemaManager
{
    /**
     * Holds instance of the Doctrine connection for this schema manager.
     *
     * @var Connection
     */
    protected $_conn;

    /**
     * Holds instance of the database platform used for this schema manager.
     *
     * @var AbstractPlatform
     */
    protected $_platform;

    public function __construct(Connection $connection, AbstractPlatform $platform)
    {
        $this->_conn     = $connection;
        $this->_platform = $platform;
    }

    /**
     * Returns the associated platform.
     *
     * @return AbstractPlatform
     */
    public function getDatabasePlatform()
    {
        return $this->_platform;
    }

    /**
     * Tries any method on the schema manager. Normally a method throws an
     * exception when your DBMS doesn't support it or if an error occurs.
     * This method allows you to try and method on your SchemaManager
     * instance and will return false if it does not work or is not supported.
     *
     * <code>
     * $result = $sm->tryMethod('dropView', 'view_name');
     * </code>
     *
     * @return mixed
     */
    public function tryMethod()
    {
        $args   = func_get_args();
        $method = $args[0];
        unset($args[0]);
        $args = array_values($args);

        $callback = [$this, $method];
        assert(is_callable($callback));

        try {
            return call_user_func_array($callback, $args);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Lists the available databases for this connection.
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function listDatabases()
    {
        $sql = $this->_platform->getListDatabasesSQL();

        $databases = $this->_conn->fetchAllAssociative($sql);

        return $this->_getPortableDatabasesList($databases);
    }

    /**
     * Returns a list of all namespaces in the current database.
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function listNamespaceNames()
    {
        $sql = $this->_platform->getListNamespacesSQL();

        $namespaces = $this->_conn->fetchAllAssociative($sql);

        return $this->getPortableNamespacesList($namespaces);
    }

    /**
     * Lists the available sequences for this connection.
     *
     * @param string|null $database
     *
     * @return Sequence[]
     *
     * @throws Exception
     */
    public function listSequences($database = null)
    {
        if ($database === null) {
            $database = $this->_conn->getDatabase();
        }

        $sql = $this->_platform->getListSequencesSQL($database);

        $sequences = $this->_conn->fetchAllAssociative($sql);

        return $this->filterAssetNames($this->_getPortableSequencesList($sequences));
    }

    /**
     * Lists the columns for a given table.
     *
     * In contrast to other libraries and to the old version of Doctrine,
     * this column definition does try to contain the 'primary' column for
     * the reason that it is not portable across different RDBMS. Use
     * {@see listTableIndexes($tableName)} to retrieve the primary key
     * of a table. Where a RDBMS specifies more details, these are held
     * in the platformDetails array.
     *
     * @param string      $table    The name of the table.
     * @param string|null $database
     *
     * @return Column[]
     *
     * @throws Exception
     */
    public function listTableColumns($table, $database = null)
    {
        if ($database === null) {
            $database = $this->_conn->getDatabase();
        }

        $sql = $this->_platform->getListTableColumnsSQL($table, $database);

        $tableColumns = $this->_conn->fetchAllAssociative($sql);

        return $this->_getPortableTableColumnList($table, $database, $tableColumns);
    }

    /**
     * Lists the indexes for a given table returning an array of Index instances.
     *
     * Keys of the portable indexes list are all lower-cased.
     *
     * @param string $table The name of the table.
     *
     * @return Index[]
     *
     * @throws Exception
     */
    public function listTableIndexes($table)
    {
        $sql = $this->_platform->getListTableIndexesSQL($table, $this->_conn->getDatabase());

        $tableIndexes = $this->_conn->fetchAllAssociative($sql);

        return $this->_getPortableTableIndexesList($tableIndexes, $table);
    }

    /**
     * Returns true if all the given tables exist.
     *
     * The usage of a string $tableNames is deprecated. Pass a one-element array instead.
     *
     * @param string|string[] $names
     *
     * @return bool
     *
     * @throws Exception
     */
    public function tablesExist($names)
    {
        $names = array_map('strtolower', (array) $names);

        return count($names) === count(array_intersect($names, array_map('strtolower', $this->listTableNames())));
    }

    /**
     * Returns a list of all tables in the current database.
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function listTableNames()
    {
        $sql = $this->_platform->getListTablesSQL();

        $tables     = $this->_conn->fetchAllAssociative($sql);
        $tableNames = $this->_getPortableTablesList($tables);

        return $this->filterAssetNames($tableNames);
    }

    /**
     * Filters asset names if they are configured to return only a subset of all
     * the found elements.
     *
     * @param mixed[] $assetNames
     *
     * @return mixed[]
     */
    protected function filterAssetNames($assetNames)
    {
        $filter = $this->_conn->getConfiguration()->getSchemaAssetsFilter();
        if ($filter === null) {
            return $assetNames;
        }

        return array_values(array_filter($assetNames, $filter));
    }

    /**
     * Lists the tables for this connection.
     *
     * @return Table[]
     *
     * @throws Exception
     */
    public function listTables()
    {
        $tableNames = $this->listTableNames();

        $tables = [];
        foreach ($tableNames as $tableName) {
            $tables[] = $this->listTableDetails($tableName);
        }

        return $tables;
    }

    /**
     * @param string $name
     *
     * @return Table
     *
     * @throws Exception
     */
    public function listTableDetails($name)
    {
        $columns     = $this->listTableColumns($name);
        $foreignKeys = [];

        if ($this->_platform->supportsForeignKeyConstraints()) {
            $foreignKeys = $this->listTableForeignKeys($name);
        }

        $indexes = $this->listTableIndexes($name);

        return new Table($name, $columns, $indexes, [], $foreignKeys);
    }

    /**
     * Lists the views this connection has.
     *
     * @return View[]
     *
     * @throws Exception
     */
    public function listViews()
    {
        $database = $this->_conn->getDatabase();
        $sql      = $this->_platform->getListViewsSQL($database);
        $views    = $this->_conn->fetchAllAssociative($sql);

        return $this->_getPortableViewsList($views);
    }

    /**
     * Lists the foreign keys for the given table.
     *
     * @param string      $table    The name of the table.
     * @param string|null $database
     *
     * @return ForeignKeyConstraint[]
     *
     * @throws Exception
     */
    public function listTableForeignKeys($table, $database = null)
    {
        if ($database === null) {
            $database = $this->_conn->getDatabase();
        }

        $sql              = $this->_platform->getListTableForeignKeysSQL($table, $database);
        $tableForeignKeys = $this->_conn->fetchAllAssociative($sql);

        return $this->_getPortableTableForeignKeysList($tableForeignKeys);
    }

    /* drop*() Methods */

    /**
     * Drops a database.
     *
     * NOTE: You can not drop the database this SchemaManager is currently connected to.
     *
     * @param string $database The name of the database to drop.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropDatabase($database)
    {
        $this->_execSql($this->_platform->getDropDatabaseSQL($database));
    }

    /**
     * Drops the given table.
     *
     * @param string $name The name of the table to drop.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropTable($name)
    {
        $this->_execSql($this->_platform->getDropTableSQL($name));
    }

    /**
     * Drops the index from the given table.
     *
     * @param Index|string $index The name of the index.
     * @param Table|string $table The name of the table.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropIndex($index, $table)
    {
        if ($index instanceof Index) {
            $index = $index->getQuotedName($this->_platform);
        }

        $this->_execSql($this->_platform->getDropIndexSQL($index, $table));
    }

    /**
     * Drops the constraint from the given table.
     *
     * @param Table|string $table The name of the table.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropConstraint(Constraint $constraint, $table)
    {
        $this->_execSql($this->_platform->getDropConstraintSQL($constraint, $table));
    }

    /**
     * Drops a foreign key from a table.
     *
     * @param ForeignKeyConstraint|string $foreignKey The name of the foreign key.
     * @param Table|string                $table      The name of the table with the foreign key.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropForeignKey($foreignKey, $table)
    {
        $this->_execSql($this->_platform->getDropForeignKeySQL($foreignKey, $table));
    }

    /**
     * Drops a sequence with a given name.
     *
     * @param string $name The name of the sequence to drop.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropSequence($name)
    {
        $this->_execSql($this->_platform->getDropSequenceSQL($name));
    }

    /**
     * Drops a view.
     *
     * @param string $name The name of the view.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropView($name)
    {
        $this->_execSql($this->_platform->getDropViewSQL($name));
    }

    /* create*() Methods */

    /**
     * Creates a new database.
     *
     * @param string $database The name of the database to create.
     *
     * @return void
     *
     * @throws Exception
     */
    public function createDatabase($database)
    {
        $this->_execSql($this->_platform->getCreateDatabaseSQL($database));
    }

    /**
     * Creates a new table.
     *
     * @return void
     *
     * @throws Exception
     */
    public function createTable(Table $table)
    {
        $createFlags = AbstractPlatform::CREATE_INDEXES | AbstractPlatform::CREATE_FOREIGNKEYS;
        $this->_execSql($this->_platform->getCreateTableSQL($table, $createFlags));
    }

    /**
     * Creates a new sequence.
     *
     * @param Sequence $sequence
     *
     * @return void
     *
     * @throws Exception
     */
    public function createSequence($sequence)
    {
        $this->_execSql($this->_platform->getCreateSequenceSQL($sequence));
    }

    /**
     * Creates a constraint on a table.
     *
     * @param Table|string $table
     *
     * @return void
     *
     * @throws Exception
     */
    public function createConstraint(Constraint $constraint, $table)
    {
        $this->_execSql($this->_platform->getCreateConstraintSQL($constraint, $table));
    }

    /**
     * Creates a new index on a table.
     *
     * @param Table|string $table The name of the table on which the index is to be created.
     *
     * @return void
     *
     * @throws Exception
     */
    public function createIndex(Index $index, $table)
    {
        $this->_execSql($this->_platform->getCreateIndexSQL($index, $table));
    }

    /**
     * Creates a new foreign key.
     *
     * @param ForeignKeyConstraint $foreignKey The ForeignKey instance.
     * @param Table|string         $table      The name of the table on which the foreign key is to be created.
     *
     * @return void
     *
     * @throws Exception
     */
    public function createForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        $this->_execSql($this->_platform->getCreateForeignKeySQL($foreignKey, $table));
    }

    /**
     * Creates a new view.
     *
     * @return void
     *
     * @throws Exception
     */
    public function createView(View $view)
    {
        $this->_execSql($this->_platform->getCreateViewSQL($view->getQuotedName($this->_platform), $view->getSql()));
    }

    /* dropAndCreate*() Methods */

    /**
     * Drops and creates a constraint.
     *
     * @see dropConstraint()
     * @see createConstraint()
     *
     * @param Table|string $table
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropAndCreateConstraint(Constraint $constraint, $table)
    {
        $this->tryMethod('dropConstraint', $constraint, $table);
        $this->createConstraint($constraint, $table);
    }

    /**
     * Drops and creates a new index on a table.
     *
     * @param Table|string $table The name of the table on which the index is to be created.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropAndCreateIndex(Index $index, $table)
    {
        $this->tryMethod('dropIndex', $index->getQuotedName($this->_platform), $table);
        $this->createIndex($index, $table);
    }

    /**
     * Drops and creates a new foreign key.
     *
     * @param ForeignKeyConstraint $foreignKey An associative array that defines properties
     *                                         of the foreign key to be created.
     * @param Table|string         $table      The name of the table on which the foreign key is to be created.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropAndCreateForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        $this->tryMethod('dropForeignKey', $foreignKey, $table);
        $this->createForeignKey($foreignKey, $table);
    }

    /**
     * Drops and create a new sequence.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropAndCreateSequence(Sequence $sequence)
    {
        $this->tryMethod('dropSequence', $sequence->getQuotedName($this->_platform));
        $this->createSequence($sequence);
    }

    /**
     * Drops and creates a new table.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropAndCreateTable(Table $table)
    {
        $this->tryMethod('dropTable', $table->getQuotedName($this->_platform));
        $this->createTable($table);
    }

    /**
     * Drops and creates a new database.
     *
     * @param string $database The name of the database to create.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropAndCreateDatabase($database)
    {
        $this->tryMethod('dropDatabase', $database);
        $this->createDatabase($database);
    }

    /**
     * Drops and creates a new view.
     *
     * @return void
     *
     * @throws Exception
     */
    public function dropAndCreateView(View $view)
    {
        $this->tryMethod('dropView', $view->getQuotedName($this->_platform));
        $this->createView($view);
    }

    /* alterTable() Methods */

    /**
     * Alters an existing tables schema.
     *
     * @return void
     *
     * @throws Exception
     */
    public function alterTable(TableDiff $tableDiff)
    {
        $queries = $this->_platform->getAlterTableSQL($tableDiff);

        foreach ($queries as $ddlQuery) {
            $this->_execSql($ddlQuery);
        }
    }

    /**
     * Renames a given table to another name.
     *
     * @param string $name    The current name of the table.
     * @param string $newName The new name of the table.
     *
     * @return void
     *
     * @throws Exception
     */
    public function renameTable($name, $newName)
    {
        $tableDiff          = new TableDiff($name);
        $tableDiff->newName = $newName;
        $this->alterTable($tableDiff);
    }

    /**
     * Methods for filtering return values of list*() methods to convert
     * the native DBMS data definition to a portable Doctrine definition
     */

    /**
     * @param mixed[] $databases
     *
     * @return string[]
     */
    protected function _getPortableDatabasesList($databases)
    {
        $list = [];
        foreach ($databases as $value) {
            $list[] = $this->_getPortableDatabaseDefinition($value);
        }

        return $list;
    }

    /**
     * Converts a list of namespace names from the native DBMS data definition to a portable Doctrine definition.
     *
     * @param array<int, array<string, mixed>> $namespaces The list of namespace names
     *                                                     in the native DBMS data definition.
     *
     * @return string[]
     */
    protected function getPortableNamespacesList(array $namespaces)
    {
        $namespacesList = [];

        foreach ($namespaces as $namespace) {
            $namespacesList[] = $this->getPortableNamespaceDefinition($namespace);
        }

        return $namespacesList;
    }

    /**
     * @param mixed $database
     *
     * @return mixed
     */
    protected function _getPortableDatabaseDefinition($database)
    {
        return $database;
    }

    /**
     * Converts a namespace definition from the native DBMS data definition to a portable Doctrine definition.
     *
     * @param array<string, mixed> $namespace The native DBMS namespace definition.
     *
     * @return mixed
     */
    protected function getPortableNamespaceDefinition(array $namespace)
    {
        return $namespace;
    }

    /**
     * @param mixed[][] $triggers
     *
     * @return mixed[][]
     */
    protected function _getPortableTriggersList($triggers)
    {
        $list = [];
        foreach ($triggers as $value) {
            $value = $this->_getPortableTriggerDefinition($value);

            if (! $value) {
                continue;
            }

            $list[] = $value;
        }

        return $list;
    }

    /**
     * @param mixed[] $trigger
     *
     * @return mixed
     */
    protected function _getPortableTriggerDefinition($trigger)
    {
        return $trigger;
    }

    /**
     * @param mixed[][] $sequences
     *
     * @return Sequence[]
     *
     * @throws Exception
     */
    protected function _getPortableSequencesList($sequences)
    {
        $list = [];

        foreach ($sequences as $value) {
            $list[] = $this->_getPortableSequenceDefinition($value);
        }

        return $list;
    }

    /**
     * @param mixed[] $sequence
     *
     * @return Sequence
     *
     * @throws Exception
     */
    protected function _getPortableSequenceDefinition($sequence)
    {
        throw Exception::notSupported('Sequences');
    }

    /**
     * Independent of the database the keys of the column list result are lowercased.
     *
     * The name of the created column instance however is kept in its case.
     *
     * @param string    $table        The name of the table.
     * @param string    $database
     * @param mixed[][] $tableColumns
     *
     * @return Column[]
     *
     * @throws Exception
     */
    protected function _getPortableTableColumnList($table, $database, $tableColumns)
    {
        $eventManager = $this->_platform->getEventManager();

        $list = [];
        foreach ($tableColumns as $tableColumn) {
            $column           = null;
            $defaultPrevented = false;

            if ($eventManager !== null && $eventManager->hasListeners(Events::onSchemaColumnDefinition)) {
                $eventArgs = new SchemaColumnDefinitionEventArgs($tableColumn, $table, $database, $this->_conn);
                $eventManager->dispatchEvent(Events::onSchemaColumnDefinition, $eventArgs);

                $defaultPrevented = $eventArgs->isDefaultPrevented();
                $column           = $eventArgs->getColumn();
            }

            if (! $defaultPrevented) {
                $column = $this->_getPortableTableColumnDefinition($tableColumn);
            }

            if ($column === null) {
                continue;
            }

            $name        = strtolower($column->getQuotedName($this->_platform));
            $list[$name] = $column;
        }

        return $list;
    }

    /**
     * Gets Table Column Definition.
     *
     * @param mixed[] $tableColumn
     *
     * @return Column
     *
     * @throws Exception
     */
    abstract protected function _getPortableTableColumnDefinition($tableColumn);

    /**
     * Aggregates and groups the index results according to the required data result.
     *
     * @param mixed[][]   $tableIndexes
     * @param string|null $tableName
     *
     * @return Index[]
     *
     * @throws Exception
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null)
    {
        $result = [];
        foreach ($tableIndexes as $tableIndex) {
            $indexName = $keyName = $tableIndex['key_name'];
            if ($tableIndex['primary']) {
                $keyName = 'primary';
            }

            $keyName = strtolower($keyName);

            if (! isset($result[$keyName])) {
                $options = [
                    'lengths' => [],
                ];

                if (isset($tableIndex['where'])) {
                    $options['where'] = $tableIndex['where'];
                }

                $result[$keyName] = [
                    'name' => $indexName,
                    'columns' => [],
                    'unique' => ! $tableIndex['non_unique'],
                    'primary' => $tableIndex['primary'],
                    'flags' => $tableIndex['flags'] ?? [],
                    'options' => $options,
                ];
            }

            $result[$keyName]['columns'][]            = $tableIndex['column_name'];
            $result[$keyName]['options']['lengths'][] = $tableIndex['length'] ?? null;
        }

        $eventManager = $this->_platform->getEventManager();

        $indexes = [];
        foreach ($result as $indexKey => $data) {
            $index            = null;
            $defaultPrevented = false;

            if ($eventManager !== null && $eventManager->hasListeners(Events::onSchemaIndexDefinition)) {
                $eventArgs = new SchemaIndexDefinitionEventArgs($data, $tableName, $this->_conn);
                $eventManager->dispatchEvent(Events::onSchemaIndexDefinition, $eventArgs);

                $defaultPrevented = $eventArgs->isDefaultPrevented();
                $index            = $eventArgs->getIndex();
            }

            if (! $defaultPrevented) {
                $index = new Index(
                    $data['name'],
                    $data['columns'],
                    $data['unique'],
                    $data['primary'],
                    $data['flags'],
                    $data['options']
                );
            }

            if ($index === null) {
                continue;
            }

            $indexes[$indexKey] = $index;
        }

        return $indexes;
    }

    /**
     * @param mixed[][] $tables
     *
     * @return string[]
     */
    protected function _getPortableTablesList($tables)
    {
        $list = [];
        foreach ($tables as $value) {
            $list[] = $this->_getPortableTableDefinition($value);
        }

        return $list;
    }

    /**
     * @param mixed $table
     *
     * @return string
     */
    protected function _getPortableTableDefinition($table)
    {
        return $table;
    }

    /**
     * @param mixed[][] $users
     *
     * @return string[][]
     */
    protected function _getPortableUsersList($users)
    {
        $list = [];
        foreach ($users as $value) {
            $list[] = $this->_getPortableUserDefinition($value);
        }

        return $list;
    }

    /**
     * @param string[] $user
     *
     * @return string[]
     */
    protected function _getPortableUserDefinition($user)
    {
        return $user;
    }

    /**
     * @param mixed[][] $views
     *
     * @return View[]
     */
    protected function _getPortableViewsList($views)
    {
        $list = [];
        foreach ($views as $value) {
            $view = $this->_getPortableViewDefinition($value);

            if ($view === false) {
                continue;
            }

            $viewName        = strtolower($view->getQuotedName($this->_platform));
            $list[$viewName] = $view;
        }

        return $list;
    }

    /**
     * @param mixed[] $view
     *
     * @return View|false
     */
    protected function _getPortableViewDefinition($view)
    {
        return false;
    }

    /**
     * @param mixed[][] $tableForeignKeys
     *
     * @return ForeignKeyConstraint[]
     */
    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $list = [];

        foreach ($tableForeignKeys as $value) {
            $list[] = $this->_getPortableTableForeignKeyDefinition($value);
        }

        return $list;
    }

    /**
     * @param mixed $tableForeignKey
     *
     * @return ForeignKeyConstraint
     */
    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        return $tableForeignKey;
    }

    /**
     * @param string[]|string $sql
     *
     * @return void
     *
     * @throws Exception
     */
    protected function _execSql($sql)
    {
        foreach ((array) $sql as $query) {
            $this->_conn->executeStatement($query);
        }
    }

    /**
     * Creates a schema instance for the current database.
     *
     * @return Schema
     *
     * @throws Exception
     */
    public function createSchema()
    {
        $namespaces = [];

        if ($this->_platform->supportsSchemas()) {
            $namespaces = $this->listNamespaceNames();
        }

        $sequences = [];

        if ($this->_platform->supportsSequences()) {
            $sequences = $this->listSequences();
        }

        $tables = $this->listTables();

        return new Schema($tables, $sequences, $this->createSchemaConfig(), $namespaces);
    }

    /**
     * Creates the configuration for this schema.
     *
     * @return SchemaConfig
     *
     * @throws Exception
     */
    public function createSchemaConfig()
    {
        $schemaConfig = new SchemaConfig();
        $schemaConfig->setMaxIdentifierLength($this->_platform->getMaxIdentifierLength());

        $searchPaths = $this->getSchemaSearchPaths();
        if (isset($searchPaths[0])) {
            $schemaConfig->setName($searchPaths[0]);
        }

        $params = $this->_conn->getParams();
        if (! isset($params['defaultTableOptions'])) {
            $params['defaultTableOptions'] = [];
        }

        if (! isset($params['defaultTableOptions']['charset']) && isset($params['charset'])) {
            $params['defaultTableOptions']['charset'] = $params['charset'];
        }

        $schemaConfig->setDefaultTableOptions($params['defaultTableOptions']);

        return $schemaConfig;
    }

    /**
     * The search path for namespaces in the currently connected database.
     *
     * The first entry is usually the default namespace in the Schema. All
     * further namespaces contain tables/sequences which can also be addressed
     * with a short, not full-qualified name.
     *
     * For databases that don't support subschema/namespaces this method
     * returns the name of the currently connected database.
     *
     * @return string[]
     *
     * @throws Exception
     */
    public function getSchemaSearchPaths()
    {
        $database = $this->_conn->getDatabase();

        if ($database !== null) {
            return [$database];
        }

        return [];
    }

    /**
     * Given a table comment this method tries to extract a typehint for Doctrine Type, or returns
     * the type given as default.
     *
     * @param string|null $comment
     * @param string      $currentType
     *
     * @return string
     */
    public function extractDoctrineTypeFromComment($comment, $currentType)
    {
        if ($comment !== null && preg_match('(\(DC2Type:(((?!\)).)+)\))', $comment, $match) === 1) {
            return $match[1];
        }

        return $currentType;
    }

    /**
     * @param string|null $comment
     * @param string|null $type
     *
     * @return string|null
     */
    public function removeDoctrineTypeFromComment($comment, $type)
    {
        if ($comment === null) {
            return null;
        }

        return str_replace('(DC2Type:' . $type . ')', '', $comment);
    }
}
