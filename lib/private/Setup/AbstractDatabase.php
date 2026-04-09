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

	/**
	 * Returns the display name of the database system (e.g., "MySQL/MariaDB", "PostgreSQL")
	 */
	abstract protected function getDisplayName(): string;

	/**
	 * Validates the database configuration
	 *
	 * @param array $config Configuration array containing database credentials and settings
	 * @return array Array of validation error messages (empty if valid)
	 */
	public function validate(array $config): array {
		$errors = [];

		$errors = array_merge($errors, $this->validateRequiredFields($config));
		$errors = array_merge($errors, $this->validateDatabaseName($config));

		return $errors;
	}

	protected function validateRequiredFields(array $config): array {
		$errors = [];

		$dbUser = $config['dbuser'] ?? '';
		$dbName = $config['dbname'] ?? '';
		$displayName = $this->getDisplayName();

		// Check for missing required parameters
		if (empty($dbUser) && empty($dbName)) {
			$errors[] = $this->trans->t('Enter the database Login and name for %s', [$displayName]);
		} elseif (empty($dbUser)) {
			$errors[] = $this->trans->t('Enter the database Login for %s', [$displayName]);
		} elseif (empty($dbName)) {
			$errors[] = $this->trans->t('Enter the database name for %s', [$displayName]);
		}

		return $errors;
	}

	protected function validateDatabaseName(array $config): array {
		$errors = [];

		$dbName = $config['dbname'] ?? '';

		if (empty($dbName)) {
			return $errors;
		}

		// Avoid downsides of supporting database names with dots (`.`)
		if (str_contains($dbName, '.')) {
			$errors[] = $this->trans->t('You cannot use dots in the database name %s', [$this->getDisplayName()]);
		}

		// Note: Child classes should implement db specific name validations
		// (optionally still calling this parent for default validations)
		// (e.g. length, characters, casing, starting character, reserved words)

		return $errors;
	}

	public function initialize(array $config): void {
		$dbParams = $this->extractDatabaseParameters($config);

		// Should a database user/credential set be created automatically?
		$this->tryCreateDbUser = $this->shouldCreateDbUser();

		// Persist database configuration to config.php
		$this->config->setValues([
			'dbname' => $dbParams['name'],
			'dbhost' => $dbParams['host'],
			'dbtableprefix' => $dbParams['tablePrefix'],
		]);

		// Set instance properties from database parameters for subsequent operations (e.g. connecting)
		$this->setInstanceProperties($dbParams);
	}

	protected function extractDatabaseParameters(array $config): array {
		return [
			// Guaranteed to exist after validate() (exceptions override initialize() - i.e. SQLite)
			'user' => $config['dbuser'] ?? throw new \InvalidArgumentException('dbuser is required'),
			'name' => $config['dbname'] ?? throw new \InvalidArgumentException('dbname is required'),
			// Password can be empty for some setups / code paths
			'pass' => $config['dbpass'] ?? '',
			'host' => !empty($config['dbhost']) ? $config['dbhost'] : 'localhost',
			'port' => !empty($config['dbport']) ? $config['dbport'] : '',
			'tablePrefix' => $config['dbtableprefix'] ?? 'oc_',
		];
	}

	protected function shouldCreateDbUser(): bool {
		$createUserConfig = $this->config->getValue('setup_create_db_user', true);
		// Accept `false` both as bool and string, since env vars result in strings
		return $createUserConfig !== false && $createUserConfig !== 'false';
	}

	protected function setInstanceProperties(array $dbParams): void {
		$this->dbUser = $dbParams['user'];
		$this->dbPassword = $dbParams['pass'];
		$this->dbName = $dbParams['name'];
		$this->dbHost = $dbParams['host'];
		$this->dbPort = $dbParams['port'];
		$this->tablePrefix = $dbParams['tablePrefix'];
	}

	/**
	 * @param array $configOverwrite Optional parameters to override (e.g., ['dbname' => null])
	 */
	protected function connect(array $configOverwrite = []): Connection {
		// Build base connection parameters
		$connectionParams = [
			'host' => $this->dbHost,
			'user' => $this->dbUser,
			'password' => $this->dbPassword,
			'tablePrefix' => $this->tablePrefix,
			'dbname' => $this->dbName
		];

		// Apply port and socket configuration
		$connectionParams = $this->applyPortAndSocketConfig($connectionParams);

		// Apply any caller-provided overrides (e.g., dbname => null)
		$connectionParams = array_merge($connectionParams, $configOverwrite);

		// Configure for primary/replica topology (both point to same server during install)
		$connectionParams['primary'] = $connectionParams;
		$connectionParams['replica'] = [$connectionParams];

		$dbType = $this->config->getValue('dbtype', 'sqlite');

		$this->logger->debug('Creating database connection', [
			'dbtype' => $dbType,
			'host' => $connectionParams['host'] ?? 'unknown',
			'port' => $connectionParams['port'] ?? $connectionParams['unix_socket'] ?? 'default',
			'dbname' => $connectionParams['dbname'] ?? 'none',
		]);

		// Create and return the connection
		$cf = new ConnectionFactory($this->config);
		$connection = $cf->getConnection($dbType, $connectionParams);
		$connection->ensureConnectedToPrimary();
		return $connection;
	}

	protected function applyPortAndSocketConfig(array $params): array {
		// Check if port/socket is specified in the dedicated port field (only used by installer)
		if (!empty($this->dbPort)) {
			if (ctype_digit($this->dbPort)) {
				$params['port'] = $this->dbPort;
			} else {
				$params['unix_socket'] = $this->dbPort;
			}
			return $params;
		}

		// Check if port/socket is embedded in the hostname (e.g., "localhost:3306")
		if (str_contains($this->dbHost, ':')) {
			[$host, $portOrSocket] = explode(':', $this->dbHost, 2);
			$params['host'] = $host;
			// Host variable may carry a port or socket.
			if (ctype_digit($portOrSocket)) {
				$params['port'] = $portOrSocket;
			} else {
				$params['unix_socket'] = $portOrSocket;
			}
		}
		return $params;
	}

	/**
	 * Sets up the database (creates database, users, etc.)
	 *
	 * Must be implemented by database-specific child classes
	 */
	abstract public function setupDatabase(): void;

	/**
	 * @throws \Exception If migration fails (handled by caller)
	 */
	public function runMigrations(?IOutput $output = null): void {
		$migrationsPath = \OC::$SERVERROOT . '/core/Migrations';

		if (!is_dir($migrationsPath)) {
			$this->logger->debug('Skipping migrations - directory not found: {path}', [
				'path' => $migrationsPath,
			]);
			return; // @todo: should we throw an Exception here instead to let caller decide?
		}

		$this->logger->info('Starting core database migrations');

		$migrationService = new MigrationService('core', Server::get(Connection::class), $output);

		// Migrate to latest version, applying schema changes only
		// (no data migrations needed for fresh install)
		$migrationService->migrate('latest', true);

		$this->logger->info('Core database migrations completed successfully');
	}
}
