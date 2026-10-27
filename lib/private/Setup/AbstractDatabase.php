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
use OCP\Server;
use Psr\Log\LoggerInterface;

abstract class AbstractDatabase {
	/**
	 * Installer options configuring an encrypted database connection.
	 * @var string[]
	 */
	protected const array CONNECTION_ENCRYPTION_OPTIONS = ['dbdriveroptions'];

	/**
	 * Installer options describing an encrypted database connection independently of the
	 * database in use, as provided by the web installer and `occ maintenance:install`.
	 * @var string[]
	 */
	protected const array ENCRYPTION_OPTIONS = ['dbsslmode', 'dbsslca', 'dbsslcert', 'dbsslkey', 'dbsslcrl', 'dbsslnoverify'];

	/**
	 * The subset of {@see static::ENCRYPTION_OPTIONS} this database supports.
	 * @var string[]
	 */
	protected const array SUPPORTED_ENCRYPTION_OPTIONS = [];

	protected string $dbprettyname = 'abstract';

	protected string $dbUser;
	protected string $dbPassword;
	protected string $dbName;
	protected string $dbHost;
	protected string $dbPort;
	protected string $tablePrefix;
	protected bool $tryCreateDbUser;

	public function __construct(
		protected IL10N $trans,
		protected SystemConfig $config,
		protected LoggerInterface $logger,
		protected ISecureRandom $random,
	) {
	}

	public function validate(array $config): array {
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
		return array_merge($errors, $this->validateEncryptionOptions($config));
	}

	/**
	 * Validate the installer options configuring an encrypted database connection.
	 *
	 * @param array $config The options passed to the installer
	 * @return string[]
	 */
	protected function validateEncryptionOptions(array $config): array {
		$errors = [];
		foreach (static::CONNECTION_ENCRYPTION_OPTIONS as $option) {
			if (isset($config[$option]) && !is_array($config[$option])) {
				$errors[] = $this->trans->t('The database option "%1$s" for %2$s has to be a list of values', [$option, $this->dbprettyname]);
			}
		}
		foreach (static::ENCRYPTION_OPTIONS as $option) {
			if (!empty($config[$option]) && !in_array($option, static::SUPPORTED_ENCRYPTION_OPTIONS, true)) {
				$errors[] = $this->trans->t('The database option "%1$s" is not supported by %2$s', [$option, $this->dbprettyname]);
			}
		}
		// A client certificate is useless without its private key and vice versa
		if (in_array('dbsslcert', static::SUPPORTED_ENCRYPTION_OPTIONS, true)
			&& empty($config['dbsslcert']) !== empty($config['dbsslkey'])) {
			$errors[] = $this->trans->t('The database options "dbsslcert" and "dbsslkey" have to be provided together');
		}
		return $errors;
	}

	/**
	 * Translate the `ENCRYPTION_OPTIONS` into the system config values that
	 * configure an encrypted connection for this database.
	 *
	 * @param array $config The options passed to the installer
	 * @return array<string, array> System config values, empty if no option was provided
	 */
	protected function getEncryptionConfig(array $config): array {
		return [];
	}

	public function initialize(array $config): void {
		$dbUser = $config['dbuser'];
		$dbPass = $config['dbpass'];
		$dbName = $config['dbname'];
		$dbHost = !empty($config['dbhost']) ? $config['dbhost'] : 'localhost';
		$dbPort = !empty($config['dbport']) ? $config['dbport'] : '';
		$dbTablePrefix = $config['dbtableprefix'] ?? 'oc_';

		$createUserConfig = $this->config->getValue('setup_create_db_user', true);
		// accept `false` both as bool and string, since setting config values from env will result in a string
		$this->tryCreateDbUser = $createUserConfig !== false && $createUserConfig !== 'false';

		$configValues = [
			'dbname' => $dbName,
			'dbhost' => $dbHost,
			'dbtableprefix' => $dbTablePrefix,
		];

		// An encrypted connection can only be configured through the system config, so the
		// options have to be persisted before the first connection is opened.
		foreach (static::CONNECTION_ENCRYPTION_OPTIONS as $option) {
			if (empty($config[$option])) {
				continue;
			}
			if (!is_array($config[$option])) {
				// Rejected by validate() already, but subclasses may not use that check
				$this->logger->error('Ignoring database option "{option}" passed to the installer because it is not a list of values', ['option' => $option]);
				continue;
			}
			$configValues[$option] = $config[$option];
		}

		// The database independent options end up in the same config values, so they are
		// applied on top of any raw value provided, e.g. through an autoconfig file.
		// array_replace() instead of array_merge() to keep the numeric PDO attribute keys.
		foreach ($this->getEncryptionConfig($config) as $option => $value) {
			$configValues[$option] = array_replace($configValues[$option] ?? [], $value);
		}

		$this->config->setValues($configValues);

		$this->dbUser = $dbUser;
		$this->dbPassword = $dbPass;
		$this->dbName = $dbName;
		$this->dbHost = $dbHost;
		$this->dbPort = $dbPort;
		$this->tablePrefix = $dbTablePrefix;
	}

	/**
	 * Generate a strong random password suitable for database user accounts.
	 *
	 * Guarantees at least 2 uppercase, 2 lowercase, 2 digit, and 2 symbol
	 * characters are present, with symbols filtered to exclude characters
	 * that are problematic in SQL string contexts (", \, ', `).
	 *
	 * @return string A 30-character random password
	 */
	protected function generateDbPassword(): string {
		$safeSymbols = str_replace(['\"', '\\', '\'', '`'], '', ISecureRandom::CHAR_SYMBOLS);

		$password = $this->random->generate(22, ISecureRandom::CHAR_ALPHANUMERIC . $safeSymbols)
			. $this->random->generate(2, ISecureRandom::CHAR_UPPER)
			. $this->random->generate(2, ISecureRandom::CHAR_LOWER)
			. $this->random->generate(2, ISecureRandom::CHAR_DIGITS)
			. $this->random->generate(2, $safeSymbols);

		return str_shuffle($password);
	}

	/**
	 * Create a new connection factory for the database.
	 */
	protected function createConnectionFactory(): ConnectionFactory {
		// needed mostly because the factory caches `mysql.utf8mb4` within the constructor
		// and we need to allow re-connect with new value (see MySQL::setupDatabase)
		return new ConnectionFactory($this->config);
	}

	/**
	 * Connect to the database that is currently being set up.
	 *
	 * Host, database name and table prefix are resolved from the system config by the connection factory,
	 * so this only needs to additionally pass the credentials entered during setup.
	 *
	 * @param array $configOverwrite Connection parameters taking precedence over the resolved ones
	 */
	protected function connect(array $configOverwrite = []): Connection {
		// The credentials entered during setup are only written to the config once the
		// database user has been set up, so they have to be passed explicitly.
		$connectionParams = [
			'user' => $this->dbUser,
			'password' => $this->dbPassword,
		];

		// There is no `dbport` config value in the config - the port is part of `dbhost` - so a port
		// provided by the installer can only be passed here. If set it takes precedence
		// over a port or socket carried by the host.
		if (!empty($this->dbPort)) {
			if (ctype_digit($this->dbPort)) {
				$connectionParams['port'] = (int)$this->dbPort;
			} else {
				$connectionParams['unix_socket'] = $this->dbPort;
			}
		}

		$connectionParams = array_merge($connectionParams, $configOverwrite);

		$connection = $this->createConnectionFactory()
			->getConnection($this->config->getValue('dbtype', 'sqlite'), $connectionParams);
		$connection->ensureConnectedToPrimary();
		return $connection;
	}

	abstract public function setupDatabase();

	public function runMigrations(?IOutput $output = null) {
		if (!is_dir(\OC::$SERVERROOT . '/core/Migrations')) {
			return;
		}
		$ms = new MigrationService('core', Server::get(Connection::class), $output);
		$ms->migrate('latest', true);
	}
}
