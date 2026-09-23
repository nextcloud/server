<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB;

use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Types;
use OC\Migration\NullOutput;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;

/**
 * Compares the live database schema against the schema expected for the
 * currently installed version, by replaying every app's migrations into an
 * in-memory schema and diffing it against the live one.
 */
class SchemaChecker {
	public function __construct(
		private readonly Connection $connection,
		private readonly IAppConfig $appConfig,
		private readonly IAppManager $appManager,
		private readonly IEventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * @return list<array{table: string, type: string, name?: string, changes?: list<string>, app: ?string, enabled: bool, optionalIndex: bool}>
	 */
	public function getFindings(?string $onlyTable = null): array {
		$expectedSchema = new Schema();
		$enabledApps = array_flip($this->appManager->getEnabledApps());

		$this->applyMigrations('core', $expectedSchema);

		// Enabled apps are already autoloaded at boot, no extra class loading needed.
		foreach (array_keys($enabledApps) as $app) {
			$this->applyMigrations($app, $expectedSchema);
		}

		// Disabled apps keep their tables, so replay their migrations too.
		$disabledApps = array_diff(array_keys($this->appConfig->getAppInstalledVersions()), array_keys($enabledApps));
		// Table name => owning disabled app, so its findings can be marked non-blocking below.
		$disabledAppTableOwners = [];
		foreach ($disabledApps as $app) {
			$this->applyDisabledMigrations($app, $expectedSchema, $disabledAppTableOwners);
		}

		$this->addMigrationsTable($expectedSchema);
		$this->materializeUniqueConstraints($expectedSchema);

		$liveSchema = $this->connection->createSchema();

		if ($onlyTable !== null) {
			$this->keepOnlyTable($expectedSchema, $onlyTable);
			$this->keepOnlyTable($liveSchema, $onlyTable);
		}

		$comparator = $this->connection->createSchemaManager()->createComparator();
		$diff = $comparator->compareSchemas($liveSchema, $expectedSchema);
		$optionalIndexNames = $this->getOptionalIndexNames();

		return array_map(function (array $finding) use ($disabledAppTableOwners, $enabledApps, $optionalIndexNames): array {
			$app = $disabledAppTableOwners[$finding['table']] ?? null;
			$finding['app'] = $app;
			// Only tables owned by a disabled app are non-blocking.
			$finding['enabled'] = $app === null || $app === 'core' || isset($enabledApps[$app]);
			$finding['optionalIndex'] = ($finding['type'] === 'missing_index' || $finding['type'] === 'unexpected_index')
				&& isset($optionalIndexNames[$finding['table']][$finding['name']]);
			return $finding;
		}, $this->buildFindings($diff));
	}

	/**
	 * @param array{table: string, type: string, name?: string, changes?: list<string>, app?: ?string, enabled?: bool, optionalIndex?: bool} $finding
	 */
	public function formatFinding(array $finding): string {
		return match ($finding['type']) {
			'missing_table' => "missing table '{$finding['table']}'",
			'unexpected_table' => "unexpected table '{$finding['table']}'",
			'missing_column' => "{$finding['table']}: missing column '{$finding['name']}'",
			'unexpected_column' => "{$finding['table']}: unexpected column '{$finding['name']}'",
			'modified_column' => "{$finding['table']}: column '{$finding['name']}' differs in: " . implode(', ', $finding['changes']),
			'missing_index' => "{$finding['table']}: missing index '{$finding['name']}'",
			'unexpected_index' => "{$finding['table']}: unexpected index '{$finding['name']}'",
			default => "{$finding['table']}: unknown finding '{$finding['type']}'",
		};
	}

	/**
	 * Splits findings into blocking ones (from core or an enabled app),
	 * non-blocking ones grouped by the disabled app that owns them, and
	 * non-blocking optional-index findings (only relevant if occ
	 * db:add-missing-indices was never run for that table).
	 *
	 * @param list<array{table: string, type: string, name?: string, changes?: list<string>, app: ?string, enabled: bool, optionalIndex: bool}> $findings
	 * @return array{blocking: list<array{table: string, type: string, name?: string, changes?: list<string>, app: ?string, enabled: bool, optionalIndex: bool}>, byDisabledApp: array<string, list<array{table: string, type: string, name?: string, changes?: list<string>, app: ?string, enabled: bool, optionalIndex: bool}>>, optionalIndices: list<array{table: string, type: string, name?: string, changes?: list<string>, app: ?string, enabled: bool, optionalIndex: bool}>}
	 */
	public function partitionFindings(array $findings): array {
		$blocking = [];
		$byDisabledApp = [];
		$optionalIndices = [];
		foreach ($findings as $finding) {
			if ($finding['optionalIndex']) {
				$optionalIndices[] = $finding;
			} elseif ($finding['enabled']) {
				$blocking[] = $finding;
			} else {
				$byDisabledApp[$finding['app']][] = $finding;
			}
		}
		return ['blocking' => $blocking, 'byDisabledApp' => $byDisabledApp, 'optionalIndices' => $optionalIndices];
	}

	private function applyMigrations(string $app, Schema $schema): void {
		$output = new NullOutput();
		$ms = new MigrationService($app, $this->connection, $output);
		foreach ($ms->getAvailableVersions() as $version) {
			$migration = $ms->createInstance($version);
			$migration->changeSchema($output, function () use (&$schema) {
				return new SchemaWrapper($this->connection, $schema);
			}, []);
		}
	}

	/**
	 * @param array<string, string> $disabledAppTableOwners table name => owning app id, updated in place
	 */
	private function applyDisabledMigrations(string $app, Schema $schema, array &$disabledAppTableOwners): void {
		try {
			$appPath = $this->appManager->getAppPath($app);
		} catch (AppPathNotFoundException) {
			// Installed, but code is gone: no migrations to replay.
			return;
		}

		$existingTables = [];
		foreach ($schema->getTables() as $table) {
			$existingTables[$table->getName()] = true;
		}

		try {
			// Disabled apps are not autoloaded on boot. Load only the migration
			// classes themselves directly from disk, rather than registering
			// the whole app for PSR-4 autoloading.
			foreach ($this->findMigrationFiles($appPath . '/lib/Migration') as $file) {
				require_once $file;
			}

			$this->applyMigrations($app, $schema);
		} catch (\Throwable) {
			return;
		}

		foreach ($schema->getTables() as $table) {
			if (!isset($existingTables[$table->getName()])) {
				$disabledAppTableOwners[$table->getName()] = $app;
			}
		}
	}

	/**
	 * Copied from MigrationService::findMigrations(), minus the class-name mapping.
	 *
	 * @return list<string>
	 */
	private function findMigrationFiles(string $directory): array {
		$directory = realpath($directory);
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

		return array_keys(iterator_to_array($iterator));
	}

	/**
	 * The migrations bookkeeping table is created directly by MigrationService
	 * outside of any app's changeSchema(), so replaying migrations never
	 * produces it. Add it explicitly so it isn't reported as missing.
	 */
	private function addMigrationsTable(Schema $schema): void {
		$tableName = $this->connection->getPrefix() . 'migrations';
		if ($schema->hasTable($tableName)) {
			return;
		}

		$table = $schema->createTable($tableName);
		$table->addColumn('app', Types::STRING, ['length' => 255]);
		$table->addColumn('version', Types::STRING, ['length' => 255]);
		$table->setPrimaryKey(['app', 'version']);
	}

	/**
	 * Doctrine's schema comparator only diffs indexes, not unique constraints.
	 * Live introspection reports a MySQL UNIQUE KEY as an Index, while a
	 * migration's addUniqueConstraint() stores it separately on the Table.
	 * Materialize each unique constraint as its equivalent index so the
	 * comparison lines up with what the live schema actually reports.
	 */
	private function materializeUniqueConstraints(Schema $schema): void {
		foreach ($schema->getTables() as $table) {
			foreach ($table->getUniqueConstraints() as $constraint) {
				if (!$table->hasIndex($constraint->getName())) {
					$table->addUniqueIndex($constraint->getColumns(), $constraint->getName());
				}
			}
		}
	}

	/**
	 * Apps can register indices that are only ever created or renamed via
	 * occ db:add-missing-indices (AddMissingIndicesEvent), not through a
	 * versioned migration. Since running that command is optional, whether
	 * such an index exists on the live schema depends on whether an admin
	 * ever ran it - it is not itself a sign of drift in either direction.
	 * Collect their names here so findings about them can be reported
	 * separately instead of as blocking missing/unexpected index findings.
	 *
	 * @return array<string, array<string, true>> table name => set of index names
	 */
	private function getOptionalIndexNames(): array {
		$event = new AddMissingIndicesEvent();
		$this->eventDispatcher->dispatchTyped($event);

		$names = [];
		foreach ($event->getMissingIndices() as $missingIndex) {
			$table = $this->connection->getPrefix() . $missingIndex['tableName'];
			$names[$table][$missingIndex['indexName']] = true;
		}
		foreach ($event->getIndicesToReplace() as $toReplace) {
			$table = $this->connection->getPrefix() . $toReplace['tableName'];
			$names[$table][$toReplace['newIndexName']] = true;
			foreach ($toReplace['oldIndexNames'] as $oldIndexName) {
				$names[$table][$oldIndexName] = true;
			}
		}

		return $names;
	}

	private function keepOnlyTable(Schema $schema, string $tableName): void {
		foreach ($schema->getTables() as $table) {
			if ($table->getName() !== $tableName) {
				$schema->dropTable($table->getName());
			}
		}
	}

	/**
	 * @return list<array{table: string, type: string, name?: string, changes?: list<string>}>
	 */
	private function buildFindings(SchemaDiff $diff): array {
		$findings = [];

		foreach ($diff->getCreatedTables() as $table) {
			$findings[] = ['table' => $table->getName(), 'type' => 'missing_table'];
		}

		foreach ($diff->getDroppedTables() as $table) {
			$findings[] = ['table' => $table->getName(), 'type' => 'unexpected_table'];
		}

		foreach ($diff->getAlteredTables() as $tableDiff) {
			array_push($findings, ...$this->buildTableFindings($tableDiff));
		}

		return $findings;
	}

	/**
	 * @return list<array{table: string, type: string, name?: string, changes?: list<string>}>
	 */
	private function buildTableFindings(TableDiff $tableDiff): array {
		$tableName = $tableDiff->getOldTable()?->getName() ?? '?';
		$findings = [];

		foreach ($tableDiff->getAddedColumns() as $column) {
			$findings[] = ['table' => $tableName, 'type' => 'missing_column', 'name' => $column->getName()];
		}

		foreach ($tableDiff->getDroppedColumns() as $column) {
			$findings[] = ['table' => $tableName, 'type' => 'unexpected_column', 'name' => $column->getName()];
		}

		foreach ($tableDiff->getModifiedColumns() as $columnDiff) {
			$columnName = $columnDiff->getOldColumn()?->getName() ?? $columnDiff->getNewColumn()->getName();
			$changes = $this->getChangedColumnProperties($columnDiff);

			if ($changes === []) {
				continue;
			}

			$findings[] = ['table' => $tableName, 'type' => 'modified_column', 'name' => $columnName, 'changes' => $changes];
		}

		foreach ($tableDiff->getAddedIndexes() as $index) {
			$findings[] = ['table' => $tableName, 'type' => 'missing_index', 'name' => $index->getName()];
		}

		foreach ($tableDiff->getDroppedIndexes() as $index) {
			$findings[] = ['table' => $tableName, 'type' => 'unexpected_index', 'name' => $index->getName()];
		}

		return $findings;
	}

	/**
	 * @return list<string>
	 */
	private function getChangedColumnProperties(ColumnDiff $columnDiff): array {
		$changes = [];

		if ($columnDiff->hasTypeChanged()) {
			$changes[] = 'type';
		}
		if ($columnDiff->hasLengthChanged()) {
			$changes[] = 'length';
		}
		if ($columnDiff->hasPrecisionChanged()) {
			$changes[] = 'precision';
		}
		if ($columnDiff->hasScaleChanged()) {
			$changes[] = 'scale';
		}
		if ($columnDiff->hasNotNullChanged()) {
			$changes[] = 'nullable';
		}
		if ($columnDiff->hasDefaultChanged()) {
			$changes[] = 'default';
		}
		if ($columnDiff->hasAutoIncrementChanged()) {
			$changes[] = 'autoincrement';
		}
		if ($columnDiff->hasUnsignedChanged()) {
			$changes[] = 'unsigned';
		}
		if ($columnDiff->hasFixedChanged()) {
			$changes[] = 'fixed';
		}
		if ($columnDiff->hasCommentChanged()) {
			$changes[] = 'comment';
		}

		return $changes;
	}
}
