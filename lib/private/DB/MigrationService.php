<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2017, ownCloud GmbH
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\DB;

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use OC\App\InfoParser;
use OC\IntegrityCheck\Helpers\AppLocator;
use OC\Migration\SimpleOutput;
use OCP\AppFramework\App;
use OCP\AppFramework\QueryException;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IMigrationStep;
use OCP\Migration\IOutput;
use Psr\Log\LoggerInterface;

class MigrationService {
	private bool $migrationTableCreated;
	private array $migrations;
	private string $migrationsPath;
	private string $migrationsNamespace;
	private IOutput $output;
	private Connection $connection;
	private string $appName;
	private bool $checkOracle;

	/**
	 * @throws \Exception
	 */
	public function __construct(string $appName, Connection $connection, ?IOutput $output = null, ?AppLocator $appLocator = null) {
		$this->appName = $appName;
		$this->connection = $connection;
		if ($output === null) {
			$this->output = new SimpleOutput(\OC::$server->get(LoggerInterface::class), $appName);
		} else {
			$this->output = $output;
		}

		if ($appName === 'core') {
			$this->migrationsPath = \OC::$SERVERROOT . '/core/Migrations';
			$this->migrationsNamespace = 'OC\\Core\\Migrations';
			$this->checkOracle = true;
		} else {
			if (null === $appLocator) {
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
	 * @return string[]
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

		return $rows;
	}

	/**
	 * Returns all versions which are available in the migration folder
	 * @return list<string>
	 */
	public function getAvailableVersions(): array {
		$this->ensureMigrationsAreLoaded();
		return array_map('strval', array_keys($this->migrations));
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
		uasort($files, function ($a, $b) {
			preg_match('/^Version(\d+)Date(\d+)\\.php$/', basename($a), $matchA);
			preg_match('/^Version(\d+)Date(\d+)\\.php$/', basename($b), $matchB);
			if (!empty($matchA) && !empty($matchB)) {
				if ($matchA[1] !== $matchB[1]) {
					return ($matchA[1] < $matchB[1]) ? -1 : 1;
				}
				return ($matchA[2] < $matchB[2]) ? -1 : 1;
			}
			return (basename($a) < basename($b)) ? -1 : 1;
		});

		$migrations = [];

		foreach ($files as $file) {
			$className = basename($file, '.php');
			$version = (string) substr($className, 7);
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
			if ($to !== 'latest' && $v > $to) {
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
				throw new \Exception('Database error when running migration ' . $version . ' for app ' . $this->getApp() . PHP_EOL. $e->getMessage(), 0, $e);
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
			$this->ensureUniqueNamesConstraints($targetSchema);
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
	protected function createInstance($version) {
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
			$this->ensureUniqueNamesConstraints($targetSchema);
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

				if ($this->connection->getDatabasePlatform() instanceof PostgreSQL94Platform) {
					$defaultName = $table->getName() . '_pkey';
					$isUsingDefaultName = strtolower($defaultName) === $indexName;

					if ($isUsingDefaultName) {
						$sequenceName = $table->getName() . '_' . implode('_', $primaryKey->getColumns()) . '_seq';
						$sequences = array_filter($sequences, function (Sequence $sequence) use ($sequenceName) {
							return $sequence->getName() !== $sequenceName;
						});
					}
				} elseif ($this->connection->getDatabasePlatform() instanceof OraclePlatform) {
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
	 * Naming constraints:
	 * - Index, sequence and primary key names must be unique within a Postgres Schema
	 *
	 * @param Schema $targetSchema
	 */
	public function ensureUniqueNamesConstraints(Schema $targetSchema): void {
		$constraintNames = [];

		$sequences = $targetSchema->getSequences();

		foreach ($targetSchema->getTables() as $table) {
			foreach ($table->getIndexes() as $thing) {
				$indexName = strtolower($thing->getName());
				if ($indexName === 'primary' || $thing->isPrimary()) {
					continue;
				}

				if (isset($constraintNames[$thing->getName()])) {
					throw new \InvalidArgumentException('Index name "' . $thing->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
				}
				$constraintNames[$thing->getName()] = $table->getName();
			}

			foreach ($table->getForeignKeys() as $thing) {
				if (isset($constraintNames[$thing->getName()])) {
					throw new \InvalidArgumentException('Foreign key name "' . $thing->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
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
					throw new \InvalidArgumentException('Primary index name "' . $indexName . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
				}
				$constraintNames[$indexName] = $table->getName();
			}
		}

		foreach ($sequences as $sequence) {
			if (isset($constraintNames[$sequence->getName()])) {
				throw new \InvalidArgumentException('Sequence name "' . $sequence->getName() . '" for table "' . $table->getName() . '" collides with the constraint on table "' . $constraintNames[$thing->getName()] . '".');
			}
			$constraintNames[$sequence->getName()] = 'sequence';
		}
	}

	private function ensureMigrationsAreLoaded() {
		if (empty($this->migrations)) {
			$this->migrations = $this->findMigrations();
		}
	}
}
