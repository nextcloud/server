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
use OCP\App\IAppManager;

/**
 * Compares the live database schema against the schema expected for the
 * currently installed version, by replaying every app's migrations into an
 * in-memory schema and diffing it against the live one.
 */
class SchemaChecker {
	public function __construct(
		private readonly Connection $connection,
		private readonly IAppManager $appManager,
	) {
	}

	/**
	 * @return list<array{table: string, type: string, name?: string, changes?: list<string>}>
	 */
	public function getFindings(?string $onlyTable = null): array {
		$expectedSchema = new Schema();
		$this->applyMigrations('core', $expectedSchema);
		foreach ($this->appManager->getEnabledApps() as $app) {
			$this->applyMigrations($app, $expectedSchema);
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

		return $this->buildFindings($diff);
	}

	/**
	 * @param array{table: string, type: string, name?: string, changes?: list<string>} $finding
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
