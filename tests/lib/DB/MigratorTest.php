<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\DB;

use \Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\OraclePlatform;
use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\SchemaConfig;
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
	 * @var \OC\DB\MDB2SchemaManager
	 */
	private $manager;

	/**
	 * @var IConfig
	 **/
	private $config;

	/** @var string */
	private $tableName;

	protected function setUp() {
		parent::setUp();

		$this->config = \OC::$server->getConfig();
		$this->connection = \OC::$server->getDatabaseConnection();
		if ($this->connection->getDatabasePlatform() instanceof OraclePlatform) {
			$this->markTestSkipped('DB migration tests are not supported on OCI');
		}
		$this->manager = new \OC\DB\MDB2SchemaManager($this->connection);
		$this->tableName = strtolower($this->getUniqueID($this->config->getSystemValue('dbtableprefix', 'oc_') . 'test_'));
	}

	protected function tearDown() {
		$this->connection->exec('DROP TABLE ' . $this->tableName);
		parent::tearDown();
	}

	/**
	 * @return \Doctrine\DBAL\Schema\Schema[]
	 */
	private function getDuplicateKeySchemas() {
		$startSchema = new Schema(array(), array(), $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');
		$table->addIndex(array('id'), $this->tableName . '_id');

		$endSchema = new Schema(array(), array(), $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');
		$table->addUniqueIndex(array('id'), $this->tableName . '_id');

		return array($startSchema, $endSchema);
	}

	private function getSchemaConfig() {
		$config = new SchemaConfig();
		$config->setName($this->connection->getDatabase());
		return $config;
	}

	private function isSQLite() {
		return $this->connection->getDriver() instanceof \Doctrine\DBAL\Driver\PDOSqlite\Driver;
	}

	/**
	 * @expectedException \OC\DB\MigrationException
	 */
	public function testDuplicateKeyUpgrade() {
		if ($this->isSQLite()) {
			$this->markTestSkipped('sqlite does not throw errors when creating a new key on existing data');
		}
		list($startSchema, $endSchema) = $this->getDuplicateKeySchemas();
		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, array('id' => 1, 'name' => 'foo'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'bar'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'qwerty'));

		$migrator->checkMigrate($endSchema);
		$this->fail('checkMigrate should have failed');
	}

	public function testUpgrade() {
		list($startSchema, $endSchema) = $this->getDuplicateKeySchemas();
		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, array('id' => 1, 'name' => 'foo'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'bar'));
		$this->connection->insert($this->tableName, array('id' => 3, 'name' => 'qwerty'));

		$migrator->checkMigrate($endSchema);
		$migrator->migrate($endSchema);
		$this->assertTrue(true);
	}

	public function testUpgradeDifferentPrefix() {
		$oldTablePrefix = $this->config->getSystemValue('dbtableprefix', 'oc_');

		$this->config->setSystemValue('dbtableprefix', 'ownc_');
		$this->tableName = strtolower($this->getUniqueID($this->config->getSystemValue('dbtableprefix') . 'test_'));

		list($startSchema, $endSchema) = $this->getDuplicateKeySchemas();
		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, array('id' => 1, 'name' => 'foo'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'bar'));
		$this->connection->insert($this->tableName, array('id' => 3, 'name' => 'qwerty'));

		$migrator->checkMigrate($endSchema);
		$migrator->migrate($endSchema);
		$this->assertTrue(true);

		$this->config->setSystemValue('dbtableprefix', $oldTablePrefix);
	}

	public function testInsertAfterUpgrade() {
		list($startSchema, $endSchema) = $this->getDuplicateKeySchemas();
		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->migrate($endSchema);

		$this->connection->insert($this->tableName, array('id' => 1, 'name' => 'foo'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'bar'));
		try {
			$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'qwerty'));
			$this->fail('Expected duplicate key insert to fail');
		} catch (DBALException $e) {
			$this->assertTrue(true);
		}
	}

	public function testAddingPrimaryKeyWithAutoIncrement() {
		$startSchema = new Schema(array(), array(), $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');

		$endSchema = new Schema(array(), array(), $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', array('autoincrement' => true));
		$table->addColumn('name', 'string');
		$table->setPrimaryKey(array('id'));

		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->checkMigrate($endSchema);
		$migrator->migrate($endSchema);

		$this->assertTrue(true);
	}

	public function testReservedKeywords() {
		$startSchema = new Schema(array(), array(), $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', array('autoincrement' => true));
		$table->addColumn('user', 'string', array('length' => 255));
		$table->setPrimaryKey(array('id'));

		$endSchema = new Schema(array(), array(), $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', array('autoincrement' => true));
		$table->addColumn('user', 'string', array('length' => 64));
		$table->setPrimaryKey(array('id'));

		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->checkMigrate($endSchema);
		$migrator->migrate($endSchema);

		$this->assertTrue(true);
	}
}
