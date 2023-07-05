<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use OC\DB\Migrator;
use OC\DB\OracleMigrator;
use OC\DB\SQLiteMigrator;
use OCP\DB\Types;
use OCP\IConfig;

/**
 * Class MigratorTest
 *
 * @group DB
 *
 * @package Test\DB
 */
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

	protected function setUp(): void {
		parent::setUp();

		$this->config = \OC::$server->getConfig();
		$this->connection = \OC::$server->get(\OC\DB\Connection::class);

		$this->tableName = $this->getUniqueTableName();
		$this->tableNameTmp = $this->getUniqueTableName();
	}

	private function getMigrator(): Migrator {
		$platform = $this->connection->getDatabasePlatform();
		$random = \OC::$server->getSecureRandom();
		$dispatcher = \OC::$server->get(\OCP\EventDispatcher\IEventDispatcher::class);
		if ($platform instanceof SqlitePlatform) {
			return new SQLiteMigrator($this->connection, $this->config, $dispatcher);
		} elseif ($platform instanceof OraclePlatform) {
			return new OracleMigrator($this->connection, $this->config, $dispatcher);
		}
		return new Migrator($this->connection, $this->config, $dispatcher);
	}

	private function getUniqueTableName() {
		return strtolower($this->getUniqueID($this->config->getSystemValueString('dbtableprefix', 'oc_') . 'test_'));
	}

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

	private function isSQLite() {
		return $this->connection->getDatabasePlatform() instanceof SqlitePlatform;
	}

	public function testUpgrade() {
		[$startSchema, $endSchema] = $this->getDuplicateKeySchemas();
		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, ['id' => 1, 'name' => 'foo']);
		$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'bar']);
		$this->connection->insert($this->tableName, ['id' => 3, 'name' => 'qwerty']);

		$migrator->migrate($endSchema);
		$this->addToAssertionCount(1);
	}

	public function testUpgradeDifferentPrefix() {
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

	public function testInsertAfterUpgrade() {
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

	public function testAddingPrimaryKeyWithAutoIncrement() {
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

	public function testReservedKeywords() {
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
	public function testColumnCommentsInUpdate() {
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

	public function testAddingForeignKey() {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true]);
		$table->addColumn('name', 'string');
		$table->setPrimaryKey(['id']);

		$fkName = "fkc";
		$tableFk = $startSchema->createTable($this->tableNameTmp);
		$tableFk->addColumn('fk_id', 'integer');
		$tableFk->addColumn('name', 'string');
		$tableFk->addForeignKeyConstraint($this->tableName, ['fk_id'], ['id'], [], $fkName);

		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);


		$this->assertTrue($startSchema->getTable($this->tableNameTmp)->hasForeignKey($fkName));
	}

	public function dataNotNullEmptyValuesFailOracle(): array {
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
	 * @dataProvider dataNotNullEmptyValuesFailOracle
	 *
	 * @param int $parameterType
	 * @param bool|int|string $value
	 * @param string $columnType
	 * @param bool $oracleThrows
	 */
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

		if ($oracleThrows && $this->connection->getDatabasePlatform() instanceof OraclePlatform) {
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
