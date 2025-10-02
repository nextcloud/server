<?php

namespace Doctrine\DBAL;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Driver\IBMDB2;
use Doctrine\DBAL\Driver\Mysqli;
use Doctrine\DBAL\Driver\OCI8;
use Doctrine\DBAL\Driver\PDO;
use Doctrine\DBAL\Driver\PgSQL;
use Doctrine\DBAL\Driver\SQLite3;
use Doctrine\DBAL\Driver\SQLSrv;
use Doctrine\DBAL\Exception\MalformedDsnException;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\Deprecations\Deprecation;
use SensitiveParameter;

use function array_keys;
use function array_merge;
use function is_a;

/**
 * Factory for creating {@see Connection} instances.
 *
 * @phpstan-type OverrideParams = array{
 *     application_name?: string,
 *     charset?: string,
 *     dbname?: string,
 *     default_dbname?: string,
 *     driver?: key-of<self::DRIVER_MAP>,
 *     driverClass?: class-string<Driver>,
 *     driverOptions?: array<mixed>,
 *     host?: string,
 *     password?: string,
 *     path?: string,
 *     persistent?: bool,
 *     platform?: Platforms\AbstractPlatform,
 *     port?: int,
 *     serverVersion?: string,
 *     url?: string,
 *     user?: string,
 *     unix_socket?: string,
 * }
 * @phpstan-type Params = array{
 *     application_name?: string,
 *     charset?: string,
 *     dbname?: string,
 *     defaultTableOptions?: array<string, mixed>,
 *     default_dbname?: string,
 *     driver?: key-of<self::DRIVER_MAP>,
 *     driverClass?: class-string<Driver>,
 *     driverOptions?: array<mixed>,
 *     host?: string,
 *     keepSlave?: bool,
 *     keepReplica?: bool,
 *     master?: OverrideParams,
 *     memory?: bool,
 *     password?: string,
 *     path?: string,
 *     persistent?: bool,
 *     platform?: Platforms\AbstractPlatform,
 *     port?: int,
 *     primary?: OverrideParams,
 *     replica?: array<OverrideParams>,
 *     serverVersion?: string,
 *     sharding?: array<string,mixed>,
 *     slaves?: array<OverrideParams>,
 *     url?: string,
 *     user?: string,
 *     wrapperClass?: class-string<Connection>,
 *     unix_socket?: string,
 * }
 */
final class DriverManager
{
    /**
     * List of supported drivers and their mappings to the driver classes.
     *
     * To add your own driver use the 'driverClass' parameter to {@see DriverManager::getConnection()}.
     */
    private const DRIVER_MAP = [
        'pdo_mysql'          => PDO\MySQL\Driver::class,
        'pdo_sqlite'         => PDO\SQLite\Driver::class,
        'pdo_pgsql'          => PDO\PgSQL\Driver::class,
        'pdo_oci'            => PDO\OCI\Driver::class,
        'oci8'               => OCI8\Driver::class,
        'ibm_db2'            => IBMDB2\Driver::class,
        'pdo_sqlsrv'         => PDO\SQLSrv\Driver::class,
        'mysqli'             => Mysqli\Driver::class,
        'pgsql'              => PgSQL\Driver::class,
        'sqlsrv'             => SQLSrv\Driver::class,
        'sqlite3'            => SQLite3\Driver::class,
    ];

    /**
     * List of URL schemes from a database URL and their mappings to driver.
     *
     * @deprecated Use actual driver names instead.
     *
     * @var array<string, string>
     * @phpstan-var array<string, key-of<self::DRIVER_MAP>>
     */
    private static array $driverSchemeAliases = [
        'db2'        => 'ibm_db2',
        'mssql'      => 'pdo_sqlsrv',
        'mysql'      => 'pdo_mysql',
        'mysql2'     => 'pdo_mysql', // Amazon RDS, for some weird reason
        'postgres'   => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
        'pgsql'      => 'pdo_pgsql',
        'sqlite'     => 'pdo_sqlite',
        'sqlite3'    => 'pdo_sqlite',
    ];

    /**
     * Private constructor. This class cannot be instantiated.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Creates a connection object based on the specified parameters.
     * This method returns a Doctrine\DBAL\Connection which wraps the underlying
     * driver connection.
     *
     * $params must contain at least one of the following.
     *
     * Either 'driver' with one of the array keys of {@see DRIVER_MAP},
     * OR 'driverClass' that contains the full class name (with namespace) of the
     * driver class to instantiate.
     *
     * Other (optional) parameters:
     *
     * <b>user (string)</b>:
     * The username to use when connecting.
     *
     * <b>password (string)</b>:
     * The password to use when connecting.
     *
     * <b>driverOptions (array)</b>:
     * Any additional driver-specific options for the driver. These are just passed
     * through to the driver.
     *
     * <b>wrapperClass</b>:
     * You may specify a custom wrapper class through the 'wrapperClass'
     * parameter but this class MUST inherit from Doctrine\DBAL\Connection.
     *
     * <b>driverClass</b>:
     * The driver class to use.
     *
     * @param Configuration|null $config       The configuration to use.
     * @param EventManager|null  $eventManager The event manager to use.
     * @phpstan-param Params $params
     *
     * @phpstan-return ($params is array{wrapperClass: class-string<T>} ? T : Connection)
     *
     * @throws Exception
     *
     * @template T of Connection
     */
    public static function getConnection(
        #[SensitiveParameter]
        array $params,
        ?Configuration $config = null,
        ?EventManager $eventManager = null
    ): Connection {
        // create default config and event manager, if not set
        $config       ??= new Configuration();
        $eventManager ??= new EventManager();
        $params         = self::parseDatabaseUrl($params);

        // URL support for PrimaryReplicaConnection
        if (isset($params['primary'])) {
            $params['primary'] = self::parseDatabaseUrl($params['primary']);
        }

        if (isset($params['replica'])) {
            foreach ($params['replica'] as $key => $replicaParams) {
                $params['replica'][$key] = self::parseDatabaseUrl($replicaParams);
            }
        }

        $driver = self::createDriver($params['driver'] ?? null, $params['driverClass'] ?? null);

        foreach ($config->getMiddlewares() as $middleware) {
            $driver = $middleware->wrap($driver);
        }

        $wrapperClass = $params['wrapperClass'] ?? Connection::class;
        if (! is_a($wrapperClass, Connection::class, true)) {
            throw Exception::invalidWrapperClass($wrapperClass);
        }

        return new $wrapperClass($params, $driver, $config, $eventManager);
    }

    /**
     * Returns the list of supported drivers.
     *
     * @return string[]
     * @phpstan-return list<key-of<self::DRIVER_MAP>>
     */
    public static function getAvailableDrivers(): array
    {
        return array_keys(self::DRIVER_MAP);
    }

    /**
     * @throws Exception
     *
     * @phpstan-assert key-of<self::DRIVER_MAP>|null $driver
     * @phpstan-assert class-string<Driver>|null     $driverClass
     */
    private static function createDriver(?string $driver, ?string $driverClass): Driver
    {
        if ($driverClass === null) {
            if ($driver === null) {
                throw Exception::driverRequired();
            }

            if (! isset(self::DRIVER_MAP[$driver])) {
                throw Exception::unknownDriver($driver, array_keys(self::DRIVER_MAP));
            }

            $driverClass = self::DRIVER_MAP[$driver];
        } elseif (! is_a($driverClass, Driver::class, true)) {
            throw Exception::invalidDriverClass($driverClass);
        }

        return new $driverClass();
    }

    /**
     * Extracts parts from a database URL, if present, and returns an
     * updated list of parameters.
     *
     * @param mixed[] $params The list of parameters.
     * @phpstan-param Params $params
     *
     * @return mixed[] A modified list of parameters with info from a database
     *                 URL extracted into indidivual parameter parts.
     * @phpstan-return Params
     *
     * @throws Exception
     */
    private static function parseDatabaseUrl(
        #[SensitiveParameter]
        array $params
    ): array {
        if (! isset($params['url'])) {
            return $params;
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5843',
            'The "url" connection parameter is deprecated. Please use %s to parse a database url before calling %s.',
            DsnParser::class,
            self::class,
        );

        $parser = new DsnParser(self::$driverSchemeAliases);
        try {
            $parsedParams = $parser->parse($params['url']);
        } catch (MalformedDsnException $e) {
            throw new Exception('Malformed parameter "url".', 0, $e);
        }

        if (isset($parsedParams['driver'])) {
            // The requested driver from the URL scheme takes precedence
            // over the default custom driver from the connection parameters (if any).
            unset($params['driverClass']);
        }

        $params = array_merge($params, $parsedParams);

        // If a schemeless connection URL is given, we require a default driver or default custom driver
        // as connection parameter.
        if (! isset($params['driverClass']) && ! isset($params['driver'])) {
            throw Exception::driverRequired($params['url']);
        }

        return $params;
    }
}
