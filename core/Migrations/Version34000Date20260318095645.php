<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;
use RuntimeException;

#[ModifyColumn(table: 'jobs', name: 'argument', type: ColumnType::TEXT, description: 'Migrate background job arguments to a text column')]
class Version34000Date20260318095645 extends SimpleMigrationStep {
	private const COPY_COLUMN = 'argument_copy';

	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	/**
	 * Oracle cannot alter VARCHAR2 to CLOB in place (ORA-22858, even on an
	 * empty table), and Doctrine always restates the type in a MODIFY, so the
	 * conversion is done out of place here. changeSchema then sees TEXT and
	 * no-ops. Each step is driven by the columns found, so an interrupted run
	 * resumes where it stopped.
	 */
	#[Override]
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_ORACLE) {
			return;
		}

		$tableName = $options['tablePrefix'] . 'jobs';
		$columns = $this->getOracleColumns($tableName);
		$argument = $columns['argument'] ?? null;
		$copy = $columns[self::COPY_COLUMN] ?? null;

		if ($copy === null && ($argument === null || $argument['type'] !== 'VARCHAR2')) {
			return;
		}
		if ($copy !== null && ($copy['type'] !== 'CLOB' || ($argument !== null && $argument['type'] !== 'VARCHAR2'))) {
			throw new RuntimeException("Unexpected state of $tableName: column " . self::COPY_COLUMN . ' exists alongside argument, but not as a CLOB copy of a VARCHAR2 argument. Resolve it manually, then rerun the upgrade.');
		}

		$output->info("Converting $tableName.argument to CLOB");
		$table = '"' . $tableName . '"';

		if ($argument !== null) {
			if ($copy === null) {
				$this->connection->executeStatement('ALTER TABLE ' . $table . ' ADD ("argument_copy" CLOB)');
				$copy = ['type' => 'CLOB', 'nullable' => true];
			}

			$this->connection->executeStatement('UPDATE ' . $table . ' SET "argument_copy" = "argument"');
			$mismatches = (int)$this->connection->executeQuery(
				'SELECT COUNT(*) FROM ' . $table
				. ' WHERE ("argument" IS NULL AND "argument_copy" IS NOT NULL)'
				. ' OR ("argument" IS NOT NULL AND "argument_copy" IS NULL)'
				. ' OR DBMS_LOB.COMPARE("argument_copy", TO_CLOB("argument")) <> 0'
			)->fetchOne();
			if ($mismatches !== 0) {
				throw new RuntimeException("$mismatches rows of $tableName were not copied to " . self::COPY_COLUMN . '. The original column is untouched; rerun the upgrade.');
			}

			// Tightening the copy before the drop makes a job written to the
			// old column only fail instead of losing its argument.
			if (!$argument['nullable'] && $copy['nullable']) {
				$this->connection->executeStatement('ALTER TABLE ' . $table . ' MODIFY ("argument_copy" NOT NULL)');
			}

			$this->connection->executeStatement('ALTER TABLE ' . $table . ' DROP COLUMN "argument"');
		}

		$this->connection->executeStatement('ALTER TABLE ' . $table . ' RENAME COLUMN "argument_copy" TO "argument"');
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('jobs')) {
			$table = $schema->getTable('jobs');
			$argumentColumn = $table->getColumn('argument');

			if ($argumentColumn->getType()->getName() !== Types::TEXT) {
				$argumentColumn->setType(Types::TEXT);
				return $schema;
			}
		}

		return null;
	}

	/**
	 * @return array<string, array{type: string, nullable: bool}>
	 */
	private function getOracleColumns(string $tableName): array {
		$result = $this->connection->executeQuery(
			'SELECT column_name AS "name", data_type AS "type", nullable AS "nullable" FROM all_tab_columns'
			. " WHERE owner = SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AND table_name = ?",
			[$tableName],
		);

		$columns = [];
		foreach ($result->fetchAll() as $row) {
			$columns[$row['name']] = ['type' => $row['type'], 'nullable' => $row['nullable'] === 'Y'];
		}
		$result->closeCursor();

		return $columns;
	}
}
