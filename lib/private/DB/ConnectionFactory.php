<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Event\Listeners\OracleSessionInit;
use OC\DB\QueryBuilder\Sharded\AutoIncrementHandler;
use OC\DB\QueryBuilder\Sharded\ShardConnectionManager;
use OC\SystemConfig;
use OCP\ICacheFactory;
use OCP\Server;

/**
 * Takes care of creating and configuring Doctrine connections.
 */
class ConnectionFactory {
	/** @var string default database name */
	public const DEFAULT_DBNAME = 'owncloud';

	/** @var string default database table prefix */
	public const DEFAULT_DBTABLEPREFIX = 'oc_';

	/**
	 * @var array
	 *
	 * Array mapping DBMS type to default connection parameters passed to
	 * \Doctrine\DBAL\DriverManager::getConnection().
	 */
	protected $defaultConnectionParams = [
		'mysql' => [
			'adapter' => AdapterMySQL::class,
			'charset' => 'UTF8',
			'driver' => 'pdo_mysql',
			'wrapperClass' => Connection::class,
		],
		'oci' => [
			'adapter' => AdapterOCI8::class,
			'charset' => 'AL32UTF8',
			'driver' => 'oci8',
			'wrapperClass' => OracleConnection::class,
		],
		'pgsql' => [
			'adapter' => AdapterPgSql::class,
			'driver' => 'pdo_pgsql',
			'wrapperClass' => Connection::class,
		],
		'sqlite3' => [
			'adapter' => AdapterSqlite::class,
			'driver' => 'pdo_sqlite',
			'wrapperClass' => Connection::class,
		],
	];

	private ShardConnectionManager $shardConnectionManager;
	private ICacheFactory $cacheFactory;

	public function __construct(
		private SystemConfig $config,
		?ICacheFactory $cacheFactory = null,
	) {
		if ($this->config->getValue('mysql.utf8mb4', false)) {
			$this->defaultConnectionParams['mysql']['charset'] = 'utf8mb4';
		}
		$collationOverride = $this->config->getValue('mysql.collation', null);
		if ($collationOverride) {
			$this->defaultConnectionParams['mysql']['collation'] = $collationOverride;
		}
		$this->shardConnectionManager = new ShardConnectionManager($this->config, $this);
		$this->cacheFactory = $cacheFactory ?? Server::get(ICacheFactory::class);
	}

	/**
	 * @brief Get default connection parameters for a given DBMS.
	 * @param string $type DBMS type
	 * @throws \InvalidArgumentException If $type is invalid
	 * @return array Default connection parameters.
	 */
	public function getDefaultConnectionParams($type) {
		$normalizedType = $this->normalizeType($type);
		if (!isset($this->defaultConnectionParams[$normalizedType])) {
			throw new \InvalidArgumentException("Unsupported type: $type");
		}
		$result = $this->defaultConnectionParams[$normalizedType];
		// \PDO::MYSQL_ATTR_FOUND_ROWS may not be defined, e.g. when the MySQL
		// driver is missing. In this case, we won't be able to connect anyway.
		if ($normalizedType === 'mysql' && defined('\PDO::MYSQL_ATTR_FOUND_ROWS')) {
			$result['driverOptions'] = [
				\PDO::MYSQL_ATTR_FOUND_ROWS => true,
			];
		}
		return $result;
	}

	/**
	 * @brief Get default connection parameters for a given DBMS.
	 * @param string $type DBMS type
	 * @param array $additionalConnectionParams Additional connection parameters
	 * @return \OC\DB\Connection
	 */
	public function getConnection(string $type, array $additionalConnectionParams): Connection {
		$normalizedType = $this->normalizeType($type);
		$eventManager = new EventManager();
		$eventManager->addEventSubscriber(new SetTransactionIsolationLevel());
		$connectionParams = $this->createConnectionParams('', $additionalConnectionParams, $type);
		switch ($normalizedType) {
			case 'pgsql':
				// pg_connect used by Doctrine DBAL does not support URI notation (enclosed in brackets)
				$matches = [];
				if (preg_match('/^\[([^\]]+)\]$/', $connectionParams['host'], $matches)) {
					// Host variable carries a port or socket.
					$connectionParams['host'] = $matches[1];
				}
				break;

			case 'oci':
				$eventManager->addEventSubscriber(new OracleSessionInit);
				$connectionParams = $this->forceConnectionStringOracle($connectionParams);
				$connectionParams['primary'] = $this->forceConnectionStringOracle($connectionParams['primary']);
				$connectionParams['replica'] = array_map([$this, 'forceConnectionStringOracle'], $connectionParams['replica']);
				break;

			case 'sqlite3':
				$journalMode = $connectionParams['sqlite.journal_mode'];
				$connectionParams['platform'] = new OCSqlitePlatform();
				$eventManager->addEventSubscriber(new SQLiteSessionInit(true, $journalMode));
				break;
		}
		/** @var Connection $connection */
		$connection = DriverManager::getConnection(
			$connectionParams,
			new Configuration(),
			$eventManager
		);
		return $connection;
	}

	/**
	 * @brief Normalize DBMS type
	 * @param string $type DBMS type
	 * @return string Normalized DBMS type
	 */
	public function normalizeType($type) {
		return $type === 'sqlite' ? 'sqlite3' : $type;
	}

	/**
	 * Checks whether the specified DBMS type is valid.
	 *
	 * @param string $type
	 * @return bool
	 */
	public function isValidType($type) {
		$normalizedType = $this->normalizeType($type);
		return isset($this->defaultConnectionParams[$normalizedType]);
	}

	/**
	 * Create the connection parameters for the config
	 */
	public function createConnectionParams(string $configPrefix = '', array $additionalConnectionParams = [], ?string $type = null) {
		// use provided type or if null use type from config
		$type = $type ?? $this->config->getValue('dbtype', 'sqlite');

		$connectionParams = array_merge($this->getDefaultConnectionParams($type), [
			'user' => $this->config->getValue($configPrefix . 'dbuser', $this->config->getValue('dbuser', '')),
			'password' => $this->config->getValue($configPrefix . 'dbpassword', $this->config->getValue('dbpassword', '')),
		]);
		$name = $this->config->getValue($configPrefix . 'dbname', $this->config->getValue('dbname', self::DEFAULT_DBNAME));

		if ($this->normalizeType($type) === 'sqlite3') {
			$dataDir = $this->config->getValue('datadirectory', \OC::$SERVERROOT . '/data');
			$connectionParams['path'] = $dataDir . '/' . $name . '.db';
		} else {
			$host = $this->config->getValue($configPrefix . 'dbhost', $this->config->getValue('dbhost', ''));
			$connectionParams = array_merge($connectionParams, $this->splitHostFromPortAndSocket($host));
			$connectionParams['dbname'] = $name;
		}

		$connectionParams['tablePrefix'] = $this->config->getValue('dbtableprefix', self::DEFAULT_DBTABLEPREFIX);
		$connectionParams['sqlite.journal_mode'] = $this->config->getValue('sqlite.journal_mode', 'WAL');

		//additional driver options, eg. for mysql ssl
		$driverOptions = $this->config->getValue($configPrefix . 'dbdriveroptions', $this->config->getValue('dbdriveroptions', null));
		if ($driverOptions) {
			$connectionParams['driverOptions'] = $driverOptions;
		}

		// set default table creation options
		$connectionParams['defaultTableOptions'] = [
			'collate' => 'utf8_bin',
			'tablePrefix' => $connectionParams['tablePrefix']
		];

		if ($type === 'pgsql') {
			$pgsqlSsl = $this->config->getValue('pgsql_ssl', false);
			if (is_array($pgsqlSsl)) {
				$connectionParams['sslmode'] = $pgsqlSsl['mode'] ?? '';
				$connectionParams['sslrootcert'] = $pgsqlSsl['rootcert'] ?? '';
				$connectionParams['sslcert'] = $pgsqlSsl['cert'] ?? '';
				$connectionParams['sslkey'] = $pgsqlSsl['key'] ?? '';
				$connectionParams['sslcrl'] = $pgsqlSsl['crl'] ?? '';
			}
		}

		if ($type === 'mysql' && $this->config->getValue('mysql.utf8mb4', false)) {
			$connectionParams['defaultTableOptions'] = [
				'collate' => 'utf8mb4_bin',
				'charset' => 'utf8mb4',
				'tablePrefix' => $connectionParams['tablePrefix']
			];
		}

		if ($this->config->getValue('dbpersistent', false)) {
			$connectionParams['persistent'] = true;
		}

		$connectionParams['sharding'] = $this->config->getValue('dbsharding', []);
		if (!empty($connectionParams['sharding'])) {
			$connectionParams['shard_connection_manager'] = $this->shardConnectionManager;
			$connectionParams['auto_increment_handler'] = new AutoIncrementHandler(
				$this->cacheFactory,
				$this->shardConnectionManager,
			);
		} else {
			// just in case only the presence could lead to funny behaviour
			unset($connectionParams['sharding']);
		}

		$connectionParams = array_merge($connectionParams, $additionalConnectionParams);

		$replica = $this->config->getValue($configPrefix . 'dbreplica', $this->config->getValue('dbreplica', [])) ?: [$connectionParams];
		return array_merge($connectionParams, [
			'primary' => $connectionParams,
			'replica' => $replica,
		]);
	}

	/**
	 * @param string $host
	 * @return array
	 */
	protected function splitHostFromPortAndSocket($host): array {
		$params = [
			'host' => $host,
		];

		$matches = [];
		if (preg_match('/^(.*):([^\]:]+)$/', $host, $matches)) {
			// Host variable carries a port or socket.
			$params['host'] = $matches[1];
			if (is_numeric($matches[2])) {
				$params['port'] = (int)$matches[2];
			} else {
				$params['unix_socket'] = $matches[2];
			}
		}

		return $params;
	}

	protected function forceConnectionStringOracle(array $connectionParams): array {
		// the driverOptions are unused in dbal and need to be mapped to the parameters
		if (isset($connectionParams['driverOptions'])) {
			$connectionParams = array_merge($connectionParams, $connectionParams['driverOptions']);
		}
		$host = $connectionParams['host'];
		$port = $connectionParams['port'] ?? null;
		$dbName = $connectionParams['dbname'];

		// we set the connect string as dbname and unset the host to coerce doctrine into using it as connect string
		if ($host === '') {
			$connectionParams['dbname'] = $dbName; // use dbname as easy connect name
		} else {
			$connectionParams['dbname'] = '//' . $host . (!empty($port) ? ":{$port}" : '') . '/' . $dbName;
		}
		unset($connectionParams['host']);

		return $connectionParams;
	}
}
