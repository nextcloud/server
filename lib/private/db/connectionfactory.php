<?php
/**
 * Copyright (c) 2014 Andreas Fischer <bantu@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;
use Doctrine\DBAL\Event\Listeners\OracleSessionInit;
use Doctrine\DBAL\Event\Listeners\SQLSessionInit;
use Doctrine\DBAL\Event\Listeners\MysqlSessionInit;

/**
* Takes care of creating and configuring Doctrine connections.
*/
class ConnectionFactory {
	/**
	* @var array
	*
	* Array mapping DBMS type to default connection parameters passed to
	* \Doctrine\DBAL\DriverManager::getConnection().
	*/
	protected $defaultConnectionParams = array(
		'mssql' => array(
			'adapter' => '\OC\DB\AdapterSQLSrv',
			'charset' => 'UTF8',
			'driver' => 'pdo_sqlsrv',
			'wrapperClass' => 'OC\DB\Connection',
		),
		'mysql' => array(
			'adapter' => '\OC\DB\AdapterMySQL',
			'charset' => 'UTF8',
			'driver' => 'pdo_mysql',
			'wrapperClass' => 'OC\DB\Connection',
		),
		'oci' => array(
			'adapter' => '\OC\DB\AdapterOCI8',
			'charset' => 'AL32UTF8',
			'driver' => 'oci8',
			'wrapperClass' => 'OC\DB\OracleConnection',
		),
		'pgsql' => array(
			'adapter' => '\OC\DB\AdapterPgSql',
			'driver' => 'pdo_pgsql',
			'wrapperClass' => 'OC\DB\Connection',
		),
		'sqlite3' => array(
			'adapter' => '\OC\DB\AdapterSqlite',
			'driver' => 'pdo_sqlite',
			'wrapperClass' => 'OC\DB\Connection',
		),
	);

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
			$result['driverOptions'] = array(
				\PDO::MYSQL_ATTR_FOUND_ROWS => true,
			);
		}
		return $result;
	}

	/**
	* @brief Get default connection parameters for a given DBMS.
	* @param string $type DBMS type
	* @param array $additionalConnectionParams Additional connection parameters
	* @return \OC\DB\Connection
	*/
	public function getConnection($type, $additionalConnectionParams) {
		$normalizedType = $this->normalizeType($type);
		$eventManager = new \Doctrine\Common\EventManager();
		switch ($normalizedType) {
			case 'mysql':
				// Send "SET NAMES utf8". Only required on PHP 5.3 below 5.3.6.
				// See http://stackoverflow.com/questions/4361459/php-pdo-charset-set-names#4361485
				$eventManager->addEventSubscriber(new MysqlSessionInit);
				$eventManager->addEventSubscriber(
					new SQLSessionInit("SET SESSION AUTOCOMMIT=1"));
				break;
			case 'oci':
				$eventManager->addEventSubscriber(new OracleSessionInit);
				break;
			case 'sqlite3':
				$journalMode = $additionalConnectionParams['sqlite.journal_mode'];
				$eventManager->addEventSubscriber(new SQLiteSessionInit(true, $journalMode));
				break;
		}
		$connection = \Doctrine\DBAL\DriverManager::getConnection(
			array_merge($this->getDefaultConnectionParams($type), $additionalConnectionParams),
			new \Doctrine\DBAL\Configuration(),
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
	* @brief Checks whether the specified DBMS type is valid.
	* @return bool
	*/
	public function isValidType($type) {
		$normalizedType = $this->normalizeType($type);
		return isset($this->defaultConnectionParams[$normalizedType]);
	}

	/**
	 * Create the connection parameters for the config
	 *
	 * @param \OC\SystemConfig $config
	 * @return array
	 */
	public function createConnectionParams($config) {
		$type = $config->getValue('dbtype', 'sqlite');

		$connectionParams = array(
			'user' => $config->getValue('dbuser', ''),
			'password' => $config->getValue('dbpassword', ''),
		);
		$name = $config->getValue('dbname', 'owncloud');

		if ($this->normalizeType($type) === 'sqlite3') {
			$dataDir = $config->getValue("datadirectory", \OC::$SERVERROOT . '/data');
			$connectionParams['path'] = $dataDir . '/' . $name . '.db';
		} else {
			$host = $config->getValue('dbhost', '');
			if (strpos($host, ':')) {
				// Host variable may carry a port or socket.
				list($host, $portOrSocket) = explode(':', $host, 2);
				if (ctype_digit($portOrSocket)) {
					$connectionParams['port'] = $portOrSocket;
				} else {
					$connectionParams['unix_socket'] = $portOrSocket;
				}
			}
			$connectionParams['host'] = $host;
			$connectionParams['dbname'] = $name;
		}

		$connectionParams['tablePrefix'] = $config->getValue('dbtableprefix', 'oc_');
		$connectionParams['sqlite.journal_mode'] = $config->getValue('sqlite.journal_mode', 'WAL');

		//additional driver options, eg. for mysql ssl
		$driverOptions = $config->getValue('dbdriveroptions', null);
		if ($driverOptions) {
			$connectionParams['driverOptions'] = $driverOptions;
		}

		return $connectionParams;
	}
}
