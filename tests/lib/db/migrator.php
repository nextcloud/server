<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\DB;

use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\SchemaConfig;

class Migrator extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \Doctrine\DBAL\Connection $connection
	 */
	private $connection;

	private $tableName;

	private $fullTableName;

	public function setUp() {
		$this->tableName = 'test_' . uniqid();
		$this->connection = \OC_DB::getConnection();
		$this->fullTableName = $this->connection->getDatabase() . '.' . $this->tableName;
	}

	public function tearDown() {
		$this->connection->exec('DROP TABLE ' . $this->fullTableName);
	}

	private function getInitialSchema() {
		$schema = new Schema(array(), array(), $this->getSchemaConfig());
		$table = $schema->createTable($this->fullTableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');
		$table->addIndex(array('id'), $this->tableName . '_id');
		return $schema;
	}

	private function getNewSchema() {
		$schema = new Schema(array(), array(), $this->getSchemaConfig());
		$table = $schema->createTable($this->fullTableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');
		$table->addUniqueIndex(array('id'), $this->tableName . '_id');
		return $schema;
	}

	private function getSchemaConfig() {
		$config = new SchemaConfig();
		$config->setName($this->connection->getDatabase());
		return $config;
	}

	/**
	 * @expectedException \OC\DB\MigrationException
	 */
	public function testDuplicateKeyUpgrade() {
		$migrator = new \OC\DB\Migrator($this->connection);
		$migrator->migrate($this->getInitialSchema());

		$this->connection->insert($this->tableName, array('id' => 1, 'name' => 'foo'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'bar'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'qwerty'));

		$migrator->checkMigrate($this->getNewSchema());
		$this->fail('checkMigrate should have failed');
	}

	public function testUpgrade() {
		$migrator = new \OC\DB\Migrator($this->connection);
		$migrator->migrate($this->getInitialSchema());

		$this->connection->insert($this->tableName, array('id' => 1, 'name' => 'foo'));
		$this->connection->insert($this->tableName, array('id' => 2, 'name' => 'bar'));
		$this->connection->insert($this->tableName, array('id' => 3, 'name' => 'qwerty'));

		$newSchema = $this->getNewSchema();
		$migrator->checkMigrate($newSchema);
		$migrator->migrate($newSchema);
		$this->assertTrue(true);
	}
}
