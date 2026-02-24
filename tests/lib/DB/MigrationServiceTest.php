<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use OC\DB\Connection;
use OC\DB\MigrationService;
use OC\DB\SchemaWrapper;
use OCP\App\AppPathNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IMigrationStep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class MigrationServiceTest
 *
 * @package Test\DB
 */
class MigrationServiceTest extends \Test\TestCase {
	private Connection&MockObject $db;

	private MigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->db = $this->createMock(Connection::class);
		$this->db
			->expects($this->any())
			->method('getPrefix')
			->willReturn('test_oc_');

		$this->migrationService = new MigrationService('testing', $this->db);
	}

	public function testGetters(): void {
		$this->assertEquals('testing', $this->migrationService->getApp());
		$this->assertEquals(\OC::$SERVERROOT . '/apps/testing/lib/Migration', $this->migrationService->getMigrationsDirectory());
		$this->assertEquals('OCA\Testing\Migration', $this->migrationService->getMigrationsNamespace());
		$this->assertEquals('test_oc_migrations', $this->migrationService->getMigrationsTableName());
	}

	public function testCore(): void {
		$migrationService = new MigrationService('core', $this->db);

		$this->assertEquals('core', $migrationService->getApp());
		$this->assertEquals(\OC::$SERVERROOT . '/core/Migrations', $migrationService->getMigrationsDirectory());
		$this->assertEquals('OC\Core\Migrations', $migrationService->getMigrationsNamespace());
		$this->assertEquals('test_oc_migrations', $migrationService->getMigrationsTableName());
	}

	public function testExecuteUnknownStep(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Version 20170130180000 is unknown.');

		$this->migrationService->executeStep('20170130180000');
	}

	public function testUnknownApp(): void {
		$this->expectException(AppPathNotFoundException::class);
		$this->expectExceptionMessage('Could not find path for unknown_bloody_app');

		new MigrationService('unknown_bloody_app', $this->db);
	}

	public function testExecuteStepWithUnknownClass(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Migration step \'X\' is unknown');

		$migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['findMigrations'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();
		$migrationService->expects($this->any())->method('findMigrations')->willReturn(
			['20170130180000' => 'X', '20170130180001' => 'Y', '20170130180002' => 'Z', '20170130180003' => 'A']
		);
		$migrationService->executeStep('20170130180000');
	}

	public function testExecuteStepWithSchemaChange(): void {
		$schema = $this->createMock(Schema::class);
		$this->db->expects($this->any())
			->method('createSchema')
			->willReturn($schema);

		$this->db->expects($this->once())
			->method('migrateToSchema');

		$qb = $this->createMock(IQueryBuilder::class);
		$qb
			->expects($this->once())
			->method('insert')
			->willReturn($qb);

		$this->db
			->expects($this->once())
			->method('getQueryBuilder')
			->willReturn($qb);

		$wrappedSchema = $this->createMock(Schema::class);
		$wrappedSchema->expects($this->atLeast(2))
			->method('getTables')
			->willReturn([]);
		$wrappedSchema->expects($this->atLeast(2))
			->method('getSequences')
			->willReturn([]);

		$schemaResult = $this->createMock(SchemaWrapper::class);
		$schemaResult->expects($this->once())
			->method('getWrappedSchema')
			->willReturn($wrappedSchema);

		$step = $this->createMock(IMigrationStep::class);
		$step->expects($this->once())
			->method('preSchemaChange');
		$step->expects($this->once())
			->method('changeSchema')
			->willReturn($schemaResult);
		$step->expects($this->once())
			->method('postSchemaChange');

		$migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['createInstance'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();

		$migrationService->expects($this->any())
			->method('createInstance')
			->with('20170130180000')
			->willReturn($step);

		$migrationService->executeStep('20170130180000');
	}

	public function testExecuteStepWithoutSchemaChange(): void {
		$schema = $this->createMock(Schema::class);
		$this->db->expects($this->any())
			->method('createSchema')
			->willReturn($schema);

		$this->db->expects($this->never())
			->method('migrateToSchema');

		$qb = $this->createMock(IQueryBuilder::class);
		$qb
			->expects($this->once())
			->method('insert')
			->willReturn($qb);

		$this->db
			->expects($this->once())
			->method('getQueryBuilder')
			->willReturn($qb);

		$step = $this->createMock(IMigrationStep::class);
		$step->expects($this->once())
			->method('preSchemaChange');
		$step->expects($this->once())
			->method('changeSchema')
			->willReturn(null);
		$step->expects($this->once())
			->method('postSchemaChange');

		$migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['createInstance'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();

		$migrationService->expects($this->any())
			->method('createInstance')
			->with('20170130180000')
			->willReturn($step);

		$migrationService->executeStep('20170130180000');
	}

	public static function dataGetMigration(): array {
		return [
			['current', '20170130180001'],
			['prev', '20170130180000'],
			['next', '20170130180002'],
			['latest', '20170130180003'],
		];
	}

	/**
	 * @param string $alias
	 * @param string $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetMigration')]
	public function testGetMigration($alias, $expected): void {
		$migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['getMigratedVersions', 'findMigrations'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();

		$migrationService->expects($this->any())->method('getMigratedVersions')->willReturn(
			['20170130180000', '20170130180001']
		);
		$migrationService->expects($this->any())->method('findMigrations')->willReturn(
			['20170130180000' => 'X', '20170130180001' => 'Y', '20170130180002' => 'Z', '20170130180003' => 'A']
		);

		$this->assertEquals(
			['20170130180000', '20170130180001', '20170130180002', '20170130180003'],
			$migrationService->getAvailableVersions());

		$migration = $migrationService->getMigration($alias);
		$this->assertEquals($expected, $migration);
	}

	public function testMigrate(): void {
		$migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['getMigratedVersions', 'findMigrations', 'executeStep'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();

		$migrationService->expects($this->any())->method('getMigratedVersions')->willReturn(
			['20170130180000', '20170130180001']
		);
		$migrationService->expects($this->any())->method('findMigrations')->willReturn(
			['20170130180000' => 'X', '20170130180001' => 'Y', '20170130180002' => 'Z', '20170130180003' => 'A']
		);

		$this->assertEquals(
			['20170130180000', '20170130180001', '20170130180002', '20170130180003'],
			$migrationService->getAvailableVersions());

		$calls = [];
		$migrationService
			->expects($this->exactly(2))
			->method('executeStep')
			->willReturnCallback(function (string $migration) use (&$calls) {
				$calls[] = $migration;
			});

		$migrationService->migrate();
		self::assertEquals(['20170130180002', '20170130180003'], $calls);
	}

	#[DataProvider('dataEnsureNamingConstraintsTableName')]
	public function testEnsureNamingConstraintsTableName(string $name, int $prefixLength, bool $tableExists, bool $throws): void {
		if ($throws) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$table = $this->createMock(Table::class);
		$table->expects($this->atLeastOnce())
			->method('getName')
			->willReturn($name);
		$table->expects($this->any())
			->method('getColumns')
			->willReturn([]);
		$table->expects($this->any())
			->method('getIndexes')
			->willReturn([]);
		$table->expects($this->any())
			->method('getForeignKeys')
			->willReturn([]);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects(self::once())
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willReturnCallback(fn () => match($tableExists) {
				false => throw new SchemaException(),
				true => $table,
			});
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureNamingConstraints($sourceSchema, $schema, $prefixLength);
	}

	public static function dataEnsureNamingConstraintsTableName(): array {
		return [
			'valid name' => [
				\str_repeat('x', 60), // table name
				3, // prefix length
				false, // has this table
				false, // throws
			],
			'valid name - long prefix' => [
				\str_repeat('x', 55),
				8,
				false,
				false,
			],
			'too long but not a new table' => [
				\str_repeat('x', 61),
				3,
				true,
				false,
			],
			'too long' => [
				\str_repeat('x', 61),
				3,
				false,
				true,
			],
			'too long with prefix' => [
				\str_repeat('x', 60),
				4,
				false,
				true,
			],
		];
	}

	#[DataProvider('dataEnsureNamingConstraintsPrimaryDefaultKey')]
	public function testEnsureNamingConstraintsPrimaryDefaultKey(string $tableName, int $prefixLength, string $platform, bool $throws): void {
		if ($throws) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$this->db->expects(self::atLeastOnce())
			->method('getDatabaseProvider')
			->willReturn($platform);

		$defaultName = match ($platform) {
			IDBConnection::PLATFORM_POSTGRES => $tableName . '_pkey',
			IDBConnection::PLATFORM_ORACLE => $tableName . '_seq',
			default => 'PRIMARY',
		};

		$index = $this->createMock(Index::class);
		$index->expects($this->any())
			->method('getName')
			->willReturn($defaultName);
		$index->expects($this->any())
			->method('getColumns')
			->willReturn([]);

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn($tableName);

		$table->expects($this->once())
			->method('getColumns')
			->willReturn([]);
		$table->expects($this->once())
			->method('getIndexes')
			->willReturn([]);
		$table->expects($this->once())
			->method('getForeignKeys')
			->willReturn([]);
		$table->expects($this->once())
			->method('getPrimaryKey')
			->willReturn($index);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects($this->atMost(1))
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureNamingConstraints($sourceSchema, $schema, $prefixLength);
	}

	public static function dataEnsureNamingConstraintsPrimaryDefaultKey(): array {
		foreach ([IDBConnection::PLATFORM_MYSQL, IDBConnection::PLATFORM_ORACLE, IDBConnection::PLATFORM_POSTGRES, IDBConnection::PLATFORM_SQLITE] as $engine) {
			$testcases["$engine valid"] = [
				str_repeat('x', 55),
				3,
				$engine,
				false,
			];
			$testcases["$engine too long"] = [
				str_repeat('x', 56), // 56 (name) + 3 (prefix) + 5 ('_pkey')= 64 > 63
				3,
				$engine,
				true,
			];
			$testcases["$engine too long prefix"] = [
				str_repeat('x', 55),
				4,
				$engine,
				true,
			];
		}
		return $testcases;
	}

	#[DataProvider('dataEnsureNamingConstraintsPrimaryCustomKey')]
	public function testEnsureNamingConstraintsPrimaryCustomKey(string $name, int $prefixLength, bool $newIndex, bool $throws): void {
		if ($throws) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$index = $this->createMock(Index::class);
		$index->expects($this->any())
			->method('getName')
			->willReturn($name);

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn('tablename');

		$table->expects($this->any())
			->method('getColumns')
			->willReturn([]);
		$table->expects($this->any())
			->method('getIndexes')
			->willReturn([]);
		$table->expects($this->any())
			->method('getForeignKeys')
			->willReturn([]);
		$table->expects($this->atLeastOnce())
			->method('getPrimaryKey')
			->willReturn($index);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects($this->once())
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willReturnCallback(fn () => match($newIndex) {
				true => throw new SchemaException(),
				false => $table,
			});
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureNamingConstraints($sourceSchema, $schema, $prefixLength);
	}

	public static function dataEnsureNamingConstraintsPrimaryCustomKey(): array {
		return [
			'valid name' => [
				str_repeat('x', 60),
				3,
				true,
				false,
			],
			'valid name - prefix does not matter' => [
				str_repeat('x', 63),
				3,
				true,
				false,
			],
			'invalid name - but not new' => [
				str_repeat('x', 64),
				3,
				false,
				false,
			],
			'too long name' => [
				str_repeat('x', 64),
				3,
				true,
				true,
			],
		];
	}

	#[DataProvider('dataEnsureNamingConstraints')]
	public function testEnsureNamingConstraintsColumnName(string $name, bool $throws): void {
		if ($throws) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$column = $this->createMock(Column::class);
		$column->expects(self::atLeastOnce())
			->method('getName')
			->willReturn($name);

		$table = $this->createMock(Table::class);
		$table->expects(self::any())
			->method('getName')
			->willReturn('valid');

		$table->expects(self::once())
			->method('getColumns')
			->willReturn([$column]);
		$table->expects(self::atMost(1))
			->method('getIndexes')
			->willReturn([]);
		$table->expects(self::atMost(1))
			->method('getForeignKeys')
			->willReturn([]);

		$schema = $this->createMock(Schema::class);
		$schema->expects(self::once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects(self::once())
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects(self::any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects(self::any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureNamingConstraints($sourceSchema, $schema, 3);
	}

	#[DataProvider('dataEnsureNamingConstraints')]
	public function testEnsureNamingConstraintsIndexName(string $name, bool $throws): void {
		if ($throws) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$index = $this->createMock(Index::class);
		$index->expects(self::atLeastOnce())
			->method('getName')
			->willReturn($name);

		$table = $this->createMock(Table::class);
		$table->expects(self::any())
			->method('getName')
			->willReturn('valid');

		$table->expects(self::atMost(1))
			->method('getColumns')
			->willReturn([]);
		$table->expects(self::once())
			->method('getIndexes')
			->willReturn([$index]);
		$table->expects(self::atMost(1))
			->method('getForeignKeys')
			->willReturn([]);

		$schema = $this->createMock(Schema::class);
		$schema->expects(self::once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects(self::once())
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects(self::any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects(self::any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureNamingConstraints($sourceSchema, $schema, 3);
	}

	#[DataProvider('dataEnsureNamingConstraints')]
	public function testEnsureNamingConstraintsForeignKeyName(string $name, bool $throws): void {
		if ($throws) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$foreignKey = $this->createMock(ForeignKeyConstraint::class);
		$foreignKey->expects(self::any())
			->method('getName')
			->willReturn($name);

		$table = $this->createMock(Table::class);
		$table->expects(self::any())
			->method('getName')
			->willReturn('valid');

		$table->expects(self::once())
			->method('getColumns')
			->willReturn([]);
		$table->expects(self::once())
			->method('getIndexes')
			->willReturn([]);
		$table->expects(self::once())
			->method('getForeignKeys')
			->willReturn([$foreignKey]);

		$schema = $this->createMock(Schema::class);
		$schema->expects(self::once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects(self::once())
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects(self::any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects(self::any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureNamingConstraints($sourceSchema, $schema, 3);
	}

	#[DataProvider('dataEnsureNamingConstraints')]
	public function testEnsureNamingConstraintsSequenceName(string $name, bool $throws): void {
		if ($throws) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$sequence = $this->createMock(Sequence::class);
		$sequence->expects($this->any())
			->method('getName')
			->willReturn($name);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([]);
		$schema->expects($this->once())
			->method('getSequences')
			->willReturn([$sequence]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureNamingConstraints($sourceSchema, $schema, 3);
	}

	public static function dataEnsureNamingConstraints(): array {
		return [
			'valid length' => [\str_repeat('x', 63), false],
			'too long' => [\str_repeat('x', 64), true],
		];
	}

	public function testEnsureOracleConstraintsValid(): void {
		$table = $this->createMock(Table::class);
		$table->expects($this->atLeastOnce())
			->method('getName')
			->willReturn('tablename');

		$primaryKey = $this->createMock(Index::class);
		$primaryKey->expects($this->once())
			->method('getName')
			->willReturn('primary_key');

		$column = $this->createMock(Column::class);
		$table->expects($this->once())
			->method('getColumns')
			->willReturn([$column]);
		$table->expects($this->once())
			->method('getPrimaryKey')
			->willReturn($primaryKey);

		$sequence = $this->createMock(Sequence::class);
		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects($this->once())
			->method('getSequences')
			->willReturn([$sequence]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureOracleConstraints($sourceSchema, $schema);
	}

	public function testEnsureOracleConstraintsValidWithPrimaryKey(): void {
		$index = $this->createMock(Index::class);
		$index->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 26));

		$table->expects($this->once())
			->method('getColumns')
			->willReturn([]);
		$table->expects($this->once())
			->method('getPrimaryKey')
			->willReturn($index);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects($this->once())
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureOracleConstraints($sourceSchema, $schema);
	}

	public function testEnsureOracleConstraintsValidWithPrimaryKeyDefault(): void {
		$defaultName = 'PRIMARY';
		if ($this->db->getDatabaseProvider() === IDBConnection::PLATFORM_POSTGRES) {
			$defaultName = \str_repeat('a', 26) . '_' . \str_repeat('b', 30) . '_seq';
		} elseif ($this->db->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) {
			$defaultName = \str_repeat('a', 26) . '_seq';
		}

		$index = $this->createMock(Index::class);
		$index->expects($this->any())
			->method('getName')
			->willReturn($defaultName);
		$index->expects($this->any())
			->method('getColumns')
			->willReturn([\str_repeat('b', 30)]);

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 25));

		$table->expects($this->once())
			->method('getColumns')
			->willReturn([]);
		$table->expects($this->once())
			->method('getPrimaryKey')
			->willReturn($index);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects($this->once())
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureOracleConstraints($sourceSchema, $schema);
	}

	public function testEnsureOracleConstraintsNoPrimaryKey(): void {
		$this->markTestSkipped('Test disabled for now due to multiple reasons, see https://github.com/nextcloud/server/pull/31580#issuecomment-1069182234 for details.');
		$this->expectException(\InvalidArgumentException::class);

		$table = $this->createMock(Table::class);
		$table->expects($this->atLeastOnce())
			->method('getName')
			->willReturn('tablename');
		$table->expects($this->once())
			->method('getColumns')
			->willReturn([]);
		$table->expects($this->once())
			->method('getPrimaryKey')
			->willReturn(null);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects($this->once())
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureOracleConstraints($sourceSchema, $schema);
	}

	/**
	 * Alternative for testEnsureOracleConstraintsNoPrimaryKey until we enforce it.
	 */
	public function testEnsureOracleConstraintsNoPrimaryKeyLogging(): void {
		$table = $this->createMock(Table::class);
		$table->expects($this->atLeastOnce())
			->method('getName')
			->willReturn('tablename');
		$table->expects($this->once())
			->method('getColumns')
			->willReturn([]);
		$table->expects($this->once())
			->method('getPrimaryKey')
			->willReturn(null);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);
		$schema->expects($this->once())
			->method('getSequences')
			->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())
			->method('error');
		$this->overwriteService(LoggerInterface::class, $logger);

		$this->migrationService->ensureOracleConstraints($sourceSchema, $schema);
	}

	#[TestWith([true])]
	#[TestWith([false])]
	public function testEnsureOracleConstraintsBooleanNotNull(bool $isOracle): void {
		$this->db->method('getDatabaseProvider')
			->willReturn($isOracle ? IDBConnection::PLATFORM_ORACLE : IDBConnection::PLATFORM_MARIADB);

		$column = $this->createMock(Column::class);
		$column->expects($this->any())
			->method('getName')
			->willReturn('aaaa');
		$column->expects($this->any())
			->method('getType')
			->willReturn(Type::getType('boolean'));
		$column->expects($this->any())
			->method('getNotnull')
			->willReturn(true);

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn('tablename');
		$table->method('getIndexes')->willReturn([]);
		$table->method('getForeignKeys')->willReturn([]);

		$table->expects($this->once())
			->method('getColumns')
			->willReturn([$column]);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);
		$schema->method('getSequences')->willReturn([]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		if ($isOracle) {
			$column->expects($this->once())
				->method('setNotnull')
				->with(false);
		} else {
			$column->expects($this->never())
				->method('setNotnull');
		}

		$this->migrationService->ensureOracleConstraints($sourceSchema, $schema);
	}

	public function testEnsureOracleConstraintsStringLength4000(): void {
		$this->expectException(\InvalidArgumentException::class);

		$column = $this->createMock(Column::class);
		$column->expects($this->any())
			->method('getName')
			->willReturn('aaaa');
		$column->expects($this->any())
			->method('getType')
			->willReturn(Type::getType('string'));
		$column->expects($this->any())
			->method('getLength')
			->willReturn(4001);

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn('tablename');

		$table->expects($this->once())
			->method('getColumns')
			->willReturn([$column]);

		$schema = $this->createMock(Schema::class);
		$schema->expects($this->once())
			->method('getTables')
			->willReturn([$table]);

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		$this->migrationService->ensureOracleConstraints($sourceSchema, $schema);
	}
}
