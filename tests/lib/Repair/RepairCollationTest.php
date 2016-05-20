<?php

namespace Test\Repair;

use OCP\Migration\IOutput;

/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class TestCollationRepair extends \OC\Repair\Collation {
	/**
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return string[]
	 */
	public function getAllNonUTF8BinTables($connection) {
		return parent::getAllNonUTF8BinTables($connection);
	}
}

/**
 * Tests for the converting of MySQL tables to InnoDB engine
 *
 * @group DB
 *
 * @see \OC\Repair\RepairMimeTypes
 */
class RepairCollationTest extends \Test\TestCase {

	/**
	 * @var TestCollationRepair
	 */
	private $repair;

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	protected function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->config = \OC::$server->getConfig();
		if (!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySqlPlatform) {
			$this->markTestSkipped("Test only relevant on MySql");
		}

		$dbPrefix = $this->config->getSystemValue("dbtableprefix");
		$this->tableName = $this->getUniqueID($dbPrefix . "_collation_test");
		$this->connection->exec("CREATE TABLE $this->tableName(text VARCHAR(16)) COLLATE utf8_unicode_ci");

		$this->repair = new TestCollationRepair($this->config, $this->connection);
	}

	protected function tearDown() {
		$this->connection->getSchemaManager()->dropTable($this->tableName);
		parent::tearDown();
	}

	public function testCollationConvert() {
		$tables = $this->repair->getAllNonUTF8BinTables($this->connection);
		$this->assertGreaterThanOrEqual(1, count($tables));

		/** @var IOutput | \PHPUnit_Framework_MockObject_MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		$tables = $this->repair->getAllNonUTF8BinTables($this->connection);
		$this->assertCount(0, $tables);
	}
}
