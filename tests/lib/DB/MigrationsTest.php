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
use OCP\IDBConnection;
use OCP\Migration\ISchemaMigration;
use OCP\Migration\ISqlMigration;

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
		$this->assertEquals(\OC::$SERVERROOT . '/apps/testing/appinfo/Migrations', $this->migrationService->getMigrationsDirectory());
		$this->assertEquals('OCA\testing\Migrations', $this->migrationService->getMigrationsNamespace());
		$this->assertEquals('test_oc_migrations', $this->migrationService->getMigrationsTableName());
	}

	public function testCore() {
		$this->migrationService = new MigrationService('core', $this->db);

		$this->assertEquals('core', $this->migrationService->getApp());
		$this->assertEquals(\OC::$SERVERROOT . '/core/Migrations', $this->migrationService->getMigrationsDirectory());
		$this->assertEquals('OC\Migrations', $this->migrationService->getMigrationsNamespace());
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

	public function testExecuteStepWithSchemaMigrationStep() {

		$schema = $this->createMock(Schema::class);
		$this->db->expects($this->any())->method('createSchema')->willReturn($schema);

		$step = $this->createMock(ISchemaMigration::class);
		$step->expects($this->once())->method('changeSchema');
		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->setMethods(['createInstance'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();
		$this->migrationService->expects($this->any())->method('createInstance')->with('20170130180000')->willReturn($step);
		$this->migrationService->executeStep('20170130180000');
	}

	public function testExecuteStepWithSqlMigrationStep() {

		$this->db->expects($this->exactly(3))->method('executeQuery')->withConsecutive(['1'], ['2'], ['3']);

		$step = $this->createMock(ISqlMigration::class);
		$step->expects($this->once())->method('sql')->willReturn(['1', '2', '3']);
		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->setMethods(['createInstance'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();
		$this->migrationService->expects($this->any())->method('createInstance')->with('20170130180000')->willReturn($step);
		$this->migrationService->executeStep('20170130180000');
	}

	public function testGetMigration() {
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

		$migration = $this->migrationService->getMigration('current');
		$this->assertEquals('20170130180001', $migration);
		$migration = $this->migrationService->getMigration('prev');
		$this->assertEquals('20170130180000', $migration);
		$migration = $this->migrationService->getMigration('next');
		$this->assertEquals('20170130180002', $migration);
		$migration = $this->migrationService->getMigration('latest');
		$this->assertEquals('20170130180003', $migration);
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
