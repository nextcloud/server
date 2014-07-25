<?php
/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class TestSqliteMigration extends \PHPUnit_Framework_TestCase {

	/** @var \Doctrine\DBAL\Connection */
	private $connection;

	/** @var string */
	private $tableName;

	public function setUp() {
		$this->connection = \OC_DB::getConnection();
		if (!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform) {
			$this->markTestSkipped("Test only relevant on Sqlite");
		}

		$dbPrefix = \OC::$server->getConfig()->getSystemValue("dbtableprefix");
		$this->tableName = uniqid($dbPrefix . "_enum_bit_test");
		$this->connection->exec("CREATE TABLE $this->tableName(t0 tinyint unsigned, t1 tinyint)");
	}

	public function tearDown() {
		$this->connection->getSchemaManager()->dropTable($this->tableName);
	}

	public function testNonOCTables() {
		$manager = new \OC\DB\MDB2SchemaManager($this->connection);
		$manager->updateDbFromStructure(__DIR__ . '/testschema.xml');

		$this->assertTrue(true);
	}

}
