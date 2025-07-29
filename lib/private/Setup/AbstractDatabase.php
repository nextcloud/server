<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Setup;

use OC\DB\Connection;
use OC\DB\ConnectionFactory;
use OC\DB\MigrationService;
use OC\SystemConfig;
use OCP\IL10N;
use OCP\Migration\IOutput;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

abstract class AbstractDatabase {
	/** @var IL10N */
	protected $trans;
	/** @var string */
	protected $dbUser;
	/** @var string */
	protected $dbPassword;
	/** @var string */
	protected $dbName;
	/** @var string */
	protected $dbHost;
	/** @var string */
	protected $dbPort;
	/** @var string */
	protected $tablePrefix;
	/** @var SystemConfig */
	protected $config;
	/** @var LoggerInterface */
	protected $logger;
	/** @var ISecureRandom */
	protected $random;
	/** @var bool */
	protected $tryCreateDbUser;

	public function __construct(IL10N $trans, SystemConfig $config, LoggerInterface $logger, ISecureRandom $random) {
		$this->trans = $trans;
		$this->config = $config;
		$this->logger = $logger;
		$this->random = $random;
	}

	public function validate($config) {
		$errors = [];
		if (empty($config['dbuser']) && empty($config['dbname'])) {
			$errors[] = $this->trans->t('Enter the database Login and name for %s', [$this->dbprettyname]);
		} elseif (empty($config['dbuser'])) {
			$errors[] = $this->trans->t('Enter the database Login for %s', [$this->dbprettyname]);
		} elseif (empty($config['dbname'])) {
			$errors[] = $this->trans->t('Enter the database name for %s', [$this->dbprettyname]);
		}
		if (substr_count($config['dbname'], '.') >= 1) {
			$errors[] = $this->trans->t('You cannot use dots in the database name %s', [$this->dbprettyname]);
		}
		return $errors;
	}

	public function initialize($config) {
		$dbUser = $config['dbuser'];
		$dbPass = $config['dbpass'];
		$dbName = $config['dbname'];
		$dbHost = !empty($config['dbhost']) ? $config['dbhost'] : 'localhost';
		$dbPort = !empty($config['dbport']) ? $config['dbport'] : '';
		$dbTablePrefix = $config['dbtableprefix'] ?? 'oc_';

		$createUserConfig = $this->config->getValue('setup_create_db_user', true);
		// accept `false` both as bool and string, since setting config values from env will result in a string
		$this->tryCreateDbUser = $createUserConfig !== false && $createUserConfig !== 'false';

		$this->config->setValues([
			'dbname' => $dbName,
			'dbhost' => $dbHost,
			'dbport' => $dbPort,
			'dbtableprefix' => $dbTablePrefix,
		]);

		$this->dbUser = $dbUser;
		$this->dbPassword = $dbPass;
		$this->dbName = $dbName;
		$this->dbHost = $dbHost;
		$this->dbPort = $dbPort;
		$this->tablePrefix = $dbTablePrefix;
	}

	/**
	 * @param array $configOverwrite
	 * @return \OC\DB\Connection
	 */
	protected function connect(array $configOverwrite = []): Connection {
		$connectionParams = [
			'host' => $this->dbHost,
			'user' => $this->dbUser,
			'password' => $this->dbPassword,
			'tablePrefix' => $this->tablePrefix,
			'dbname' => $this->dbName
		];

		// adding port support through installer
		if (!empty($this->dbPort)) {
			if (ctype_digit($this->dbPort)) {
				$connectionParams['port'] = $this->dbPort;
			} else {
				$connectionParams['unix_socket'] = $this->dbPort;
			}
		} elseif (strpos($this->dbHost, ':')) {
			// Host variable may carry a port or socket.
			[$host, $portOrSocket] = explode(':', $this->dbHost, 2);
			if (ctype_digit($portOrSocket)) {
				$connectionParams['port'] = $portOrSocket;
			} else {
				$connectionParams['unix_socket'] = $portOrSocket;
			}
			$connectionParams['host'] = $host;
		}
		$connectionParams = array_merge($connectionParams, $configOverwrite);
		$connectionParams = array_merge($connectionParams, ['primary' => $connectionParams, 'replica' => [$connectionParams]]);
		$cf = new ConnectionFactory($this->config);
		$connection = $cf->getConnection($this->config->getValue('dbtype', 'sqlite'), $connectionParams);
		$connection->ensureConnectedToPrimary();
		return $connection;
	}

	abstract public function setupDatabase();

	public function runMigrations(?IOutput $output = null) {
		if (!is_dir(\OC::$SERVERROOT . '/core/Migrations')) {
			return;
		}
		$ms = new MigrationService('core', \OC::$server->get(Connection::class), $output);
		$ms->migrate('latest', true);
	}
}
