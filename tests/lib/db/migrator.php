<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\DB;

use \Doctrine\DBAL\DBALException;
use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\SchemaConfig;

class Migrator extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \Doctrine\DBAL\Connection $connection
	 */
	private $connection;

	private $tableName;

	public function setUp() {
		$this->connection = \OC_DB::getConnection();
		if ($this->connection->getDriver() instanceof \Doctrine\DBAL\Driver\OCI8\Driver) {
			$this->markTestSkipped('DB migration tests arent supported on OCI');
		}
		$this->tableName = 'test_' . uniqid();
	}

	public function tearDown() {
		$this->connection->exec('DROP TABLE ' . $this->tableName);
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

	private function getMigrator() {
		if ($this->isSQLite()) {
			return new \OC\DB\SQLiteMigrator($this->connection);
		} else {
			return new \OC\DB\Migrator($this->connection);
		}
	}

	/**
	 * @expectedException \OC\DB\MigrationException
	 */
	public function testDuplicateKeyUpgrade() {
		if ($this->isSQLite()) {
			$this->markTestSkipped('sqlite doesnt throw errors when creating a new key on existing data');
		}
		list($startSchema, $endSchema) = $this->getDuplicateKeySchemas();
		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, array('id' => 1, 'name' => 'foo'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'bar'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'qwerty'));

		$migrator->checkMigrate($endSchema);
		$this->fail('checkMigrate should have failed');
	}

	public function testUpgrade() {
		list($startSchema, $endSchema) = $this->getDuplicateKeySchemas();
		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, array('id' => 1, 'name' => 'foo'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'bar'));
		$this->connection->insert($this->tableName, array('id' => 3, 'name' => 'qwerty'));

		$migrator->checkMigrate($endSchema);
		$migrator->migrate($endSchema);
		$this->assertTrue(true);
	}

	public function testInsertAfterUpgrade() {
		list($startSchema, $endSchema) = $this->getDuplicateKeySchemas();
		$migrator = $this->getMigrator();
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

		$migrator = $this->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->checkMigrate($endSchema);
		$migrator->migrate($endSchema);

		$this->assertTrue(true);
	}
}
