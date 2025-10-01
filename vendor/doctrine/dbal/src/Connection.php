<?php

namespace Doctrine\DBAL;

use Closure;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\ArrayResult;
use Doctrine\DBAL\Cache\CacheException;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Exception as TheDriverException;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\Event\TransactionBeginEventArgs;
use Doctrine\DBAL\Event\TransactionCommitEventArgs;
use Doctrine\DBAL\Event\TransactionRollBackEventArgs;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\TransactionRolledBack;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Schema\LegacySchemaManagerFactory;
use Doctrine\DBAL\Schema\SchemaManagerFactory;
use Doctrine\DBAL\SQL\Parser;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;
use LogicException;
use SensitiveParameter;
use Throwable;
use Traversable;

use function array_key_exists;
use function assert;
use function count;
use function get_class;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function key;
use function method_exists;
use function sprintf;

/**
 * A database abstraction-level connection that implements features like events, transaction isolation levels,
 * configuration, emulated transaction nesting, lazy connecting and more.
 *
 * @phpstan-import-type Params from DriverManager
 * @phpstan-consistent-constructor
 */
class Connection
{
    /**
     * Represents an array of ints to be expanded by Doctrine SQL parsing.
     *
     * @deprecated Use {@see ArrayParameterType::INTEGER} instead.
     */
    public const PARAM_INT_ARRAY = ArrayParameterType::INTEGER;

    /**
     * Represents an array of strings to be expanded by Doctrine SQL parsing.
     *
     * @deprecated Use {@see ArrayParameterType::STRING} instead.
     */
    public const PARAM_STR_ARRAY = ArrayParameterType::STRING;

    /**
     * Represents an array of ascii strings to be expanded by Doctrine SQL parsing.
     *
     * @deprecated Use {@see ArrayParameterType::ASCII} instead.
     */
    public const PARAM_ASCII_STR_ARRAY = ArrayParameterType::ASCII;

    /**
     * Offset by which PARAM_* constants are detected as arrays of the param type.
     *
     * @internal Should be used only within the wrapper layer.
     */
    public const ARRAY_PARAM_OFFSET = 100;

    /**
     * The wrapped driver connection.
     *
     * @var DriverConnection|null
     */
    protected $_conn;

    /** @var Configuration */
    protected $_config;

    /**
     * @deprecated
     *
     * @var EventManager
     */
    protected $_eventManager;

    /**
     * @deprecated Use {@see createExpressionBuilder()} instead.
     *
     * @var ExpressionBuilder
     */
    protected $_expr;

    /**
     * The current auto-commit mode of this connection.
     */
    private bool $autoCommit = true;

    /**
     * The transaction nesting level.
     */
    private int $transactionNestingLevel = 0;

    /**
     * The currently active transaction isolation level or NULL before it has been determined.
     *
     * @var TransactionIsolationLevel::*|null
     */
    private $transactionIsolationLevel;

    /**
     * If nested transactions should use savepoints.
     */
    private bool $nestTransactionsWithSavepoints = false;

    /**
     * The parameters used during creation of the Connection instance.
     *
     * @var array<string,mixed>
     * @phpstan-var Params
     */
    private array $params;

    /**
     * The database platform object used by the connection or NULL before it's initialized.
     */
    private ?AbstractPlatform $platform = null;

    private ?ExceptionConverter $exceptionConverter = null;
    private ?Parser $parser                         = null;

    /**
     * The schema manager.
     *
     * @deprecated Use {@see createSchemaManager()} instead.
     *
     * @var AbstractSchemaManager|null
     */
    protected $_schemaManager;

    /**
     * The used DBAL driver.
     *
     * @var Driver
     */
    protected $_driver;

    /**
     * Flag that indicates whether the current transaction is marked for rollback only.
     */
    private bool $isRollbackOnly = false;

    private SchemaManagerFactory $schemaManagerFactory;

    /**
     * Initializes a new instance of the Connection class.
     *
     * @internal The connection can be only instantiated by the driver manager.
     *
     * @param array<string,mixed> $params       The connection parameters.
     * @param Driver              $driver       The driver to use.
     * @param Configuration|null  $config       The configuration, optional.
     * @param EventManager|null   $eventManager The event manager, optional.
     * @phpstan-param Params $params
     *
     * @throws Exception
     */
    public function __construct(
        #[SensitiveParameter]
        array $params,
        Driver $driver,
        ?Configuration $config = null,
        ?EventManager $eventManager = null
    ) {
        $this->_driver = $driver;
        $this->params  = $params;

        // Create default config and event manager if none given
        $config       ??= new Configuration();
        $eventManager ??= new EventManager();

        $this->_config       = $config;
        $this->_eventManager = $eventManager;

        if (isset($params['platform'])) {
            if (! $params['platform'] instanceof Platforms\AbstractPlatform) {
                throw Exception::invalidPlatformType($params['platform']);
            }

            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5699',
                'The "platform" connection parameter is deprecated.'
                    . ' Use a driver middleware that would instantiate the platform instead.',
            );

            $this->platform = $params['platform'];
            $this->platform->setEventManager($this->_eventManager);
            $this->platform->setDisableTypeComments($config->getDisableTypeComments());
        }

        $this->_expr = $this->createExpressionBuilder();

        $this->autoCommit = $config->getAutoCommit();

        $schemaManagerFactory = $config->getSchemaManagerFactory();
        if ($schemaManagerFactory === null) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/5812',
                'Not configuring a schema manager factory is deprecated.'
                    . ' Use %s which is going to be the default in DBAL 4.',
                DefaultSchemaManagerFactory::class,
            );

            $schemaManagerFactory = new LegacySchemaManagerFactory();
        }

        $this->schemaManagerFactory = $schemaManagerFactory;
    }

    /**
     * Gets the parameters used during instantiation.
     *
     * @internal
     *
     * @return array<string,mixed>
     * @phpstan-return Params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Gets the name of the currently selected database.
     *
     * @return string|null The name of the database or NULL if a database is not selected.
     *                     The platforms which don't support the concept of a database (e.g. embedded databases)
     *                     must always return a string as an indicator of an implicitly selected database.
     *
     * @throws Exception
     */
    public function getDatabase()
    {
        $platform = $this->getDatabasePlatform();
        $query    = $platform->getDummySelectSQL($platform->getCurrentDatabaseExpression());
        $database = $this->fetchOne($query);

        assert(is_string($database) || $database === null);

        return $database;
    }

    /**
     * Gets the DBAL driver instance.
     *
     * @return Driver
     */
    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * Gets the Configuration used by the Connection.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->_config;
    }

    /**
     * Gets the EventManager used by the Connection.
     *
     * @deprecated
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/5784',
            '%s is deprecated.',
            __METHOD__,
        );

        return $this->_eventManager;
    }

    /**
     * Gets the DatabasePlatform for the connection.
     *
     * @return AbstractPlatform
     *
     * @throws Exception
     */
    public function getDatabasePlatform()
    {
        if ($this->platform === null) {
            $this->platform = $this->detectDatabasePlatform();
            $this->platform->setEventManager($this->_eventManager);
            $this->platform->setDisableTypeComments($this->_config->getDisableTypeComments());
        }

        return $this->platform;
    }

    /**
     * Creates an expression builder for the connection.
     */
    public function createExpressionBuilder(): ExpressionBuilder
    {
        return new ExpressionBuilder($this);
    }

    /**
     * Gets the ExpressionBuilder for the connection.
     *
     * @deprecated Use {@see createExpressionBuilder()} instead.
     *
     * @return ExpressionBuilder
     */
    public function getExpressionBuilder()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4515',
            'Connection::getExpressionBuilder() is deprecated,'
                . ' use Connection::createExpressionBuilder() instead.',
        );

        return $this->_expr;
    }

    /**
     * Establishes the connection with the database.
     *
     * @internal This method will be made protected in DBAL 4.0.
     *
     * @return bool TRUE if the connection was successfully established, FALSE if
     *              the connection is already open.
     *
     * @throws Exception
     *
     * @phpstan-assert !null $this->_conn
     */
    public function connect()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4966',
            'Public access to Connection::connect() is deprecated.',
        );

        if ($this->_conn !== null) {
            return false;
        }

        try {
            $this->_conn = $this->_driver->connect($this->params);
        } catch (Driver\Exception $e) {
            throw $this->convertException($e);
        }

        if ($this->autoCommit === false) {
            $this->beginTransaction();
        }

        if ($this->_eventManager->hasListeners(Events::postConnect)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/5784',
                'Subscribing to %s events is deprecated. Implement a middleware instead.',
                Events::postConnect,
            );

            $eventArgs = new Event\ConnectionEventArgs($this);
            $this->_eventManager->dispatchEvent(Events::postConnect, $eventArgs);
        }

        return true;
    }

    /**
     * Detects and sets the database platform.
     *
     * Evaluates custom platform class and version in order to set the correct platform.
     *
     * @throws Exception If an invalid platform was specified for this connection.
     */
    private function detectDatabasePlatform(): AbstractPlatform
    {
        $version = $this->getDatabasePlatformVersion();

        if ($version !== null) {
            assert($this->_driver instanceof VersionAwarePlatformDriver);

            return $this->_driver->createDatabasePlatformForVersion($version);
        }

        return $this->_driver->getDatabasePlatform();
    }

    /**
     * Returns the version of the related platform if applicable.
     *
     * Returns null if either the driver is not capable to create version
     * specific platform instances, no explicit server version was specified
     * or the underlying driver connection cannot determine the platform
     * version without having to query it (performance reasons).
     *
     * @return string|null
     *
     * @throws Throwable
     */
    private function getDatabasePlatformVersion()
    {
        // Driver does not support version specific platforms.
        if (! $this->_driver instanceof VersionAwarePlatformDriver) {
            return null;
        }

        // Explicit platform version requested (supersedes auto-detection).
        if (isset($this->params['serverVersion'])) {
            return $this->params['serverVersion'];
        }

        if (isset($this->params['primary']) && isset($this->params['primary']['serverVersion'])) {
            return $this->params['primary']['serverVersion'];
        }

        // If not connected, we need to connect now to determine the platform version.
        if ($this->_conn === null) {
            try {
                $this->connect();
            } catch (Exception $originalException) {
                if (! isset($this->params['dbname'])) {
                    throw $originalException;
                }

                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5707',
                    'Relying on a fallback connection used to determine the database platform while connecting'
                        . ' to a non-existing database is deprecated. Either use an existing database name in'
                        . ' connection parameters or omit the database name if the platform'
                        . ' and the server configuration allow that.',
                );

                // The database to connect to might not yet exist.
                // Retry detection without database name connection parameter.
                $params = $this->params;

                unset($this->params['dbname']);

                try {
                    $this->connect();
                } catch (Exception $fallbackException) {
                    // Either the platform does not support database-less connections
                    // or something else went wrong.
                    throw $originalException;
                } finally {
                    $this->params = $params;
                }

                $serverVersion = $this->getServerVersion();

                // Close "temporary" connection to allow connecting to the real database again.
                $this->close();

                return $serverVersion;
            }
        }

        return $this->getServerVersion();
    }

    /**
     * Returns the database server version if the underlying driver supports it.
     *
     * @return string|null
     *
     * @throws Exception
     */
    private function getServerVersion()
    {
        $connection = $this->getWrappedConnection();

        // Automatic platform version detection.
        if ($connection instanceof ServerInfoAwareConnection) {
            try {
                return $connection->getServerVersion();
            } catch (Driver\Exception $e) {
                throw $this->convertException($e);
            }
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4750',
            'Not implementing the ServerInfoAwareConnection interface in %s is deprecated',
            get_class($connection),
        );

        // Unable to detect platform version.
        return null;
    }

    /**
     * Returns the current auto-commit mode for this connection.
     *
     * @see    setAutoCommit
     *
     * @return bool True if auto-commit mode is currently enabled for this connection, false otherwise.
     */
    public function isAutoCommit()
    {
        return $this->autoCommit === true;
    }

    /**
     * Sets auto-commit mode for this connection.
     *
     * If a connection is in auto-commit mode, then all its SQL statements will be executed and committed as individual
     * transactions. Otherwise, its SQL statements are grouped into transactions that are terminated by a call to either
     * the method commit or the method rollback. By default, new connections are in auto-commit mode.
     *
     * NOTE: If this method is called during a transaction and the auto-commit mode is changed, the transaction is
     * committed. If this method is called and the auto-commit mode is not changed, the call is a no-op.
     *
     * @see   isAutoCommit
     *
     * @param bool $autoCommit True to enable auto-commit mode; false to disable it.
     *
     * @return void
     */
    public function setAutoCommit($autoCommit)
    {
        $autoCommit = (bool) $autoCommit;

        // Mode not changed, no-op.
        if ($autoCommit === $this->autoCommit) {
            return;
        }

        $this->autoCommit = $autoCommit;

        // Commit all currently active transactions if any when switching auto-commit mode.
        if ($this->_conn === null || $this->transactionNestingLevel === 0) {
            return;
        }

        $this->commitAll();
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return array<string, mixed>|false False is returned if no rows are found.
     *
     * @throws Exception
     */
    public function fetchAssociative(string $query, array $params = [], array $types = [])
    {
        return $this->executeQuery($query, $params, $types)->fetchAssociative();
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<mixed>|false False is returned if no rows are found.
     *
     * @throws Exception
     */
    public function fetchNumeric(string $query, array $params = [], array $types = [])
    {
        return $this->executeQuery($query, $params, $types)->fetchNumeric();
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return mixed|false False is returned if no rows are found.
     *
     * @throws Exception
     */
    public function fetchOne(string $query, array $params = [], array $types = [])
    {
        return $this->executeQuery($query, $params, $types)->fetchOne();
    }

    /**
     * Whether an actual connection to the database is established.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->_conn !== null;
    }

    /**
     * Checks whether a transaction is currently active.
     *
     * @return bool TRUE if a transaction is currently active, FALSE otherwise.
     */
    public function isTransactionActive()
    {
        return $this->transactionNestingLevel > 0;
    }

    /**
     * Adds condition based on the criteria to the query components
     *
     * @param array<string,mixed> $criteria   Map of key columns to their values
     * @param string[]            $columns    Column names
     * @param mixed[]             $values     Column values
     * @param string[]            $conditions Key conditions
     *
     * @throws Exception
     */
    private function addCriteriaCondition(
        array $criteria,
        array &$columns,
        array &$values,
        array &$conditions
    ): void {
        $platform = $this->getDatabasePlatform();

        foreach ($criteria as $columnName => $value) {
            if ($value === null) {
                $conditions[] = $platform->getIsNullExpression($columnName);
                continue;
            }

            $columns[]    = $columnName;
            $values[]     = $value;
            $conditions[] = $columnName . ' = ?';
        }
    }

    /**
     * Executes an SQL DELETE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string                                                               $table    Table name
     * @param array<string, mixed>                                                 $criteria Deletion criteria
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types    Parameter types
     *
     * @return int|string The number of affected rows.
     *
     * @throws Exception
     */
    public function delete($table, array $criteria, array $types = [])
    {
        if (count($criteria) === 0) {
            throw InvalidArgumentException::fromEmptyCriteria();
        }

        $columns = $values = $conditions = [];

        $this->addCriteriaCondition($criteria, $columns, $values, $conditions);

        return $this->executeStatement(
            'DELETE FROM ' . $table . ' WHERE ' . implode(' AND ', $conditions),
            $values,
            is_string(key($types)) ? $this->extractTypeValues($columns, $types) : $types,
        );
    }

    /**
     * Closes the connection.
     *
     * @return void
     */
    public function close()
    {
        $this->_conn                   = null;
        $this->transactionNestingLevel = 0;
    }

    /**
     * Sets the transaction isolation level.
     *
     * @param TransactionIsolationLevel::* $level The level to set.
     *
     * @return int|string
     *
     * @throws Exception
     */
    public function setTransactionIsolation($level)
    {
        $this->transactionIsolationLevel = $level;

        return $this->executeStatement($this->getDatabasePlatform()->getSetTransactionIsolationSQL($level));
    }

    /**
     * Gets the currently active transaction isolation level.
     *
     * @return TransactionIsolationLevel::* The current transaction isolation level.
     *
     * @throws Exception
     */
    public function getTransactionIsolation()
    {
        return $this->transactionIsolationLevel ??= $this->getDatabasePlatform()->getDefaultTransactionIsolationLevel();
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string                                                               $table    Table name
     * @param array<string, mixed>                                                 $data     Column-value pairs
     * @param array<string, mixed>                                                 $criteria Update criteria
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types    Parameter types
     *
     * @return int|string The number of affected rows.
     *
     * @throws Exception
     */
    public function update($table, array $data, array $criteria, array $types = [])
    {
        $columns = $values = $conditions = $set = [];

        foreach ($data as $columnName => $value) {
            $columns[] = $columnName;
            $values[]  = $value;
            $set[]     = $columnName . ' = ?';
        }

        $this->addCriteriaCondition($criteria, $columns, $values, $conditions);

        if (is_string(key($types))) {
            $types = $this->extractTypeValues($columns, $types);
        }

        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $set)
                . ' WHERE ' . implode(' AND ', $conditions);

        return $this->executeStatement($sql, $values, $types);
    }

    /**
     * Inserts a table row with specified data.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string                                                               $table Table name
     * @param array<string, mixed>                                                 $data  Column-value pairs
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     *
     * @return int|string The number of affected rows.
     *
     * @throws Exception
     */
    public function insert($table, array $data, array $types = [])
    {
        if (count($data) === 0) {
            return $this->executeStatement('INSERT INTO ' . $table . ' () VALUES ()');
        }

        $columns = [];
        $values  = [];
        $set     = [];

        foreach ($data as $columnName => $value) {
            $columns[] = $columnName;
            $values[]  = $value;
            $set[]     = '?';
        }

        return $this->executeStatement(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ')' .
            ' VALUES (' . implode(', ', $set) . ')',
            $values,
            is_string(key($types)) ? $this->extractTypeValues($columns, $types) : $types,
        );
    }

    /**
     * Extract ordered type list from an ordered column list and type map.
     *
     * @param array<int, string>                                                   $columnList
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types
     *
     * @return array<int, int|string|Type|null>|array<string, int|string|Type|null>
     */
    private function extractTypeValues(array $columnList, array $types): array
    {
        $typeValues = [];

        foreach ($columnList as $columnName) {
            $typeValues[] = $types[$columnName] ?? ParameterType::STRING;
        }

        return $typeValues;
    }

    /**
     * Quotes a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * NOTE: Just because you CAN use quoted identifiers does not mean
     * you SHOULD use them. In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str The name to be quoted.
     *
     * @return string The quoted name.
     */
    public function quoteIdentifier($str)
    {
        return $this->getDatabasePlatform()->quoteIdentifier($str);
    }

    /**
     * The usage of this method is discouraged. Use prepared statements
     * or {@see AbstractPlatform::quoteStringLiteral()} instead.
     *
     * @param mixed                $value
     * @param int|string|Type|null $type
     *
     * @return mixed
     */
    public function quote($value, $type = ParameterType::STRING)
    {
        $connection = $this->getWrappedConnection();

        [$value, $bindingType] = $this->getBindingInfo($value, $type);

        return $connection->quote($value, $bindingType);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of numeric arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<list<mixed>>
     *
     * @throws Exception
     */
    public function fetchAllNumeric(string $query, array $params = [], array $types = []): array
    {
        return $this->executeQuery($query, $params, $types)->fetchAllNumeric();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function fetchAllAssociative(string $query, array $params = [], array $types = []): array
    {
        return $this->executeQuery($query, $params, $types)->fetchAllAssociative();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return array<mixed,mixed>
     *
     * @throws Exception
     */
    public function fetchAllKeyValue(string $query, array $params = [], array $types = []): array
    {
        return $this->executeQuery($query, $params, $types)->fetchAllKeyValue();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return array<mixed,array<string,mixed>>
     *
     * @throws Exception
     */
    public function fetchAllAssociativeIndexed(string $query, array $params = [], array $types = []): array
    {
        return $this->executeQuery($query, $params, $types)->fetchAllAssociativeIndexed();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of the first column values.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<mixed>
     *
     * @throws Exception
     */
    public function fetchFirstColumn(string $query, array $params = [], array $types = []): array
    {
        return $this->executeQuery($query, $params, $types)->fetchFirstColumn();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented as numeric arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return Traversable<int,list<mixed>>
     *
     * @throws Exception
     */
    public function iterateNumeric(string $query, array $params = [], array $types = []): Traversable
    {
        return $this->executeQuery($query, $params, $types)->iterateNumeric();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented
     * as associative arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return Traversable<int,array<string,mixed>>
     *
     * @throws Exception
     */
    public function iterateAssociative(string $query, array $params = [], array $types = []): Traversable
    {
        return $this->executeQuery($query, $params, $types)->iterateAssociative();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return Traversable<mixed,mixed>
     *
     * @throws Exception
     */
    public function iterateKeyValue(string $query, array $params = [], array $types = []): Traversable
    {
        return $this->executeQuery($query, $params, $types)->iterateKeyValue();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @param string                                           $query  SQL query
     * @param list<mixed>|array<string, mixed>                 $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
     * @return Traversable<mixed,array<string,mixed>>
     *
     * @throws Exception
     */
    public function iterateAssociativeIndexed(string $query, array $params = [], array $types = []): Traversable
    {
        return $this->executeQuery($query, $params, $types)->iterateAssociativeIndexed();
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over the first column values.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return Traversable<int,mixed>
     *
     * @throws Exception
     */
    public function iterateColumn(string $query, array $params = [], array $types = []): Traversable
    {
        return $this->executeQuery($query, $params, $types)->iterateColumn();
    }

    /**
     * Prepares an SQL statement.
     *
     * @param string $sql The SQL statement to prepare.
     *
     * @throws Exception
     */
    public function prepare(string $sql): Statement
    {
        $connection = $this->getWrappedConnection();

        try {
            $statement = $connection->prepare($sql);
        } catch (Driver\Exception $e) {
            throw $this->convertExceptionDuringQuery($e, $sql);
        }

        return new Statement($this, $statement, $sql);
    }

    /**
     * Executes an, optionally parameterized, SQL query.
     *
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string                                                               $sql    SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @throws Exception
     */
    public function executeQuery(
        string $sql,
        array $params = [],
        $types = [],
        ?QueryCacheProfile $qcp = null
    ): Result {
        if ($qcp !== null) {
            return $this->executeCacheQuery($sql, $params, $types, $qcp);
        }

        $connection = $this->getWrappedConnection();

        $logger = $this->_config->getSQLLogger();
        if ($logger !== null) {
            $logger->startQuery($sql, $params, $types);
        }

        try {
            if (count($params) > 0) {
                if ($this->needsArrayParameterConversion($params, $types)) {
                    [$sql, $params, $types] = $this->expandArrayParameters($sql, $params, $types);
                }

                $stmt = $connection->prepare($sql);

                $this->bindParameters($stmt, $params, $types);

                $result = $stmt->execute();
            } else {
                $result = $connection->query($sql);
            }

            return new Result($result, $this);
        } catch (Driver\Exception $e) {
            throw $this->convertExceptionDuringQuery($e, $sql, $params, $types);
        } finally {
            if ($logger !== null) {
                $logger->stopQuery();
            }
        }
    }

    /**
     * Executes a caching query.
     *
     * @param string                                                               $sql    SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @throws CacheException
     * @throws Exception
     */
    public function executeCacheQuery($sql, $params, $types, QueryCacheProfile $qcp): Result
    {
        $resultCache = $qcp->getResultCache() ?? $this->_config->getResultCache();

        if ($resultCache === null) {
            throw CacheException::noResultDriverConfigured();
        }

        $connectionParams = $this->params;
        unset($connectionParams['platform'], $connectionParams['password'], $connectionParams['url']);

        [$cacheKey, $realKey] = $qcp->generateCacheKeys($sql, $params, $types, $connectionParams);

        $item = $resultCache->getItem($cacheKey);

        if ($item->isHit()) {
            $value = $item->get();
            if (! is_array($value)) {
                $value = [];
            }

            if (isset($value[$realKey])) {
                return new Result(new ArrayResult($value[$realKey]), $this);
            }
        } else {
            $value = [];
        }

        $data = $this->fetchAllAssociative($sql, $params, $types);

        $value[$realKey] = $data;

        $item->set($value);

        $lifetime = $qcp->getLifetime();
        if ($lifetime > 0) {
            $item->expiresAfter($lifetime);
        }

        $resultCache->save($item);

        return new Result(new ArrayResult($data), $this);
    }

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     *
     * Could be used for:
     *  - DML statements: INSERT, UPDATE, DELETE, etc.
     *  - DDL statements: CREATE, DROP, ALTER, etc.
     *  - DCL statements: GRANT, REVOKE, etc.
     *  - Session control statements: ALTER SESSION, SET, DECLARE, etc.
     *  - Other statements that don't yield a row set.
     *
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string                                                               $sql    SQL statement
     * @param list<mixed>|array<string, mixed>                                     $params Statement parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return int|string The number of affected rows.
     *
     * @throws Exception
     */
    public function executeStatement($sql, array $params = [], array $types = [])
    {
        $connection = $this->getWrappedConnection();

        $logger = $this->_config->getSQLLogger();
        if ($logger !== null) {
            $logger->startQuery($sql, $params, $types);
        }

        try {
            if (count($params) > 0) {
                if ($this->needsArrayParameterConversion($params, $types)) {
                    [$sql, $params, $types] = $this->expandArrayParameters($sql, $params, $types);
                }

                $stmt = $connection->prepare($sql);

                $this->bindParameters($stmt, $params, $types);

                return $stmt->execute()
                    ->rowCount();
            }

            return $connection->exec($sql);
        } catch (Driver\Exception $e) {
            throw $this->convertExceptionDuringQuery($e, $sql, $params, $types);
        } finally {
            if ($logger !== null) {
                $logger->stopQuery();
            }
        }
    }

    /**
     * Returns the current transaction nesting level.
     *
     * @return int The nesting level. A value of 0 means there's no active transaction.
     */
    public function getTransactionNestingLevel()
    {
        return $this->transactionNestingLevel;
    }

    /**
     * Returns the ID of the last inserted row, or the last value from a sequence object,
     * depending on the underlying driver.
     *
     * Note: This method may not return a meaningful or consistent result across different drivers,
     * because the underlying database may not even support the notion of AUTO_INCREMENT/IDENTITY
     * columns or sequences.
     *
     * @param string|null $name Name of the sequence object from which the ID should be returned.
     *
     * @return string|int|false A string representation of the last inserted ID.
     *
     * @throws Exception
     */
    public function lastInsertId($name = null)
    {
        if ($name !== null) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4687',
                'The usage of Connection::lastInsertId() with a sequence name is deprecated.',
            );
        }

        try {
            return $this->getWrappedConnection()->lastInsertId($name);
        } catch (Driver\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this Connection instance as an (optional) parameter.
     *
     * If an exception occurs during execution of the function or transaction commit,
     * the transaction is rolled back and the exception re-thrown.
     *
     * @param Closure(self):T $func The function to execute transactionally.
     *
     * @return T The value returned by $func
     *
     * @throws Throwable
     *
     * @template T
     */
    public function transactional(Closure $func)
    {
        $this->beginTransaction();

        $successful = false;

        try {
            $res = $func($this);

            $successful = true;
        } finally {
            if (! $successful) {
                $this->rollBack();
            }
        }

        $shouldRollback = true;
        try {
            $this->commit();

            $shouldRollback = false;
        } catch (TheDriverException $t) {
            $convertedException = $this->handleDriverException($t, null);
            $shouldRollback     = ! (
                $convertedException instanceof TransactionRolledBack
                || $convertedException instanceof UniqueConstraintViolationException
                || $convertedException instanceof ForeignKeyConstraintViolationException
                || $convertedException instanceof DeadlockException
            );

            throw $t;
        } finally {
            if ($shouldRollback) {
                $this->rollBack();
            }
        }

        return $res;
    }

    /**
     * Sets if nested transactions should use savepoints.
     *
     * @param bool $nestTransactionsWithSavepoints
     *
     * @return void
     *
     * @throws Exception
     */
    public function setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints)
    {
        if (! $nestTransactionsWithSavepoints) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5383',
                <<<'DEPRECATION'
                Nesting transactions without enabling savepoints is deprecated.
                Call %s::setNestTransactionsWithSavepoints(true) to enable savepoints.
                DEPRECATION,
                self::class,
            );
        }

        if ($this->transactionNestingLevel > 0) {
            throw ConnectionException::mayNotAlterNestedTransactionWithSavepointsInTransaction();
        }

        $this->nestTransactionsWithSavepoints = (bool) $nestTransactionsWithSavepoints;
    }

    /**
     * Gets if nested transactions should use savepoints.
     *
     * @return bool
     */
    public function getNestTransactionsWithSavepoints()
    {
        return $this->nestTransactionsWithSavepoints;
    }

    /**
     * Returns the savepoint name to use for nested transactions.
     *
     * @return string
     */
    protected function _getNestedTransactionSavePointName()
    {
        return 'DOCTRINE_' . $this->transactionNestingLevel;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function beginTransaction()
    {
        $connection = $this->getWrappedConnection();

        ++$this->transactionNestingLevel;

        $logger = $this->_config->getSQLLogger();

        if ($this->transactionNestingLevel === 1) {
            if ($logger !== null) {
                $logger->startQuery('"START TRANSACTION"');
            }

            $connection->beginTransaction();

            if ($logger !== null) {
                $logger->stopQuery();
            }
        } elseif ($this->nestTransactionsWithSavepoints) {
            if ($logger !== null) {
                $logger->startQuery('"SAVEPOINT"');
            }

            $this->createSavepoint($this->_getNestedTransactionSavePointName());
            if ($logger !== null) {
                $logger->stopQuery();
            }
        } else {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5383',
                <<<'DEPRECATION'
                Nesting transactions without enabling savepoints is deprecated.
                Call %s::setNestTransactionsWithSavepoints(true) to enable savepoints.
                DEPRECATION,
                self::class,
            );
        }

        $eventManager = $this->getEventManager();

        if ($eventManager->hasListeners(Events::onTransactionBegin)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/5784',
                'Subscribing to %s events is deprecated.',
                Events::onTransactionBegin,
            );

            $eventManager->dispatchEvent(Events::onTransactionBegin, new TransactionBeginEventArgs($this));
        }

        return true;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function commit()
    {
        if ($this->transactionNestingLevel === 0) {
            throw ConnectionException::noActiveTransaction();
        }

        if ($this->isRollbackOnly) {
            throw ConnectionException::commitFailedRollbackOnly();
        }

        $result = true;

        $connection = $this->getWrappedConnection();

        try {
            if ($this->transactionNestingLevel === 1) {
                $result = $this->doCommit($connection);
            } elseif ($this->nestTransactionsWithSavepoints) {
                $this->releaseSavepoint($this->_getNestedTransactionSavePointName());
            }
        } finally {
            $this->updateTransactionStateAfterCommit();
        }

        return $result;
    }

    private function updateTransactionStateAfterCommit(): void
    {
        --$this->transactionNestingLevel;

        $eventManager = $this->getEventManager();

        if ($eventManager->hasListeners(Events::onTransactionCommit)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/5784',
                'Subscribing to %s events is deprecated.',
                Events::onTransactionCommit,
            );

            $eventManager->dispatchEvent(Events::onTransactionCommit, new TransactionCommitEventArgs($this));
        }

        if ($this->autoCommit !== false || $this->transactionNestingLevel !== 0) {
            return;
        }

        $this->beginTransaction();
    }

    /**
     * @return bool
     *
     * @throws DriverException
     */
    private function doCommit(DriverConnection $connection)
    {
        $logger = $this->_config->getSQLLogger();

        if ($logger !== null) {
            $logger->startQuery('"COMMIT"');
        }

        $result = $connection->commit();

        if ($logger !== null) {
            $logger->stopQuery();
        }

        return $result;
    }

    /**
     * Commits all current nesting transactions.
     *
     * @throws Exception
     */
    private function commitAll(): void
    {
        while ($this->transactionNestingLevel !== 0) {
            if ($this->autoCommit === false && $this->transactionNestingLevel === 1) {
                // When in no auto-commit mode, the last nesting commit immediately starts a new transaction.
                // Therefore we need to do the final commit here and then leave to avoid an infinite loop.
                $this->commit();

                return;
            }

            $this->commit();
        }
    }

    /**
     * Cancels any database changes done during the current transaction.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function rollBack()
    {
        if ($this->transactionNestingLevel === 0) {
            throw ConnectionException::noActiveTransaction();
        }

        $connection = $this->getWrappedConnection();

        $logger = $this->_config->getSQLLogger();

        if ($this->transactionNestingLevel === 1) {
            if ($logger !== null) {
                $logger->startQuery('"ROLLBACK"');
            }

            $this->transactionNestingLevel = 0;
            $connection->rollBack();
            $this->isRollbackOnly = false;
            if ($logger !== null) {
                $logger->stopQuery();
            }

            if ($this->autoCommit === false) {
                $this->beginTransaction();
            }
        } elseif ($this->nestTransactionsWithSavepoints) {
            if ($logger !== null) {
                $logger->startQuery('"ROLLBACK TO SAVEPOINT"');
            }

            $this->rollbackSavepoint($this->_getNestedTransactionSavePointName());
            --$this->transactionNestingLevel;
            if ($logger !== null) {
                $logger->stopQuery();
            }
        } else {
            $this->isRollbackOnly = true;
            --$this->transactionNestingLevel;
        }

        $eventManager = $this->getEventManager();

        if ($eventManager->hasListeners(Events::onTransactionRollBack)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/5784',
                'Subscribing to %s events is deprecated.',
                Events::onTransactionRollBack,
            );

            $eventManager->dispatchEvent(Events::onTransactionRollBack, new TransactionRollBackEventArgs($this));
        }

        return true;
    }

    /**
     * Creates a new savepoint.
     *
     * @param string $savepoint The name of the savepoint to create.
     *
     * @return void
     *
     * @throws Exception
     */
    public function createSavepoint($savepoint)
    {
        $platform = $this->getDatabasePlatform();

        if (! $platform->supportsSavepoints()) {
            throw ConnectionException::savepointsNotSupported();
        }

        $this->executeStatement($platform->createSavePoint($savepoint));
    }

    /**
     * Releases the given savepoint.
     *
     * @param string $savepoint The name of the savepoint to release.
     *
     * @return void
     *
     * @throws Exception
     */
    public function releaseSavepoint($savepoint)
    {
        $logger = $this->_config->getSQLLogger();

        $platform = $this->getDatabasePlatform();

        if (! $platform->supportsSavepoints()) {
            throw ConnectionException::savepointsNotSupported();
        }

        if (! $platform->supportsReleaseSavepoints()) {
            if ($logger !== null) {
                $logger->stopQuery();
            }

            return;
        }

        if ($logger !== null) {
            $logger->startQuery('"RELEASE SAVEPOINT"');
        }

        $this->executeStatement($platform->releaseSavePoint($savepoint));

        if ($logger === null) {
            return;
        }

        $logger->stopQuery();
    }

    /**
     * Rolls back to the given savepoint.
     *
     * @param string $savepoint The name of the savepoint to rollback to.
     *
     * @return void
     *
     * @throws Exception
     */
    public function rollbackSavepoint($savepoint)
    {
        $platform = $this->getDatabasePlatform();

        if (! $platform->supportsSavepoints()) {
            throw ConnectionException::savepointsNotSupported();
        }

        $this->executeStatement($platform->rollbackSavePoint($savepoint));
    }

    /**
     * Gets the wrapped driver connection.
     *
     * @deprecated Use {@link getNativeConnection()} to access the native connection.
     *
     * @return DriverConnection
     *
     * @throws Exception
     */
    public function getWrappedConnection()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4966',
            'Connection::getWrappedConnection() is deprecated.'
                . ' Use Connection::getNativeConnection() to access the native connection.',
        );

        $this->connect();

        return $this->_conn;
    }

    /** @return resource|object */
    public function getNativeConnection()
    {
        $this->connect();

        if (! method_exists($this->_conn, 'getNativeConnection')) {
            throw new LogicException(sprintf(
                'The driver connection %s does not support accessing the native connection.',
                get_class($this->_conn),
            ));
        }

        return $this->_conn->getNativeConnection();
    }

    /**
     * Creates a SchemaManager that can be used to inspect or change the
     * database schema through the connection.
     *
     * @throws Exception
     */
    public function createSchemaManager(): AbstractSchemaManager
    {
        return $this->schemaManagerFactory->createSchemaManager($this);
    }

    /**
     * Gets the SchemaManager that can be used to inspect or change the
     * database schema through the connection.
     *
     * @deprecated Use {@see createSchemaManager()} instead.
     *
     * @return AbstractSchemaManager
     *
     * @throws Exception
     */
    public function getSchemaManager()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4515',
            'Connection::getSchemaManager() is deprecated, use Connection::createSchemaManager() instead.',
        );

        return $this->_schemaManager ??= $this->createSchemaManager();
    }

    /**
     * Marks the current transaction so that the only possible
     * outcome for the transaction to be rolled back.
     *
     * @return void
     *
     * @throws ConnectionException If no transaction is active.
     */
    public function setRollbackOnly()
    {
        if ($this->transactionNestingLevel === 0) {
            throw ConnectionException::noActiveTransaction();
        }

        $this->isRollbackOnly = true;
    }

    /**
     * Checks whether the current transaction is marked for rollback only.
     *
     * @return bool
     *
     * @throws ConnectionException If no transaction is active.
     */
    public function isRollbackOnly()
    {
        if ($this->transactionNestingLevel === 0) {
            throw ConnectionException::noActiveTransaction();
        }

        return $this->isRollbackOnly;
    }

    /**
     * Converts a given value to its database representation according to the conversion
     * rules of a specific DBAL mapping type.
     *
     * @param mixed  $value The value to convert.
     * @param string $type  The name of the DBAL mapping type.
     *
     * @return mixed The converted value.
     *
     * @throws Exception
     */
    public function convertToDatabaseValue($value, $type)
    {
        return Type::getType($type)->convertToDatabaseValue($value, $this->getDatabasePlatform());
    }

    /**
     * Converts a given value to its PHP representation according to the conversion
     * rules of a specific DBAL mapping type.
     *
     * @param mixed  $value The value to convert.
     * @param string $type  The name of the DBAL mapping type.
     *
     * @return mixed The converted type.
     *
     * @throws Exception
     */
    public function convertToPHPValue($value, $type)
    {
        return Type::getType($type)->convertToPHPValue($value, $this->getDatabasePlatform());
    }

    /**
     * Binds a set of parameters, some or all of which are typed with a PDO binding type
     * or DBAL mapping type, to a given statement.
     *
     * @param DriverStatement                                                      $stmt   Prepared statement
     * @param list<mixed>|array<string, mixed>                                     $params Statement parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @throws Exception
     */
    private function bindParameters(DriverStatement $stmt, array $params, array $types): void
    {
        // Check whether parameters are positional or named. Mixing is not allowed.
        if (is_int(key($params))) {
            $bindIndex = 1;

            foreach ($params as $key => $value) {
                if (isset($types[$key])) {
                    $type                  = $types[$key];
                    [$value, $bindingType] = $this->getBindingInfo($value, $type);
                } else {
                    if (array_key_exists($key, $types)) {
                        Deprecation::trigger(
                            'doctrine/dbal',
                            'https://github.com/doctrine/dbal/pull/5550',
                            'Using NULL as prepared statement parameter type is deprecated.'
                                . 'Omit or use ParameterType::STRING instead',
                        );
                    }

                    $bindingType = ParameterType::STRING;
                }

                $stmt->bindValue($bindIndex, $value, $bindingType);

                ++$bindIndex;
            }
        } else {
            // Named parameters
            foreach ($params as $name => $value) {
                if (isset($types[$name])) {
                    $type                  = $types[$name];
                    [$value, $bindingType] = $this->getBindingInfo($value, $type);
                } else {
                    if (array_key_exists($name, $types)) {
                        Deprecation::trigger(
                            'doctrine/dbal',
                            'https://github.com/doctrine/dbal/pull/5550',
                            'Using NULL as prepared statement parameter type is deprecated.'
                                . 'Omit or use ParameterType::STRING instead',
                        );
                    }

                    $bindingType = ParameterType::STRING;
                }

                $stmt->bindValue($name, $value, $bindingType);
            }
        }
    }

    /**
     * Gets the binding type of a given type.
     *
     * @param mixed                $value The value to bind.
     * @param int|string|Type|null $type  The type to bind (PDO or DBAL).
     *
     * @return array{mixed, int} [0] => the (escaped) value, [1] => the binding type.
     *
     * @throws Exception
     */
    private function getBindingInfo($value, $type): array
    {
        if (is_string($type)) {
            $type = Type::getType($type);
        }

        if ($type instanceof Type) {
            $value       = $type->convertToDatabaseValue($value, $this->getDatabasePlatform());
            $bindingType = $type->getBindingType();
        } else {
            $bindingType = $type ?? ParameterType::STRING;
        }

        return [$value, $bindingType];
    }

    /**
     * Creates a new instance of a SQL query builder.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return new Query\QueryBuilder($this);
    }

    /**
     * @internal
     *
     * @param list<mixed>|array<string, mixed>                                     $params
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types
     */
    final public function convertExceptionDuringQuery(
        Driver\Exception $e,
        string $sql,
        array $params = [],
        array $types = []
    ): DriverException {
        return $this->handleDriverException($e, new Query($sql, $params, $types));
    }

    /** @internal */
    final public function convertException(Driver\Exception $e): DriverException
    {
        return $this->handleDriverException($e, null);
    }

    /**
     * @param array<int, mixed>|array<string, mixed>                               $params
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types
     *
     * @return array{string, list<mixed>, array<int,Type|int|string|null>}
     */
    private function expandArrayParameters(string $sql, array $params, array $types): array
    {
        $this->parser ??= $this->getDatabasePlatform()->createSQLParser();
        $visitor        = new ExpandArrayParameters($params, $types);

        $this->parser->parse($sql, $visitor);

        return [
            $visitor->getSQL(),
            $visitor->getParameters(),
            $visitor->getTypes(),
        ];
    }

    /**
     * @param array<int, mixed>|array<string, mixed>                               $params
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types
     */
    private function needsArrayParameterConversion(array $params, array $types): bool
    {
        if (is_string(key($params))) {
            return true;
        }

        foreach ($types as $type) {
            if (
                $type === ArrayParameterType::INTEGER
                || $type === ArrayParameterType::STRING
                || $type === ArrayParameterType::ASCII
                || $type === ArrayParameterType::BINARY
            ) {
                return true;
            }
        }

        return false;
    }

    private function handleDriverException(
        Driver\Exception $driverException,
        ?Query $query
    ): DriverException {
        $this->exceptionConverter ??= $this->_driver->getExceptionConverter();
        $exception                  = $this->exceptionConverter->convert($driverException, $query);

        if ($exception instanceof ConnectionLost) {
            $this->close();
        }

        return $exception;
    }

    /**
     * BC layer for a wide-spread use-case of old DBAL APIs
     *
     * @deprecated Use {@see executeStatement()} instead
     *
     * @param array<mixed>           $params The query parameters
     * @param array<int|string|null> $types  The parameter types
     */
    public function executeUpdate(string $sql, array $params = [], array $types = []): int
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4163',
            '%s is deprecated, please use executeStatement() instead.',
            __METHOD__,
        );

        return $this->executeStatement($sql, $params, $types);
    }

    /**
     * BC layer for a wide-spread use-case of old DBAL APIs
     *
     * @deprecated Use {@see executeQuery()} instead
     */
    public function query(string $sql): Result
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4163',
            '%s is deprecated, please use executeQuery() instead.',
            __METHOD__,
        );

        return $this->executeQuery($sql);
    }

    /**
     * BC layer for a wide-spread use-case of old DBAL APIs
     *
     * @deprecated please use {@see executeStatement()} instead
     */
    public function exec(string $sql): int
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4163',
            '%s is deprecated, please use executeStatement() instead.',
            __METHOD__,
        );

        return $this->executeStatement($sql);
    }
}
