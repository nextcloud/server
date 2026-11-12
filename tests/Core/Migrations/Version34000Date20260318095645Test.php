<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Migrations;

use OC\DB\Connection;
use OC\DB\MigrationService;
use OC\DB\SchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Snowflake\ISnowflakeGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

/**
 * Runs the real migration through MigrationService against oc_jobs in each
 * state an upgrading instance can be in, and restores oc_jobs afterwards.
 */
#[Group(name: 'DB')]
class Version34000Date20260318095645Test extends TestCase {
	private const VERSION = '34000Date20260318095645';
	private const PROBE_CLASS = 'Tests\\Core\\Migrations\\ArgumentProbe';

	private Connection $connection;
	private string $table;
	private bool $installedNullable = false;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(Connection::class);
		$this->table = '"' . $this->connection->getPrefix() . 'jobs"';
		if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) {
			$this->installedNullable = $this->getOracleColumns()['argument']['nullable'];
		}
	}

	#[\Override]
	protected function tearDown(): void {
		if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) {
			$this->restoreInstalledState();
		}
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('jobs')
			->where($qb->expr()->eq('class', $qb->createNamedParameter(self::PROBE_CLASS)))
			->executeStatement();

		parent::tearDown();
	}

	/**
	 * Fresh installs and app installs apply all steps as one schema diff,
	 * without preSchemaChange, so the installed test instance must end up
	 * with a text column and no leftover copy.
	 */
	public function testInstalledSchemaHasTextColumn(): void {
		$table = (new SchemaWrapper($this->connection))->getTable('jobs');

		$this->assertSame(Types::TEXT, $table->getColumn('argument')->getType()->getName());
		$this->assertFalse($table->hasColumn('argument_copy'));
	}

	public function testMigrationKeepsConvertedColumn(): void {
		$values = $this->insertProbeRows();

		$this->executeMigration();

		$table = (new SchemaWrapper($this->connection))->getTable('jobs');
		$this->assertSame(Types::TEXT, $table->getColumn('argument')->getType()->getName());
		$this->assertFalse($table->hasColumn('argument_copy'));
		$this->assertSame($values, $this->getProbeArguments());
	}

	public static function oracleStateProvider(): array {
		return [
			'never upgraded' => ['setUpNeverUpgraded', false],
			'converted by hand' => ['setUpConvertedByHand', false],
			'converted by hand, nullable' => ['setUpConvertedByHandNullable', true],
			'interrupted after adding the copy' => ['setUpInterruptedAfterAdd', false],
			'interrupted before dropping the original' => ['setUpInterruptedBeforeDrop', false],
			'interrupted before renaming the copy' => ['setUpInterruptedBeforeRename', false],
		];
	}

	#[DataProvider('oracleStateProvider')]
	public function testMigrationConvertsOracleColumn(string $setUpState, bool $expectedNullable): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_ORACLE) {
			$this->markTestSkipped('The out-of-place conversion only runs on Oracle');
		}

		$values = $this->insertProbeRows();
		$this->$setUpState();

		$this->executeMigration();

		$columns = $this->getOracleColumns();
		$this->assertSame('CLOB', $columns['argument']['type']);
		$this->assertSame($expectedNullable, $columns['argument']['nullable']);
		$this->assertArrayNotHasKey('argument_copy', $columns);
		$this->assertSame(Types::TEXT, (new SchemaWrapper($this->connection))->getTable('jobs')
			->getColumn('argument')->getType()->getName());
		$this->assertSame($values, $this->getProbeArguments());

		$this->insertProbeRow('{"after":"migration"}');
		$this->assertSame([...$values, '{"after":"migration"}'], $this->getProbeArguments());
	}

	private function setUpNeverUpgraded(): void {
		$tooLong = (int)$this->connection->executeQuery('SELECT COUNT(*) FROM ' . $this->table . ' WHERE DBMS_LOB.GETLENGTH("argument") > 4000')->fetchOne();
		if ($tooLong > 0) {
			$this->markTestSkipped("$tooLong rows in oc_jobs do not fit the pre-migration VARCHAR2(4000) column");
		}
		$this->replaceColumn('argument', 'VARCHAR2(4000)', 'DBMS_LOB.SUBSTR("argument", 4000, 1)');
		$this->alter('MODIFY ("argument" DEFAULT \'\' NOT NULL)');
	}

	private function setUpConvertedByHand(): void {
		$this->setArgumentNullable(false);
	}

	private function setUpConvertedByHandNullable(): void {
		$this->setArgumentNullable(true);
	}

	private function setUpInterruptedAfterAdd(): void {
		$this->setUpNeverUpgraded();
		$this->alter('ADD ("argument_copy" CLOB)');
	}

	private function setUpInterruptedBeforeDrop(): void {
		$this->setUpInterruptedAfterAdd();
		$this->connection->executeStatement('UPDATE ' . $this->table . ' SET "argument_copy" = "argument"');
		$this->alter('MODIFY ("argument_copy" NOT NULL)');
	}

	private function setUpInterruptedBeforeRename(): void {
		$this->setUpInterruptedBeforeDrop();
		$this->alter('DROP COLUMN "argument"');
	}

	/**
	 * Puts oc_jobs.argument back to the installed CLOB, whatever state a
	 * failed test left it in, so the rest of the DB group is unaffected.
	 */
	private function restoreInstalledState(): void {
		$columns = $this->getOracleColumns();
		if (isset($columns['argument_copy'])) {
			if (isset($columns['argument'])) {
				$this->alter('DROP COLUMN "argument_copy"');
			} else {
				$this->alter('RENAME COLUMN "argument_copy" TO "argument"');
			}
			$columns = $this->getOracleColumns();
		}
		if ($columns['argument']['type'] !== 'CLOB') {
			$this->replaceColumn('argument', 'CLOB', '"argument"');
		}
		$this->setArgumentNullable($this->installedNullable);
	}

	private function setArgumentNullable(bool $nullable): void {
		if ($this->getOracleColumns()['argument']['nullable'] !== $nullable) {
			$this->alter('MODIFY ("argument" ' . ($nullable ? 'NULL' : 'NOT NULL') . ')');
		}
	}

	private function replaceColumn(string $column, string $type, string $copyExpression): void {
		$this->alter('ADD ("replacement" ' . $type . ')');
		$this->connection->executeStatement('UPDATE ' . $this->table . ' SET "replacement" = ' . $copyExpression);
		$this->alter('DROP COLUMN "' . $column . '"');
		$this->alter('RENAME COLUMN "replacement" TO "' . $column . '"');
	}

	private function alter(string $clause): void {
		$this->connection->executeStatement('ALTER TABLE ' . $this->table . ' ' . $clause);
	}

	private function executeMigration(): void {
		(new MigrationService('core', $this->connection))->executeStep(self::VERSION);

		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('version')
			->from('migrations')
			->where($qb->expr()->eq('app', $qb->createNamedParameter('core')))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(self::VERSION)))
			->executeQuery();
		$this->assertSame([self::VERSION], $result->fetchFirstColumn());
		$result->closeCursor();
	}

	/**
	 * @return list<string>
	 */
	private function insertProbeRows(): array {
		$values = [
			'{"foo":"bar"}',
			json_encode(['data' => str_repeat('x', 3900)]),
			json_encode(['unicode' => 'üñïçødé 🥘 "quoted" <tag> \\']),
		];
		foreach ($values as $value) {
			$this->insertProbeRow($value);
		}
		return $values;
	}

	private function insertProbeRow(string $argument): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('jobs')
			->values([
				'id' => $qb->createNamedParameter(Server::get(ISnowflakeGenerator::class)->nextId()),
				'class' => $qb->createNamedParameter(self::PROBE_CLASS),
				'argument' => $qb->createNamedParameter($argument),
				'argument_hash' => $qb->createNamedParameter(hash('sha256', $argument)),
			])
			->executeStatement();
	}

	/**
	 * @return list<string>
	 */
	private function getProbeArguments(): array {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('argument')
			->from('jobs')
			->where($qb->expr()->eq('class', $qb->createNamedParameter(self::PROBE_CLASS)))
			->orderBy('id')
			->executeQuery();
		$arguments = $result->fetchFirstColumn();
		$result->closeCursor();
		return $arguments;
	}

	/**
	 * @return array<string, array{type: string, nullable: bool}>
	 */
	private function getOracleColumns(): array {
		$rows = $this->connection->executeQuery(
			'SELECT column_name AS "name", data_type AS "type", nullable AS "nullable" FROM all_tab_columns'
			. ' WHERE owner = SYS_CONTEXT(\'USERENV\', \'CURRENT_SCHEMA\') AND table_name = ?',
			[$this->connection->getPrefix() . 'jobs'],
		)->fetchAll();

		$columns = [];
		foreach ($rows as $row) {
			$columns[$row['name']] = ['type' => $row['type'], 'nullable' => $row['nullable'] === 'Y'];
		}
		return $columns;
	}
}
