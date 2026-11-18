<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Types\Type;
use OC\DB\Migrator;
use OC\DB\OracleMigrator;
use OC\DB\SchemaWrapper;
use OC\DB\SQLiteMigrator;
use OCP\DB\Types;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;

/**
 * Class MigratorTest
 *
 *
 * @package Test\DB
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class MigratorTest extends \Test\TestCase {
	/**
	 * @var \Doctrine\DBAL\Connection $connection
	 */
	private $connection;

	/**
	 * @var IConfig
	 **/
	private $config;

	/** @var string */
	private $tableName;

	/** @var string */
	private $tableNameTmp;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = Server::get(IConfig::class);
		$this->connection = Server::get(\OC\DB\Connection::class);

		$this->tableName = $this->getUniqueTableName();
		$this->tableNameTmp = $this->getUniqueTableName();
	}

	private function getMigrator(): Migrator {
		$dispatcher = Server::get(IEventDispatcher::class);
		if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_SQLITE) {
			return new SQLiteMigrator($this->connection, $this->config, $dispatcher);
		} elseif ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) {
			return new OracleMigrator($this->connection, $this->config, $dispatcher);
		}
		return new Migrator($this->connection, $this->config, $dispatcher);
	}

	private function getUniqueTableName() {
		return strtolower($this->getUniqueID($this->config->getSystemValueString('dbtableprefix', 'oc_') . 'test_'));
	}

	#[\Override]
	protected function tearDown(): void {
		// Try to delete if exists (IF EXISTS NOT SUPPORTED IN ORACLE)
		try {
			$this->connection->exec('DROP TABLE ' . $this->connection->quoteIdentifier($this->tableNameTmp));
		} catch (Exception $e) {
		}

		try {
			$this->connection->exec('DROP TABLE ' . $this->connection->quoteIdentifier($this->tableName));
		} catch (Exception $e) {
		}
		parent::tearDown();
	}

	/**
	 * @return \Doctrine\DBAL\Schema\Schema[]
	 */
	private function getDuplicateKeySchemas() {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');
		$table->addIndex(['id'], $this->tableName . '_id');

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');
		$table->addUniqueIndex(['id'], $this->tableName . '_id');

		return [$startSchema, $endSchema];
	}

	/**
	 * @return \Doctrine\DBAL\Schema\Schema[]
	 */
	private function getChangedTypeSchema($from, $to) {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', $from);
		$table->addColumn('name', 'string');
		$table->addIndex(['id'], $this->tableName . '_id');

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', $to);
		$table->addColumn('name', 'string');
		$table->addIndex(['id'], $this->tableName . '_id');

		return [$startSchema, $endSchema];
	}

	private function getSchemaConfig() {
		$config = new SchemaConfig();
		$config->setName($this->connection->getDatabase());
		return $config;
	}

	public function testUpgrade(): void {
		[$startSchema, $endSchema] = $this->getDuplicateKeySchemas();
		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, ['id' => 1, 'name' => 'foo']);
		$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'bar']);
		$this->connection->insert($this->tableName, ['id' => 3, 'name' => 'qwerty']);

		$migrator->migrate($endSchema);
		$this->addToAssertionCount(1);
	}

	public function testUpgradeDifferentPrefix(): void {
		$oldTablePrefix = $this->config->getSystemValueString('dbtableprefix', 'oc_');

		$this->config->setSystemValue('dbtableprefix', 'ownc_');
		$this->tableName = strtolower($this->getUniqueID($this->config->getSystemValueString('dbtableprefix') . 'test_'));

		[$startSchema, $endSchema] = $this->getDuplicateKeySchemas();
		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, ['id' => 1, 'name' => 'foo']);
		$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'bar']);
		$this->connection->insert($this->tableName, ['id' => 3, 'name' => 'qwerty']);

		$migrator->migrate($endSchema);
		$this->addToAssertionCount(1);

		$this->config->setSystemValue('dbtableprefix', $oldTablePrefix);
	}

	public function testInsertAfterUpgrade(): void {
		[$startSchema, $endSchema] = $this->getDuplicateKeySchemas();
		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->migrate($endSchema);

		$this->connection->insert($this->tableName, ['id' => 1, 'name' => 'foo']);
		$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'bar']);
		try {
			$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'qwerty']);
			$this->fail('Expected duplicate key insert to fail');
		} catch (Exception $e) {
			$this->addToAssertionCount(1);
		}
	}

	public function testAddingPrimaryKeyWithAutoIncrement(): void {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true]);
		$table->addColumn('name', 'string');
		$table->setPrimaryKey(['id']);

		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->migrate($endSchema);

		$this->addToAssertionCount(1);
	}

	public function testReservedKeywords(): void {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true]);
		$table->addColumn('user', 'string', ['length' => 255]);
		$table->setPrimaryKey(['id']);

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true]);
		$table->addColumn('user', 'string', ['length' => 64]);
		$table->setPrimaryKey(['id']);

		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->migrate($endSchema);

		$this->addToAssertionCount(1);
	}

	/**
	 * Test for nextcloud/server#36803
	 */
	public function testColumnCommentsInUpdate(): void {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true, 'comment' => 'foo']);
		$table->setPrimaryKey(['id']);

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true, 'comment' => 'foo']);
		// Assert adding comments on existing tables work (or at least does not throw)
		$table->addColumn('time', 'integer', ['comment' => 'unix-timestamp', 'notnull' => false]);
		$table->setPrimaryKey(['id']);

		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->migrate($endSchema);

		$this->addToAssertionCount(1);
	}

	/**
	 * Validates the manual workaround for the ORA-22858 upgrade failure:
	 * converting a string column to text via add-copy-drop-rename in plain
	 * SQL, after which the type guard of a migration like
	 * Version34000Date20260318095645 (oc_jobs.argument) sees a text column
	 * and no-ops.
	 */
	public function testStringToTextConversionWorkaroundOnOracle(): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_ORACLE) {
			$this->markTestSkipped('Validates Oracle-specific workaround SQL');
		}

		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', Types::BIGINT);
		$table->addColumn('argument', Types::STRING, [
			'notnull' => true,
			'length' => 4000,
			'default' => '',
		]);
		$table->addIndex(['id'], $this->tableName . '_id');

		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$values = [
			'{"foo":"bar"}',
			json_encode(['data' => str_repeat('x', 3900)]),
			json_encode(['emoji' => 'üñïçødé 🥘 "quoted" <tag> \\']),
		];
		foreach ($values as $i => $value) {
			$this->connection->insert($this->tableName, ['id' => $i + 1, 'argument' => $value]);
		}

		$quotedTable = $this->connection->quoteIdentifier($this->tableName);
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' ADD ("argument2" CLOB)');
		$this->connection->executeStatement('UPDATE ' . $quotedTable . ' SET "argument2" = "argument"');

		// verification gate: must be 0 before the old column may be dropped
		$mismatches = $this->connection->executeQuery(
			'SELECT COUNT(*) FROM ' . $quotedTable
			. ' WHERE ("argument" IS NOT NULL AND "argument2" IS NULL)'
			. ' OR DBMS_LOB.COMPARE("argument2", TO_CLOB("argument")) <> 0'
		)->fetchOne();
		$this->assertSame(0, (int)$mismatches);

		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' DROP COLUMN "argument"');
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' RENAME COLUMN "argument2" TO "argument"');
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' MODIFY ("argument" NOT NULL)');

		$this->assertSame($values, $this->connection->executeQuery(
			'SELECT ' . $this->connection->quoteIdentifier('argument')
			. ' FROM ' . $quotedTable
			. ' ORDER BY ' . $this->connection->quoteIdentifier('id') . ' ASC'
		)->fetchFirstColumn());

		// same code path as the migration's type guard (ISchemaWrapper)
		$schema = new SchemaWrapper($this->connection);
		$column = $schema->getTable(substr($this->tableName, strlen($this->connection->getPrefix())))
			->getColumn('argument');
		$this->assertSame(Type::getType(Types::TEXT), $column->getType());
		$this->assertTrue($column->getNotnull());
	}

	/**
	 * The string to text conversion fails on Oracle even with zero rows:
	 * ORA-22858 does not depend on the column's contents, so emptying the
	 * table is not a workaround.
	 */
	public function testChangeStringToTextEmptyTableFailsOnOracle(): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_ORACLE) {
			$this->markTestSkipped('Documents an Oracle-specific limitation');
		}

		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', Types::BIGINT);
		$table->addColumn('argument', Types::STRING, [
			'notnull' => true,
			'length' => 4000,
		]);

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', Types::BIGINT);
		$table->addColumn('argument', Types::TEXT, [
			'notnull' => true,
		]);

		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		try {
			$migrator->migrate($endSchema);
			$this->fail('Expected the conversion of an empty table to fail with ORA-22858');
		} catch (\Doctrine\DBAL\Exception\DriverException $e) {
			$this->assertStringContainsString('ORA-22858', $e->getMessage());
		}
		if ($this->connection->isTransactionActive()) {
			$this->connection->rollBack();
		}
	}

	/**
	 * The PL/SQL block of the customer workaround script, read from the
	 * shipped script file so CI validates the literal artifact, with the
	 * sqlplus substitution variables replaced the way sqlplus would.
	 */
	private function getWorkaroundScriptBlock(string $tableName, string $backupConfirmed): string {
		$script = file_get_contents(\OC::$SERVERROOT . '/tests/data/nc-ora22858-workaround.sql');
		$this->assertNotFalse($script, 'workaround script file not readable');
		$this->assertSame(1, preg_match('/^DECLARE\R.*?^END;/ms', $script, $matches), 'PL/SQL block not found in workaround script');
		return str_replace(
			['&table_name', '&backup_confirmed'],
			[$tableName, $backupConfirmed],
			$matches[0],
		);
	}

	private function createWorkaroundTestTable(): array {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', Types::BIGINT);
		$table->addColumn('argument', Types::STRING, [
			'notnull' => true,
			'length' => 4000,
			'default' => '',
		]);
		$table->addIndex(['id'], $this->tableName . '_id');
		$this->getMigrator()->migrate($startSchema);

		$values = [
			'{"foo":"bar"}',
			json_encode(['data' => str_repeat('x', 3900)]),
			json_encode(['emoji' => 'üñïçødé 🥘 "quoted" <tag> \\']),
		];
		foreach ($values as $i => $value) {
			$this->connection->insert($this->tableName, ['id' => $i + 1, 'argument' => $value]);
		}
		return $values;
	}

	private function assertWorkaroundResult(array $values): void {
		$this->assertSame($values, $this->connection->executeQuery(
			'SELECT ' . $this->connection->quoteIdentifier('argument')
			. ' FROM ' . $this->connection->quoteIdentifier($this->tableName)
			. ' ORDER BY ' . $this->connection->quoteIdentifier('id') . ' ASC'
		)->fetchFirstColumn());

		$schema = new SchemaWrapper($this->connection);
		$column = $schema->getTable(substr($this->tableName, strlen($this->connection->getPrefix())))
			->getColumn('argument');
		$this->assertSame(Type::getType(Types::TEXT), $column->getType());
		$this->assertTrue($column->getNotnull());
	}

	/**
	 * Validates the customer workaround script for the ORA-22858 upgrade
	 * failure: abort without backup confirmation, fresh conversion, and
	 * idempotent re-run.
	 */
	public function testWorkaroundScriptOnOracle(): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_ORACLE) {
			$this->markTestSkipped('Validates the Oracle-specific workaround script');
		}

		$values = $this->createWorkaroundTestTable();

		// pressing enter at the prompt yields an empty value - must abort
		foreach (['no', ''] as $notConfirmed) {
			try {
				$this->connection->executeStatement($this->getWorkaroundScriptBlock($this->tableName, $notConfirmed));
				$this->fail('Expected the script to abort without backup confirmation');
			} catch (\Doctrine\DBAL\Exception\DriverException $e) {
				$this->assertStringContainsString('backup not confirmed', $e->getMessage());
			}
		}

		// a customer-added constraint referencing the column must abort the
		// run (DROP COLUMN would silently remove it); the system NOT NULL
		// constraint must not trigger this
		$quotedTable = $this->connection->quoteIdentifier($this->tableName);
		$constraintName = strtoupper($this->tableName) . '_CX';
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable
			. ' ADD CONSTRAINT ' . $constraintName . ' CHECK ("argument" IS NOT NULL)');
		try {
			$this->connection->executeStatement($this->getWorkaroundScriptBlock($this->tableName, 'yes'));
			$this->fail('Expected the script to refuse a customer-added constraint on the column');
		} catch (\Doctrine\DBAL\Exception\DriverException $e) {
			$this->assertStringContainsString($constraintName, $e->getMessage());
		}
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' DROP CONSTRAINT ' . $constraintName);

		// an argument2 column this script did not create must not be dropped
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' ADD ("argument2" NUMBER)');
		try {
			$this->connection->executeStatement($this->getWorkaroundScriptBlock($this->tableName, 'yes'));
			$this->fail('Expected the script to refuse a foreign argument2 column');
		} catch (\Doctrine\DBAL\Exception\DriverException $e) {
			$this->assertStringContainsString('argument2', $e->getMessage());
		}
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' DROP COLUMN "argument2"');

		$this->connection->executeStatement($this->getWorkaroundScriptBlock($this->tableName, 'yes'));
		$this->assertWorkaroundResult($values);

		// re-running must be a clean no-op
		$this->connection->executeStatement($this->getWorkaroundScriptBlock($this->tableName, 'yes'));
		$this->assertWorkaroundResult($values);
	}

	/**
	 * The script must finish a run that was interrupted between dropping the
	 * original column and renaming the copy.
	 */
	public function testWorkaroundScriptResumesInterruptedRunOnOracle(): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_ORACLE) {
			$this->markTestSkipped('Validates the Oracle-specific workaround script');
		}

		$values = $this->createWorkaroundTestTable();

		// reproduce the state after DROP COLUMN but before RENAME
		$quotedTable = $this->connection->quoteIdentifier($this->tableName);
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' ADD ("argument2" CLOB)');
		$this->connection->executeStatement('UPDATE ' . $quotedTable . ' SET "argument2" = "argument"');
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' DROP COLUMN "argument"');

		$this->connection->executeStatement($this->getWorkaroundScriptBlock($this->tableName, 'yes'));
		$this->assertWorkaroundResult($values);
	}

	private function getPreflightScriptBlock(string $tableName): string {
		$script = file_get_contents(\OC::$SERVERROOT . '/tests/data/nc-ora22858-preflight.sql');
		$this->assertNotFalse($script, 'preflight script file not readable');
		$this->assertSame(1, preg_match('/^DECLARE\R.*?^END;/ms', $script, $matches), 'PL/SQL block not found in preflight script');
		return str_replace('&table_name', $tableName, $matches[0]);
	}

	/**
	 * Fetch and clear the DBMS_OUTPUT buffer of the current session.
	 */
	private function fetchDbmsOutput(): string {
		$oci = $this->connection->getNativeConnection();
		$stmt = oci_parse($oci,
			'DECLARE
				l_lines DBMS_OUTPUT.CHARARR;
				l_n INTEGER := 1000;
				l_all VARCHAR2(32767) := \'\';
			BEGIN
				DBMS_OUTPUT.GET_LINES(l_lines, l_n);
				FOR i IN 1 .. l_n LOOP
					l_all := l_all || l_lines(i) || CHR(10);
				END LOOP;
				:output := l_all;
			END;');
		$output = '';
		oci_bind_by_name($stmt, ':output', $output, 32767);
		$this->assertTrue(oci_execute($stmt), 'fetching DBMS_OUTPUT failed');
		return (string)$output;
	}

	/**
	 * The read-only pre-flight diagnostics must produce a complete, honest
	 * report on a real table without modifying it, and report (not throw on)
	 * a missing table. Output markers are asserted via DBMS_OUTPUT because
	 * the script's outer exception handler means "did not throw" proves
	 * nothing by itself.
	 */
	public function testPreflightScriptOnOracle(): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_ORACLE) {
			$this->markTestSkipped('Validates the Oracle-specific preflight script');
		}

		$values = $this->createWorkaroundTestTable();
		$this->connection->executeStatement('BEGIN DBMS_OUTPUT.ENABLE(NULL); END;');

		$this->connection->executeStatement($this->getPreflightScriptBlock($this->tableName));
		$output = $this->fetchDbmsOutput();
		$this->assertStringContainsString('PREFLIGHT COMPLETE', $output);
		$this->assertStringContainsString('Table found.', $output);
		$this->assertStringContainsString('Rows: 3, NULL arguments: 0', $output);
		$this->assertStringNotContainsString('INTERNAL ERROR', $output);
		$this->assertStringNotContainsString('could not read rows', $output);
		// CI runs privileged, so no section may take a degradation path here
		$this->assertStringNotContainsString('skipped', $output);

		// table untouched: same rows, column still the original string type
		$this->assertSame($values, $this->connection->executeQuery(
			'SELECT ' . $this->connection->quoteIdentifier('argument')
			. ' FROM ' . $this->connection->quoteIdentifier($this->tableName)
			. ' ORDER BY ' . $this->connection->quoteIdentifier('id') . ' ASC'
		)->fetchFirstColumn());
		$schema = new SchemaWrapper($this->connection);
		$column = $schema->getTable(substr($this->tableName, strlen($this->connection->getPrefix())))
			->getColumn('argument');
		$this->assertSame(Type::getType(Types::STRING), $column->getType());

		// a nonexistent table must be reported cleanly, not thrown, and must
		// not produce misleading storage output
		$this->connection->executeStatement($this->getPreflightScriptBlock($this->tableNameTmp));
		$output = $this->fetchDbmsOutput();
		$this->assertStringContainsString('TABLE NOT FOUND', $output);
		$this->assertStringContainsString('PREFLIGHT COMPLETE', $output);
		$this->assertStringNotContainsString('INTERNAL ERROR', $output);
		$this->assertStringNotContainsString('Table segment size', $output);
	}

	/**
	 * A run interrupted between the RENAME and the NOT NULL restore leaves
	 * a nullable CLOB column - a re-run must repair the constraint instead
	 * of declaring the state done.
	 */
	public function testWorkaroundScriptRestoresNotNullOnOracle(): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_ORACLE) {
			$this->markTestSkipped('Validates the Oracle-specific workaround script');
		}

		$values = $this->createWorkaroundTestTable();

		// reproduce the state after RENAME but before the NOT NULL restore
		$quotedTable = $this->connection->quoteIdentifier($this->tableName);
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' ADD ("argument2" CLOB)');
		$this->connection->executeStatement('UPDATE ' . $quotedTable . ' SET "argument2" = "argument"');
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' DROP COLUMN "argument"');
		$this->connection->executeStatement('ALTER TABLE ' . $quotedTable . ' RENAME COLUMN "argument2" TO "argument"');

		$this->connection->executeStatement($this->getWorkaroundScriptBlock($this->tableName, 'yes'));
		$this->assertWorkaroundResult($values);
	}

	public function testAddingForeignKey(): void {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true]);
		$table->addColumn('name', 'string');
		$table->setPrimaryKey(['id']);

		$fkName = 'fkc';
		$tableFk = $startSchema->createTable($this->tableNameTmp);
		$tableFk->addColumn('fk_id', 'integer');
		$tableFk->addColumn('name', 'string');
		$tableFk->addForeignKeyConstraint($this->tableName, ['fk_id'], ['id'], [], $fkName);

		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$this->assertTrue($startSchema->getTable($this->tableNameTmp)->hasForeignKey($fkName));
	}

	public static function dataNotNullEmptyValuesFailOracle(): array {
		return [
			[ParameterType::BOOLEAN, true, Types::BOOLEAN, false],
			[ParameterType::BOOLEAN, false, Types::BOOLEAN, true],

			[ParameterType::STRING, 'foo', Types::STRING, false],
			[ParameterType::STRING, '', Types::STRING, true],

			[ParameterType::INTEGER, 1234, Types::INTEGER, false],
			[ParameterType::INTEGER, 0, Types::INTEGER, false], // Integer 0 is not stored as Null and therefor works

			[ParameterType::STRING, '{"a": 2}', Types::JSON, false],
		];
	}

	/**
	 *
	 * @param int $parameterType
	 * @param bool|int|string $value
	 * @param string $columnType
	 * @param bool $oracleThrows
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataNotNullEmptyValuesFailOracle')]
	public function testNotNullEmptyValuesFailOracle(int $parameterType, $value, string $columnType, bool $oracleThrows): void {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', Types::BIGINT);
		$table->addColumn('will_it_blend', $columnType, [
			'notnull' => true,
		]);
		$table->addIndex(['id'], $this->tableName . '_id');

		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		if ($oracleThrows && $this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) {
			// Oracle can not store false|empty string in notnull columns
			$this->expectException(\Doctrine\DBAL\Exception\NotNullConstraintViolationException::class);
		}

		$this->connection->insert(
			$this->tableName,
			['id' => 1, 'will_it_blend' => $value],
			['id' => ParameterType::INTEGER, 'will_it_blend' => $parameterType],
		);

		$this->addToAssertionCount(1);
	}
}
