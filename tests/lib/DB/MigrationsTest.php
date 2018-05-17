<?php

/**
 * Copyright (c) 2016 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace Test\DB;

use Doctrine\DBAL\Schema\Schema;
use OC\DB\Connection;
use OC\DB\MigrationService;
use OC\DB\SchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IMigrationStep;

/**
 * Class MigrationsTest
 *
 * @package Test\DB
 */
class MigrationsTest extends \Test\TestCase {

	/** @var MigrationService | \PHPUnit_Framework_MockObject_MockObject */
	private $migrationService;
	/** @var \PHPUnit_Framework_MockObject_MockObject | IDBConnection $db */
	private $db;

	public function setUp() {
		parent::setUp();

		$this->db = $this->createMock(Connection::class);
		$this->db->expects($this->any())->method('getPrefix')->willReturn('test_oc_');
		$this->migrationService = new MigrationService('testing', $this->db);
	}

	public function testGetters() {
		$this->assertEquals('testing', $this->migrationService->getApp());
		$this->assertEquals(\OC::$SERVERROOT . '/apps/testing/lib/Migration', $this->migrationService->getMigrationsDirectory());
		$this->assertEquals('OCA\Testing\Migration', $this->migrationService->getMigrationsNamespace());
		$this->assertEquals('test_oc_migrations', $this->migrationService->getMigrationsTableName());
	}

	public function testCore() {
		$this->migrationService = new MigrationService('core', $this->db);

		$this->assertEquals('core', $this->migrationService->getApp());
		$this->assertEquals(\OC::$SERVERROOT . '/core/Migrations', $this->migrationService->getMigrationsDirectory());
		$this->assertEquals('OC\Core\Migrations', $this->migrationService->getMigrationsNamespace());
		$this->assertEquals('test_oc_migrations', $this->migrationService->getMigrationsTableName());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Version 20170130180000 is unknown.
	 */
	public function testExecuteUnknownStep() {
		$this->migrationService->executeStep('20170130180000');
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage App not found
	 */
	public function testUnknownApp() {
		$migrationService = new MigrationService('unknown-bloody-app', $this->db);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Migration step 'X' is unknown
	 */
	public function testExecuteStepWithUnknownClass() {
		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->setMethods(['findMigrations'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();
		$this->migrationService->expects($this->any())->method('findMigrations')->willReturn(
			['20170130180000' => 'X', '20170130180001' => 'Y', '20170130180002' => 'Z', '20170130180003' => 'A']
		);
		$this->migrationService->executeStep('20170130180000');
	}

	public function testExecuteStepWithSchemaChange() {

		$schema = $this->createMock(Schema::class);
		$this->db->expects($this->any())
			->method('createSchema')
			->willReturn($schema);

		$this->db->expects($this->once())
			->method('migrateToSchema');

		$schemaResult = $this->createMock(SchemaWrapper::class);
		$schemaResult->expects($this->once())
			->method('getWrappedSchema')
			->willReturn($this->createMock(Schema::class));

		$step = $this->createMock(IMigrationStep::class);
		$step->expects($this->at(0))
			->method('preSchemaChange');
		$step->expects($this->at(1))
			->method('changeSchema')
			->willReturn($schemaResult);
		$step->expects($this->at(2))
			->method('postSchemaChange');

		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->setMethods(['createInstance'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();

		$this->migrationService->expects($this->any())
			->method('createInstance')
			->with('20170130180000')
			->willReturn($step);
		$this->migrationService->executeStep('20170130180000');
	}

	public function testExecuteStepWithoutSchemaChange() {

		$schema = $this->createMock(Schema::class);
		$this->db->expects($this->any())
			->method('createSchema')
			->willReturn($schema);

		$this->db->expects($this->never())
			->method('migrateToSchema');

		$step = $this->createMock(IMigrationStep::class);
		$step->expects($this->at(0))
			->method('preSchemaChange');
		$step->expects($this->at(1))
			->method('changeSchema')
			->willReturn(null);
		$step->expects($this->at(2))
			->method('postSchemaChange');

		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->setMethods(['createInstance'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();

		$this->migrationService->expects($this->any())
			->method('createInstance')
			->with('20170130180000')
			->willReturn($step);
		$this->migrationService->executeStep('20170130180000');
	}

	public function dataGetMigration() {
		return [
			['current', '20170130180001'],
			['prev', '20170130180000'],
			['next', '20170130180002'],
			['latest', '20170130180003'],
		];
	}

	/**
	 * @dataProvider dataGetMigration
	 * @param string $alias
	 * @param string $expected
	 */
	public function testGetMigration($alias, $expected) {
		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->setMethods(['getMigratedVersions', 'findMigrations'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();
		$this->migrationService->expects($this->any())->method('getMigratedVersions')->willReturn(
			['20170130180000', '20170130180001']
		);
		$this->migrationService->expects($this->any())->method('findMigrations')->willReturn(
			['20170130180000' => 'X', '20170130180001' => 'Y', '20170130180002' => 'Z', '20170130180003' => 'A']
		);

		$this->assertEquals(
			['20170130180000', '20170130180001', '20170130180002', '20170130180003'],
			$this->migrationService->getAvailableVersions());

		$migration = $this->migrationService->getMigration($alias);
		$this->assertEquals($expected, $migration);
	}

	public function testMigrate() {
		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->setMethods(['getMigratedVersions', 'findMigrations', 'executeStep'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();
		$this->migrationService->expects($this->any())->method('getMigratedVersions')->willReturn(
			['20170130180000', '20170130180001']
		);
		$this->migrationService->expects($this->any())->method('findMigrations')->willReturn(
			['20170130180000' => 'X', '20170130180001' => 'Y', '20170130180002' => 'Z', '20170130180003' => 'A']
		);

		$this->assertEquals(
			['20170130180000', '20170130180001', '20170130180002', '20170130180003'],
			$this->migrationService->getAvailableVersions());

		$this->migrationService->expects($this->exactly(2))->method('executeStep')
			->withConsecutive(['20170130180002'], ['20170130180003']);
		$this->migrationService->migrate();
	}
}
