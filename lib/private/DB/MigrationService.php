<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use OC\App\InfoParser;
use OC\IntegrityCheck\Helpers\AppLocator;
use OC\Migration\SimpleOutput;
use OCP\AppFramework\App;
use OCP\AppFramework\QueryException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IMigrationStep;
use OCP\Migration\IOutput;
use OCP\Server;
use Psr\Log\LoggerInterface;

class MigrationService {
	private bool $migrationTableCreated;
	private array $migrations;
	private string $migrationsPath;
	private string $migrationsNamespace;
	private IOutput $output;
	private LoggerInterface $logger;
	private Connection $connection;
	private string $appName;
	private bool $checkOracle;

	/**
	 * @throws \Exception
	 */
	public function __construct(string $appName, Connection $connection, ?IOutput $output = null, ?AppLocator $appLocator = null, ?LoggerInterface $logger = null) {
		$this->appName = $appName;
		$this->connection = $connection;
		if ($logger === null) {
			$this->logger = Server::get(LoggerInterface::class);
		} else {
			$this->logger = $logger;
		}
		if ($output === null) {
			$this->output = new SimpleOutput($this->logger, $appName);
		} else {
			$this->output = $output;
		}

		if ($appName === 'core') {
			$this->migrationsPath = \OC::$SERVERROOT . '/core/Migrations';
			$this->migrationsNamespace = 'OC\\Core\\Migrations';
			$this->checkOracle = true;
		} else {
			if ($appLocator === null) {
				$appLocator = new AppLocator();
			}
			$appPath = $appLocator->getAppPath($appName);
			$namespace = App::buildAppNamespace($appName);
			$this->migrationsPath = "$appPath/lib/Migration";
			$this->migrationsNamespace = $namespace . '\\Migration';

			$infoParser = new InfoParser();
			$info = $infoParser->parse($appPath . '/appinfo/info.xml');
			if (!isset($info['dependencies']['database'])) {
				$this->checkOracle = true;
			} else {
				$this->checkOracle = false;
				foreach ($info['dependencies']['database'] as $database) {
					if (\is_string($database) && $database === 'oci') {
						$this->checkOracle = true;
					} elseif (\is_array($database) && isset($database['@value']) && $database['@value'] === 'oci') {
						$this->checkOracle = true;
					}
				}
			}
		}
		$this->migrationTableCreated = false;
	}

	/**
	 * Returns the name of the app for which this migration is executed
	 */
	public function getApp(): string {
		return $this->appName;
	}

	/**
	 * @codeCoverageIgnore - this will implicitly tested on installation
	 */
	private function createMigrationTable(): bool {
		if ($this->migrationTableCreated) {
			return false;
		}

		if ($this->connection->tableExists('migrations') && \OC::$server->getConfig()->getAppValue('core', 'vendor', '') !== 'owncloud') {
			$this->migrationTableCreated = true;
			return false;
		}

		$schema = new SchemaWrapper($this->connection);

		/**
		 * We drop the table when it has different columns or the definition does not
		 * match. E.g. ownCloud uses a length of 177 for app and 14 for version.
		 */
		try {
			$table = $schema->getTable('migrations');
			$columns = $table->getColumns();

			if (count($columns) === 2) {
				try {
					$column = $table->getColumn('app');
					$schemaMismatch = $column->getLength() !== 255;

					if (!$schemaMismatch) {
						$column = $table->getColumn('version');
						$schemaMismatch = $column->getLength() !== 255;
					}
				} catch (SchemaException $e) {
					// One of the columns is missing
					$schemaMismatch = true;
				}

				if (!$schemaMismatch) {
					// Table exists and schema matches: return back!
					$this->migrationTableCreated = true;
					return false;
				}
			}

			// Drop the table, when it didn't match our expectations.
			$this->connection->dropTable('migrations');

			// Recreate the schema after the table was dropped.
			$schema = new SchemaWrapper($this->connection);
		} catch (SchemaException $e) {
			// Table not found, no need to panic, we will create it.
		}

		$table = $schema->createTable('migrations');
		$table->addColumn('app', Types::STRING, ['length' => 255]);
		$table->addColumn('version', Types::STRING, ['length' => 255]);
		$table->setPrimaryKey(['app', 'version']);

		$this->connection->migrateToSchema($schema->getWrappedSchema());

		$this->migrationTableCreated = true;

		return true;
	}

	/**
	 * Returns all versions which have already been applied
	 *
	 * @return list<string>
	 * @codeCoverageIgnore - no need to test this
	 */
	public function getMigratedVersions() {
		$this->createMigrationTable();
		$qb = $this->connection->getQueryBuilder();

		$qb->select('version')
			->from('migrations')
			->where($qb->expr()->eq('app', $qb->createNamedParameter($this->getApp())))
			->orderBy('version');

		$result = $qb->executeQuery();
		$rows = $result->fetchAll(\PDO::FETCH_COLUMN);
		$result->closeCursor();

		usort($rows, $this->sortMigrations(...));

		return $rows;
	}

	/**
	 * Returns all versions which are available in the migration folder
	 * @return list<string>
	 */
	public function getAvailableVersions(): array {
		$this->ensureMigrationsAreLoaded();
		$versions = array_map('strval', array_keys($this->migrations));
		usort($versions, $this->sortMigrations(...));
		return $versions;
	}

	protected function sortMigrations(string $a, string $b): int {
		preg_match('/(\d+)Date(\d+)/', basename($a), $matchA);
		preg_match('/(\d+)Date(\d+)/', basename($b), $matchB);
		if (!empty($matchA) && !empty($matchB)) {
			$versionA = (int)$matchA[1];
			$versionB = (int)$matchB[1];
			if ($versionA !== $versionB) {
				return ($versionA < $versionB) ? -1 : 1;
			}
			return strnatcmp($matchA[2], $matchB[2]);
		}
		return strnatcmp(basename($a), basename($b));
	}

	/**
	 * @return array<string, string>
	 */
	protected function findMigrations(): array {
		$directory = realpath($this->migrationsPath);
		if ($directory === false || !file_exists($directory) || !is_dir($directory)) {
			return [];
		}

		$iterator = new \RegexIterator(
			new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::LEAVES_ONLY
			),
			'#^.+\\/Version[^\\/]{1,255}\\.php$#i',
			\RegexIterator::GET_MATCH);

		$files = array_keys(iterator_to_array($iterator));
		usort($files, $this->sortMigrations(...));

		$migrations = [];

		foreach ($files as $file) {
			$className = basename($file, '.php');
			$version = (string)substr($className, 7);
			if ($version === '0') {
				throw new \InvalidArgumentException(
					"Cannot load a migrations with the name '$version' because it is a reserved number"
				);
			}
			$migrations[$version] = sprintf('%s\\%s', $this->migrationsNamespace, $className);
		}

		return $migrations;
	}

	/**
	 * @param string $to
	 * @return string[]
	 */
	private function getMigrationsToExecute($to) {
		$knownMigrations = $this->getMigratedVersions();
		$availableMigrations = $this->getAvailableVersions();

		$toBeExecuted = [];
		foreach ($availableMigrations as $v) {
			if ($to !== 'latest' && ($this->sortMigrations($v, $to) > 0)) {
				continue;
			}
			if ($this->shallBeExecuted($v, $knownMigrations)) {
				$toBeExecuted[] = $v;
			}
		}

		return $toBeExecuted;
	}

	/**
	 * @param string $m
	 * @param string[] $knownMigrations
	 * @return bool
	 */
	private function shallBeExecuted($m, $knownMigrations) {
		if (in_array($m, $knownMigrations)) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $version
	 */
	private function markAsExecuted($version) {
		$this->connection->insertIfNotExist('*PREFIX*migrations', [
			'app' => $this->appName,
			'version' => $version
		]);
	}

	/**
	 * Returns the name of the table which holds the already applied versions
	 *
	 * @return string
	 */
	public function getMigrationsTableName() {
		return $this->connection->getPrefix() . 'migrations';
	}

	/**
	 * Returns the namespace of the version classes
	 *
	 * @return string
	 */
	public function getMigrationsNamespace() {
		return $this->migrationsNamespace;
	}

	/**
	 * Returns the directory which holds the versions
	 *
	 * @return string
	 */
	public function getMigrationsDirectory() {
		return $this->migrationsPath;
	}

	/**
	 * Return the explicit version for the aliases; current, next, prev, latest
	 *
	 * @return mixed|null|string
	 */
	public function getMigration(string $alias) {
		switch ($alias) {
			case 'current':
				return $this->getCurrentVersion();
			case 'next':
				return $this->getRelativeVersion($this->getCurrentVersion(), 1);
			case 'prev':
				return $this->getRelativeVersion($this->getCurrentVersion(), -1);
			case 'latest':
				$this->ensureMigrationsAreLoaded();

				$migrations = $this->getAvailableVersions();
				return @end($migrations);
		}
		return '0';
	}

	private function getRelativeVersion(string $version, int $delta): ?string {
		$this->ensureMigrationsAreLoaded();

		$versions = $this->getAvailableVersions();
		array_unshift($versions, '0');
		/** @var int $offset */
		$offset = array_search($version, $versions, true);
		if ($offset === false || !isset($versions[$offset + $delta])) {
			// Unknown version or delta out of bounds.
			return null;
		}

		return (string)$versions[$offset + $delta];
	}

	private function getCurrentVersion(): string {
		$m = $this->getMigratedVersions();
		if (count($m) === 0) {
			return '0';
		}
		$migrations = array_values($m);
		return @end($migrations);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function getClass(string $version): string {
		$this->ensureMigrationsAreLoaded();

		if (isset($this->migrations[$version])) {
			return $this->migrations[$version];
		}

		throw new \InvalidArgumentException("Version $version is unknown.");
	}

	/**
	 * Allows to set an IOutput implementation which is used for logging progress and messages
	 */
	public function setOutput(IOutput $output): void {
		$this->output = $output;
	}

	/**
	 * Applies all not yet applied versions up to $to
	 * @throws \InvalidArgumentException
	 */
	public function migrate(string $to = 'latest', bool $schemaOnly = false): void {
		if ($schemaOnly) {
			$this->output->debug('Migrating schema only');
			$this->migrateSchemaOnly($to);
			return;
		}

		// read known migrations
		$toBeExecuted = $this->getMigrationsToExecute($to);
		foreach ($toBeExecuted as $version) {
			try {
				$this->executeStep($version, $schemaOnly);
			} catch (\Exception $e) {
				// The exception itself does not contain the name of the migration,
				// so we wrap it here, to make debugging easier.
				throw new \Exception('Database error when running migration ' . $version . ' for app ' . $this->getApp() . PHP_EOL . $e->getMessage(), 0, $e);
			}
		}
	}

	/**
	 * Applies all not yet applied versions up to $to
	 * @throws \InvalidArgumentException
	 */
	public function migrateSchemaOnly(string $to = 'latest'): void {
		// read known migrations
		$toBeExecuted = $this->getMigrationsToExecute($to);

		if (empty($toBeExecuted)) {
			return;
		}

		$toSchema = null;
		foreach ($toBeExecuted as $version) {
			$this->output->debug('- Reading ' . $version);
			$instance = $this->createInstance($version);

			$toSchema = $instance->changeSchema($this->output, function () use ($toSchema): ISchemaWrapper {
				return $toSchema ?: new SchemaWrapper($this->connection);
			}, ['tablePrefix' => $this->connection->getPrefix()]) ?: $toSchema;
		}

		if ($toSchema instanceof SchemaWrapper) {
			$this->output->debug('- Checking target database schema');
			$targetSchema = $toSchema->getWrappedSchema();
			$this->ensureUniqueNamesConstraints($targetSchema, true);
			if ($this->checkOracle) {
				$beforeSchema = $this->connection->createSchema();
				$this->ensureOracleConstraints($beforeSchema, $targetSchema, strlen($this->connection->getPrefix()));
			}

			$this->output->debug('- Migrate database schema');
			$this->connection->migrateToSchema($targetSchema);
			$toSchema->performDropTableCalls();
		}

		$this->output->debug('- Mark migrations as executed');
		foreach ($toBeExecuted as $version) {
			$this->markAsExecuted($version);
		}
	}

	/**
	 * Get the human readable descriptions for the migration steps to run
	 *
	 * @param string $to
	 * @return string[] [$name => $description]
	 */
	public function describeMigrationStep($to = 'latest') {
		$toBeExecuted = $this->getMigrationsToExecute($to);
		$description = [];
		foreach ($toBeExecuted as $version) {
			$migration = $this->createInstance($version);
			if ($migration->name()) {
				$description[$migration->name()] = $migration->description();
			}
		}
		return $description;
	}

	/**
	 * @param string $version
	 * @return IMigrationStep
	 * @throws \InvalidArgumentException
	 */
	public function createInstance($version) {
		$class = $this->getClass($version);
		try {
			$s = \OCP\Server::get($class);

			if (!$s instanceof IMigrationStep) {
				throw new \InvalidArgumentException('Not a valid migration');
			}
		} catch (QueryException $e) {
			if (class_exists($class)) {
				$s = new $class();
			} else {
				throw new \InvalidArgumentException("Migration step '$class' is unknown");
			}
		}

		return $s;
	}

	/**
	 * Executes one explicit version
	 *
	 * @param string $version
	 * @param bool $schemaOnly
	 * @throws \InvalidArgumentException
	 */
	public function executeStep($version, $schemaOnly = false) {
		$instance = $this->createInstance($version);

		if (!$schemaOnly) {
			$instance->preSchemaChange($this->output, function (): ISchemaWrapper {
				return new SchemaWrapper($this->connection);
			}, ['tablePrefix' => $this->connection->getPrefix()]);
		}

		$toSchema = $instance->changeSchema($this->output, function (): ISchemaWrapper {
			return new SchemaWrapper($this->connection);
		}, ['tablePrefix' => $this->connection->getPrefix()]);

		if ($toSchema instanceof SchemaWrapper) {
			$targetSchema = $toSchema->getWrappedSchema();
			$this->ensureUniqueNamesConstraints($targetSchema, $schemaOnly);
			if ($this->checkOracle) {
				$sourceSchema = $this->connection->createSchema();
				$this->ensureOracleConstraints($sourceSchema, $targetSchema, strlen($this->connection->getPrefix()));
			}
			$this->connection->migrateToSchema($targetSchema);
			$toSchema->performDropTableCalls();
		}

		if (!$schemaOnly) {
			$instance->postSchemaChange($this->output, function (): ISchemaWrapper {
				return new SchemaWrapper($this->connection);
			}, ['tablePrefix' => $this->connection->getPrefix()]);
		}

		$this->markAsExecuted($version);
	}

	/**
	 * Naming constraints:
	 * - Tables names must be 30 chars or shorter (27 + oc_ prefix)
	 * - Column names must be 30 chars or shorter
	 * - Index names must be 30 chars or shorter
	 * - Sequence names must be 30 chars or shorter
	 * - Primary key names must be set or the table name 23 chars or shorter
	 *
	 * Data constraints:
	 * - Tables need a primary key (Not specific to Oracle, but required for performant clustering support)
	 * - Columns with "NotNull" can not have empty string as default value
	 * - Columns with "NotNull" can not have number 0 as default value
	 * - Columns with type "bool" (which is in fact integer of length 1) can not be "NotNull" as it can not store 0/false
	 * - Columns with type "string" can not be longer than 4.000 characters, use "text" instead
	 *
	 * @see https://github.com/nextcloud/documentation/blob/master/developer_manual/basics/storage/database.rst
	 *
	 * @param Schema $sourceSchema
	 * @param Schema $targetSchema
	 * @param int $prefixLength
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function ensureOracleConstraints(Schema $sourceSchema, Schema $targetSchema, int $prefixLength) {
		$sequences = $targetSchema->getSequences();

		foreach ($targetSchema->getTables() as $table) {
			try {
				$sourceTable = $sourceSchema->getTable($table->getName());
			} catch (SchemaException $e) {
				if (\strlen($table->getName()) - $prefixLength > 27) {
					throw new \InvalidArgumentException('Table name "' . $table->getName() . '" is too long.');
				}
				$sourceTable = null;
			}

			foreach ($table->getColumns() as $thing) {
				// If the table doesn't exist OR if the column doesn't exist in the table
				if (!$sourceTable instanceof Table || !$sourceTable->hasColumn($thing->getName())) {
					if (\strlen($thing->getName()) > 30) {
						throw new \InvalidArgumentException('Column name "' . $table->getName() . '"."' . $thing->getName() . '" is too long.');
					}

					if ($thing->getNotnull() && $thing->getDefault() === ''
						&& $sourceTable instanceof Table && !$sourceTable->hasColumn($thing->getName())) {
						throw new \InvalidArgumentException('Column "' . $table->getName() . '"."' . $thing->getName() . '" is NotNull, but has empty string or null as default.');
					}

					if ($thing->getNotnull() && $thing->getType()->getName() === Types::BOOLEAN) {
						throw new \InvalidArgumentException('Column "' . $table->getName() . '"."' . $thing->getName() . '" is type Bool and also NotNull, so it can not store "false".');
					}

					$sourceColumn = null;
				} else {
					$sourceColumn = $sourceTable->getColumn($thing->getName());
				}

				// If the column was just created OR the length changed OR the type changed
				// we will NOT detect invalid length if the column is not modified
				if (($sourceColumn === null || $sourceColumn->getLength() !== $thing->getLength() || $sourceColumn->getType()->getName() !== Types::STRING)
					&& $thing->getLength() > 4000 && $thing->getType()->getName() === Types::STRING) {
					throw new \InvalidArgumentException('Column "' . $table->getName() . '"."' . $thing->getName() . '" is type String, but exceeding the 4.000 length limit.');
				}
			}

			foreach ($table->getIndexes() as $thing) {
				if ((!$sourceTable instanceof Table || !$sourceTable->hasIndex($thing->getName())) && \strlen($thing->getName()) > 30) {
					throw new \InvalidArgumentException('Index name "' . $table->getName() . '"."' . $thing->getName() . '" is too long.');
				}
			}

			foreach ($table->getForeignKeys() as $thing) {
				if ((!$sourceTable instanceof Table || !$sourceTable->hasForeignKey($thing->getName())) && \strlen($thing->getName()) > 30) {
					throw new \InvalidArgumentException('Foreign key name "' . $table->getName() . '"."' . $thing->getName() . '" is too long.');
				}
			}

			$primaryKey = $table->getPrimaryKey();
			if ($primaryKey instanceof Index && (!$sourceTable instanceof Table || !$sourceTable->hasPrimaryKey())) {
				$indexName = strtolower($primaryKey->getName());
				$isUsingDefaultName = $indexName === 'primary';

				if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_POSTGRES) {
					$defaultName = $table->getName() . '_pkey';
					$isUsingDefaultName = strtolower($defaultName) === $indexName;

					if ($isUsingDefaultName) {
						$sequenceName = $table->getName() . '_' . implode('_', $primaryKey->getColumns()) . '_seq';
						$sequences = array_filter($sequences, function (Sequence $sequence) use ($sequenceName) {
							return $sequence->getName() !== $sequenceName;
						});
					}
				} elseif ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) {
					$defaultName = $table->getName() . '_seq';
					$isUsingDefaultName = strtolower($defaultName) === $indexName;
				}

				if (!$isUsingDefaultName && \strlen($indexName) > 30) {
					throw new \InvalidArgumentException('Primary index name on "' . $table->getName() . '" is too long.');
				}
				if ($isUsingDefaultName && \strlen($table->getName()) - $prefixLength >= 23) {
					throw new \InvalidArgumentException('Primary index name on "' . $table->getName() . '" is too long.');
				}
			} elseif (!$primaryKey instanceof Index && !$sourceTable instanceof Table) {
				/** @var LoggerInterface $logger */
				$logger = \OC::$server->get(LoggerInterface::class);
				$logger->error('Table "' . $table->getName() . '" has no primary key and therefor will not behave sane in clustered setups. This will throw an exception and not be installable in a future version of Nextcloud.');
				// throw new \InvalidArgumentException('Table "' . $table->getName() . '" has no primary key and therefor will not behave sane in clustered setups.');
			}
		}

		foreach ($sequences as $sequence) {
			if (!$sourceSchema->hasSequence($sequence->getName()) && \strlen($sequence->getName()) > 30) {
				throw new \InvalidArgumentException('Sequence name "' . $sequence->getName() . '" is too long.');
			}
		}
	}

	/**
	 * Ensure naming constraints
	 *
	 * Naming constraints:
	 * - Index, sequence and primary key names must be unique within a Postgres Schema
	 *
	 * Only on installation we want to break hard, so that all developers notice
	 * the bugs when installing the app on any database or CI, and can work on
	 * fixing their migrations before releasing a version incompatible with Postgres.
	 *
	 * In case of updates we might be running on production instances and the
	 * administrators being faced with the error would not know how to resolve it
	 * anyway. This can also happen with instances, that had the issue before the
	 * current update, so we don't want to make their life more complicated
	 * than needed.
	 *
	 * @param Schema $targetSchema
	 * @param bool $isInstalling
	 */
	public function ensureUniqueNamesConstraints(Schema $targetSchema, bool $isInstalling): void {
		$constraintNames = [];
		$sequences = $targetSchema->getSequences();

		foreach ($targetSchema->getTables() as $table) {
			foreach ($table->getIndexes() as $thing) {
				$indexName = strtolower($thing->getName());
				if ($indexName === 'primary' || $thing->isPrimary()) {
					continue;
				}

				if (isset($constraintNames[$thing->getName()])) {
					if ($isInstalling) {
						throw new \InvalidArgumentException('Index name "' . $thing->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
					}
					$this->logErrorOrWarning('Index name "' . $thing->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
				}
				$constraintNames[$thing->getName()] = $table->getName();
			}

			foreach ($table->getForeignKeys() as $thing) {
				if (isset($constraintNames[$thing->getName()])) {
					if ($isInstalling) {
						throw new \InvalidArgumentException('Foreign key name "' . $thing->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
					}
					$this->logErrorOrWarning('Foreign key name "' . $thing->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
				}
				$constraintNames[$thing->getName()] = $table->getName();
			}

			$primaryKey = $table->getPrimaryKey();
			if ($primaryKey instanceof Index) {
				$indexName = strtolower($primaryKey->getName());
				if ($indexName === 'primary') {
					continue;
				}

				if (isset($constraintNames[$indexName])) {
					if ($isInstalling) {
						throw new \InvalidArgumentException('Primary index name "' . $indexName . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
					}
					$this->logErrorOrWarning('Primary index name "' . $indexName . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
				}
				$constraintNames[$indexName] = $table->getName();
			}
		}

		foreach ($sequences as $sequence) {
			if (isset($constraintNames[$sequence->getName()])) {
				if ($isInstalling) {
					throw new \InvalidArgumentException('Sequence name "' . $sequence->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
				}
				$this->logErrorOrWarning('Sequence name "' . $sequence->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
			}
			$constraintNames[$sequence->getName()] = 'sequence';
		}
	}

	protected function logErrorOrWarning(string $log): void {
		if ($this->output instanceof SimpleOutput) {
			$this->output->warning($log);
		} else {
			$this->logger->error($log);
		}
	}

	private function ensureMigrationsAreLoaded() {
		if (empty($this->migrations)) {
			$this->migrations = $this->findMigrations();
		}
	}
}
