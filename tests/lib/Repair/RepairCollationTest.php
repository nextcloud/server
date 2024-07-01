<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\Collation;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class TestCollationRepair extends Collation {
	/**
	 * @param IDBConnection $connection
	 * @return string[]
	 */
	public function getAllNonUTF8BinTables(IDBConnection $connection) {
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
class RepairCollationTest extends TestCase {
	/**
	 * @var TestCollationRepair
	 */
	private $repair;

	/**
	 * @var IDBConnection
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

	/** @var LoggerInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OC::$server->get(IDBConnection::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->config = \OC::$server->getConfig();
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_MYSQL) {
			$this->markTestSkipped("Test only relevant on MySql");
		}

		$dbPrefix = $this->config->getSystemValueString("dbtableprefix");
		$this->tableName = $this->getUniqueID($dbPrefix . "_collation_test");
		$this->connection->prepare("CREATE TABLE $this->tableName(text VARCHAR(16)) COLLATE utf8_unicode_ci")->execute();

		$this->repair = new TestCollationRepair($this->config, $this->logger, $this->connection, false);
	}

	protected function tearDown(): void {
		$this->connection->getInner()->createSchemaManager()->dropTable($this->tableName);
		parent::tearDown();
	}

	public function testCollationConvert() {
		$tables = $this->repair->getAllNonUTF8BinTables($this->connection);
		$this->assertGreaterThanOrEqual(1, count($tables));

		/** @var IOutput | \PHPUnit\Framework\MockObject\MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		$tables = $this->repair->getAllNonUTF8BinTables($this->connection);
		$this->assertCount(0, $tables);
	}
}
