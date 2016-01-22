<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\DB;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use OC\DB\MDB2SchemaManager;

/**
 * Class Connection
 *
 * @group DB
 *
 * @package Test\DB
 */
class Connection extends \Test\TestCase {
	/**
	 * @var \OCP\IDBConnection
	 */
	private $connection;

	public static function setUpBeforeClass() {
		self::dropTestTable();
		parent::setUpBeforeClass();
	}

	public static function tearDownAfterClass() {
		self::dropTestTable();
		parent::tearDownAfterClass();
	}

	protected static function dropTestTable() {
		if (\OC::$server->getConfig()->getSystemValue('dbtype', 'sqlite') !== 'oci') {
			\OC::$server->getDatabaseConnection()->dropTable('table');
		}
	}

	public function setUp() {
		parent::setUp();
		$this->connection = \OC::$server->getDatabaseConnection();
	}

	/**
	 * @param string $table
	 */
	public function assertTableExist($table) {
		if ($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
			// sqlite removes the tables after closing the DB
			$this->assertTrue(true);
		} else {
			$this->assertTrue($this->connection->tableExists($table), 'Table ' . $table . ' exists.');
		}
	}

	/**
	 * @param string $table
	 */
	public function assertTableNotExist($table) {
		if ($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
			// sqlite removes the tables after closing the DB
			$this->assertTrue(true);
		} else {
			$this->assertFalse($this->connection->tableExists($table), 'Table ' . $table . ' doesnt exists.');
		}
	}

	private function makeTestTable() {
		$schemaManager = new MDB2SchemaManager($this->connection);
		$schemaManager->createDbFromStructure(__DIR__ . '/testschema.xml');
	}

	public function testTableExists() {
		$this->assertTableNotExist('table');
		$this->makeTestTable();
		$this->assertTableExist('table');
	}

	/**
	 * @depends testTableExists
	 */
	public function testDropTable() {
		$this->assertTableExist('table');
		$this->connection->dropTable('table');
		$this->assertTableNotExist('table');
	}

	private function getTextValueByIntergerField($integerField) {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select('textfield')
			->from('table')
			->where($builder->expr()->eq('integerfield', $builder->createNamedParameter($integerField, \PDO::PARAM_INT)));

		$result = $query->execute();
		return $result->fetchColumn();
	}

	public function testSetValues() {
		$this->makeTestTable();
		$this->connection->setValues('table', [
			'integerfield' => 1
		], [
			'textfield' => 'foo',
			'clobfield' => 'not_null'
		]);

		$this->assertEquals('foo', $this->getTextValueByIntergerField(1));

		$this->connection->dropTable('table');
	}

	public function testSetValuesOverWrite() {
		$this->makeTestTable();
		$this->connection->setValues('table', [
			'integerfield' => 1
		], [
			'textfield' => 'foo',
			'clobfield' => 'not_null'
		]);

		$this->connection->setValues('table', [
			'integerfield' => 1
		], [
			'textfield' => 'bar'
		]);

		$this->assertEquals('bar', $this->getTextValueByIntergerField(1));

		$this->connection->dropTable('table');
	}

	public function testSetValuesOverWritePrecondition() {
		$this->makeTestTable();
		$this->connection->setValues('table', [
			'integerfield' => 1
		], [
			'textfield' => 'foo',
			'booleanfield' => true,
			'clobfield' => 'not_null'
		]);

		$this->connection->setValues('table', [
			'integerfield' => 1
		], [
			'textfield' => 'bar'
		], [
			'booleanfield' => true
		]);

		$this->assertEquals('bar', $this->getTextValueByIntergerField(1));

		$this->connection->dropTable('table');
	}

	/**
	 * @expectedException \OCP\PreConditionNotMetException
	 */
	public function testSetValuesOverWritePreconditionFailed() {
		$this->makeTestTable();
		$this->connection->setValues('table', [
			'integerfield' => 1
		], [
			'textfield' => 'foo',
			'booleanfield' => true,
			'clobfield' => 'not_null'
		]);

		$this->connection->setValues('table', [
			'integerfield' => 1
		], [
			'textfield' => 'bar'
		], [
			'booleanfield' => false
		]);
	}
}
