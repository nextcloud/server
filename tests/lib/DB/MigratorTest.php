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
	 * The PL/SQL block of the customer workaround script
	 * (nc-ora22858-workaround.sql) with the sqlplus substitution variables
	 * replaced. Must be kept identical to the block in the shipped script.
	 */
	private function getWorkaroundScriptBlock(string $tableName, string $backupConfirmed): string {
		$block = <<<'SQL'
DECLARE
	l_table         CONSTANT VARCHAR2(128) := '&table_name';
	l_table_exists  INTEGER;
	l_old_type      user_tab_columns.data_type%TYPE;
	l_old_nullable  user_tab_columns.nullable%TYPE;
	l_new_type      user_tab_columns.data_type%TYPE;
	l_rows          INTEGER;
	l_nulls         INTEGER;
	l_count         INTEGER;

	PROCEDURE fail(p_msg IN VARCHAR2) IS
	BEGIN
		RAISE_APPLICATION_ERROR(-20001, p_msg);
	END;

	PROCEDURE say(p_msg IN VARCHAR2) IS
	BEGIN
		DBMS_OUTPUT.PUT_LINE(p_msg);
	END;
BEGIN
	IF LOWER(TRIM('&backup_confirmed')) NOT IN ('yes', 'y') THEN
		fail('Aborted: restorable backup not confirmed. Nothing was changed.');
	END IF;

	SELECT COUNT(*) INTO l_table_exists
	  FROM user_tables WHERE table_name = l_table;
	IF l_table_exists = 0 THEN
		fail('Table "' || l_table || '" not found in this schema. Check the '
			|| 'dbtableprefix in config.php and that you are connected as the '
			|| 'Nextcloud database user. Nothing was changed.');
	END IF;

	BEGIN
		SELECT data_type, nullable INTO l_old_type, l_old_nullable
		  FROM user_tab_columns
		 WHERE table_name = l_table AND column_name = 'argument';
	EXCEPTION WHEN NO_DATA_FOUND THEN
		l_old_type := NULL;
	END;

	BEGIN
		SELECT data_type INTO l_new_type
		  FROM user_tab_columns
		 WHERE table_name = l_table AND column_name = 'argument2';
	EXCEPTION WHEN NO_DATA_FOUND THEN
		l_new_type := NULL;
	END;

	-- State: already converted (also the state after a successful run).
	IF l_old_type = 'CLOB' AND l_new_type IS NULL THEN
		say('Column "argument" is already CLOB - nothing to do.');
		say('Re-run `occ upgrade` if it has not completed yet.');
		RETURN;
	END IF;

	-- State: unexpected - do not guess.
	IF l_old_type = 'CLOB' AND l_new_type IS NOT NULL THEN
		fail('Unexpected state: "argument" is already CLOB but "argument2" '
			|| 'also exists. Manual inspection required. Nothing was changed.');
	END IF;

	-- State: interrupted between DROP and RENAME (copy was verified and
	-- committed before the drop, so finishing the rename is safe).
	IF l_old_type IS NULL AND l_new_type = 'CLOB' THEN
		say('Resuming interrupted run: renaming "argument2" to "argument".');
		EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
			|| '" RENAME COLUMN "argument2" TO "argument"';
		EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table
			|| '" WHERE "argument" IS NULL' INTO l_nulls;
		IF l_nulls = 0 THEN
			EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
				|| '" MODIFY ("argument" NOT NULL)';
		ELSE
			say('WARNING: ' || l_nulls || ' NULL values present, NOT NULL '
				|| 'constraint not restored - report this in the ticket.');
		END IF;
		say('SUCCESS (resumed run completed). Re-run `occ upgrade` now.');
		RETURN;
	END IF;

	IF l_old_type IS NULL THEN
		fail('Column "argument" not found on "' || l_table || '" - '
			|| 'unexpected schema. Nothing was changed.');
	END IF;

	IF l_old_type <> 'VARCHAR2' THEN
		fail('Unexpected type for "argument": ' || l_old_type
			|| ' (expected VARCHAR2). Nothing was changed.');
	END IF;

	-- State: interrupted after ADD/copy but before DROP - the temporary
	-- column may hold a partial copy; remove it and redo from scratch.
	IF l_new_type IS NOT NULL THEN
		say('Previous interrupted run detected: dropping "argument2" and '
			|| 'redoing the copy.');
		EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
			|| '" DROP COLUMN "argument2"';
	END IF;

	-- Fresh conversion.
	EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table || '"' INTO l_rows;
	EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table
		|| '" WHERE "argument" IS NULL' INTO l_nulls;
	say('Rows: ' || l_rows || ', NULL arguments: ' || l_nulls);
	IF l_nulls > 0 THEN
		fail(l_nulls || ' NULL values in "argument" - stop and report this '
			|| 'in the ticket. Nothing was changed.');
	END IF;

	say('Adding temporary CLOB column and copying data...');
	EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table || '" ADD ("argument2" CLOB)';
	EXECUTE IMMEDIATE 'UPDATE "' || l_table || '" SET "argument2" = "argument"';
	say('Copied ' || SQL%ROWCOUNT || ' rows.');

	-- Verification gate - runs BEFORE the copy is committed.
	EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table
		|| '" WHERE ("argument" IS NOT NULL AND "argument2" IS NULL)'
		|| ' OR DBMS_LOB.COMPARE("argument2", TO_CLOB("argument")) <> 0'
		INTO l_count;
	IF l_count <> 0 THEN
		ROLLBACK;
		EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
			|| '" DROP COLUMN "argument2"';
		fail('Verification failed: ' || l_count || ' rows did not copy '
			|| 'identically. The copy was rolled back and the temporary '
			|| 'column removed - the original column is untouched. Report '
			|| 'this in the ticket.');
	END IF;
	COMMIT;
	say('Copy verified: 0 mismatches. Swapping columns...');

	EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table || '" DROP COLUMN "argument"';
	EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
		|| '" RENAME COLUMN "argument2" TO "argument"';
	IF l_old_nullable = 'N' THEN
		EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
			|| '" MODIFY ("argument" NOT NULL)';
	END IF;

	-- Final checks.
	EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table || '"' INTO l_count;
	IF l_count <> l_rows THEN
		fail('Row count changed during conversion: ' || l_rows || ' -> '
			|| l_count || '. Restore from backup and report this.');
	END IF;
	SELECT data_type INTO l_old_type
	  FROM user_tab_columns
	 WHERE table_name = l_table AND column_name = 'argument';
	IF l_old_type <> 'CLOB' THEN
		fail('Post-check failed: "argument" is ' || l_old_type
			|| ', expected CLOB.');
	END IF;

	say('SUCCESS: "argument" converted to CLOB, ' || l_rows
		|| ' rows verified intact. Re-run `occ upgrade` now.');
END;
SQL;
		return str_replace(
			['&table_name', '&backup_confirmed'],
			[$tableName, $backupConfirmed],
			$block,
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

		try {
			$this->connection->executeStatement($this->getWorkaroundScriptBlock($this->tableName, 'no'));
			$this->fail('Expected the script to abort without backup confirmation');
		} catch (\Doctrine\DBAL\Exception\DriverException $e) {
			$this->assertStringContainsString('backup not confirmed', $e->getMessage());
		}

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
