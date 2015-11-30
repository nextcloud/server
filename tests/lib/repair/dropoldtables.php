<?php
/**
 * Copyright (c) 2015 Joas Schilling <nickvergessen@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Repair;

/**
 * Tests for the dropping old tables
 *
 * @group DB
 *
 * @see \OC\Repair\DropOldTables
 */
class DropOldTables extends \Test\TestCase {
	/** @var \OCP\IDBConnection */
	protected $connection;

	protected function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$manager = new \OC\DB\MDB2SchemaManager($this->connection);
		$manager->createDbFromStructure(__DIR__ . '/fixtures/dropoldtables.xml');
	}

	public function testRun() {
		$this->assertFalse($this->connection->tableExists('sharing'), 'Asserting that the table oc_sharing does not exist before repairing');
		$this->assertTrue($this->connection->tableExists('permissions'), 'Asserting that the table oc_permissions does exist before repairing');

		$repair = new \OC\Repair\DropOldTables($this->connection);
		$repair->run();

		$this->assertFalse($this->connection->tableExists('sharing'), 'Asserting that the table oc_sharing does not exist after repairing');
		$this->assertFalse($this->connection->tableExists('permissions'), 'Asserting that the table oc_permissions does not exist after repairing');
	}
}
