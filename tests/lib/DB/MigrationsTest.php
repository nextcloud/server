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
use OC\Migration\MetadataManager;
use OCP\App\IAppManager;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\DropTable;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IMigrationStep;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class MigrationsTest
 *
 * @package Test\DB
 */
class MigrationsTest extends \Test\TestCase {
	private MigrationService|MockObject $migrationService;
	private MockObject|IDBConnection $db;
	private IAppManager $appManager;

	protected function setUp(): void {
		parent::setUp();

		$this->db = $this->createMock(Connection::class);
		$this->db->expects($this->any())->method('getPrefix')->willReturn('test_oc_');
		$this->migrationService = new MigrationService('testing', $this->db);

		$this->appManager = Server::get(IAppManager::class);
	}

	public function testGetters(): void {
		$this->assertEquals('testing', $this->migrationService->getApp());
		$this->assertEquals(\OC::$SERVERROOT . '/apps/testing/lib/Migration', $this->migrationService->getMigrationsDirectory());
		$this->assertEquals('OCA\Testing\Migration', $this->migrationService->getMigrationsNamespace());
		$this->assertEquals('test_oc_migrations', $this->migrationService->getMigrationsTableName());
	}

	public function testCore(): void {
		$this->migrationService = new MigrationService('core', $this->db);

		$this->assertEquals('core', $this->migrationService->getApp());
		$this->assertEquals(\OC::$SERVERROOT . '/core/Migrations', $this->migrationService->getMigrationsDirectory());
		$this->assertEquals('OC\Core\Migrations', $this->migrationService->getMigrationsNamespace());
		$this->assertEquals('test_oc_migrations', $this->migrationService->getMigrationsTableName());
	}


	public function testExecuteUnknownStep(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Version 20170130180000 is unknown.');

		$this->migrationService->executeStep('20170130180000');
	}


	public function testUnknownApp(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('App not found');

		$migrationService = new MigrationService('unknown-bloody-app', $this->db);
	}


	public function testExecuteStepWithUnknownClass(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Migration step \'X\' is unknown');

		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['findMigrations'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();
		$this->migrationService->expects($this->any())->method('findMigrations')->willReturn(
			['20170130180000' => 'X', '20170130180001' => 'Y', '20170130180002' => 'Z', '20170130180003' => 'A']
		);
		$this->migrationService->executeStep('20170130180000');
	}

	public function testExecuteStepWithSchemaChange(): void {
		$schema = $this->createMock(Schema::class);
		$this->db->expects($this->any())
			->method('createSchema')
			->willReturn($schema);

		$this->db->expects($this->once())
			->method('migrateToSchema');

		$wrappedSchema = $this->createMock(Schema::class);
		$wrappedSchema->expects($this->exactly(2))
			->method('getTables')
			->willReturn([]);
		$wrappedSchema->expects($this->exactly(2))
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

		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['createInstance'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();

		$this->migrationService->expects($this->any())
			->method('createInstance')
			->with('20170130180000')
			->willReturn($step);
		$this->migrationService->executeStep('20170130180000');
	}

	public function testExecuteStepWithoutSchemaChange(): void {
		$schema = $this->createMock(Schema::class);
		$this->db->expects($this->any())
			->method('createSchema')
			->willReturn($schema);

		$this->db->expects($this->never())
			->method('migrateToSchema');

		$step = $this->createMock(IMigrationStep::class);
		$step->expects($this->once())
			->method('preSchemaChange');
		$step->expects($this->once())
			->method('changeSchema')
			->willReturn(null);
		$step->expects($this->once())
			->method('postSchemaChange');

		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['createInstance'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();

		$this->migrationService->expects($this->any())
			->method('createInstance')
			->with('20170130180000')
			->willReturn($step);
		$this->migrationService->executeStep('20170130180000');
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
	 * @dataProvider dataGetMigration
	 * @param string $alias
	 * @param string $expected
	 */
	public function testGetMigration($alias, $expected): void {
		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['getMigratedVersions', 'findMigrations'])
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

	public function testMigrate(): void {
		$this->migrationService = $this->getMockBuilder(MigrationService::class)
			->onlyMethods(['getMigratedVersions', 'findMigrations', 'executeStep'])
			->setConstructorArgs(['testing', $this->db])
			->getMock();
		$this->migrationService->method('getMigratedVersions')
			->willReturn(
				['20170130180000', '20170130180001']
			);
		$this->migrationService->method('findMigrations')
			->willReturn(
				['20170130180000' => 'X', '20170130180001' => 'Y', '20170130180002' => 'Z', '20170130180003' => 'A']
			);

		$this->assertEquals(
			['20170130180000', '20170130180001', '20170130180002', '20170130180003'],
			$this->migrationService->getAvailableVersions()
		);

		$calls = [
			['20170130180002', false],
			['20170130180003', false],
		];
		$this->migrationService->expects($this->exactly(2))
			->method('executeStep')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});
		$this->migrationService->migrate();
	}

	public function testEnsureOracleConstraintsValid(): void {
		$column = $this->createMock(Column::class);
		$column->expects($this->once())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

		$index = $this->createMock(Index::class);
		$index->expects($this->once())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

		$foreignKey = $this->createMock(ForeignKeyConstraint::class);
		$foreignKey->expects($this->once())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

		$table = $this->createMock(Table::class);
		$table->expects($this->atLeastOnce())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

		$sequence = $this->createMock(Sequence::class);
		$sequence->expects($this->atLeastOnce())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

		$primaryKey = $this->createMock(Index::class);
		$primaryKey->expects($this->once())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

		$table->expects($this->once())
			->method('getColumns')
			->willReturn([$column]);
		$table->expects($this->once())
			->method('getIndexes')
			->willReturn([$index]);
		$table->expects($this->once())
			->method('getForeignKeys')
			->willReturn([$foreignKey]);
		$table->expects($this->once())
			->method('getPrimaryKey')
			->willReturn($primaryKey);

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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testEnsureOracleConstraintsTooLongTableName(): void {
		$this->expectException(\InvalidArgumentException::class);

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 31));

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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testEnsureOracleConstraintsTooLongPrimaryWithDefault(): void {
		$this->expectException(\InvalidArgumentException::class);

		$defaultName = 'PRIMARY';
		if ($this->db->getDatabaseProvider() === IDBConnection::PLATFORM_POSTGRES) {
			$defaultName = \str_repeat('a', 27) . '_' . \str_repeat('b', 30) . '_seq';
		} elseif ($this->db->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) {
			$defaultName = \str_repeat('a', 27) . '_seq';
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
			->willReturn(\str_repeat('a', 27));

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

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testEnsureOracleConstraintsTooLongPrimaryWithName(): void {
		$this->expectException(\InvalidArgumentException::class);

		$index = $this->createMock(Index::class);
		$index->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 31));

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 26));

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

		$sourceSchema = $this->createMock(Schema::class);
		$sourceSchema->expects($this->any())
			->method('getTable')
			->willThrowException(new SchemaException());
		$sourceSchema->expects($this->any())
			->method('hasSequence')
			->willReturn(false);

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testEnsureOracleConstraintsTooLongColumnName(): void {
		$this->expectException(\InvalidArgumentException::class);

		$column = $this->createMock(Column::class);
		$column->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 31));

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testEnsureOracleConstraintsTooLongIndexName(): void {
		$this->expectException(\InvalidArgumentException::class);

		$index = $this->createMock(Index::class);
		$index->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 31));

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

		$table->expects($this->once())
			->method('getColumns')
			->willReturn([]);
		$table->expects($this->once())
			->method('getIndexes')
			->willReturn([$index]);

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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testEnsureOracleConstraintsTooLongForeignKeyName(): void {
		$this->expectException(\InvalidArgumentException::class);

		$foreignKey = $this->createMock(ForeignKeyConstraint::class);
		$foreignKey->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 31));

		$table = $this->createMock(Table::class);
		$table->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 30));

		$table->expects($this->once())
			->method('getColumns')
			->willReturn([]);
		$table->expects($this->once())
			->method('getIndexes')
			->willReturn([]);
		$table->expects($this->once())
			->method('getForeignKeys')
			->willReturn([$foreignKey]);

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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testEnsureOracleConstraintsNoPrimaryKey(): void {
		$this->markTestSkipped('Test disabled for now due to multiple reasons, see https://github.com/nextcloud/server/pull/31580#issuecomment-1069182234 for details.');
		$this->expectException(\InvalidArgumentException::class);

		$table = $this->createMock(Table::class);
		$table->expects($this->atLeastOnce())
			->method('getName')
			->willReturn(\str_repeat('a', 30));
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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testEnsureOracleConstraintsTooLongSequenceName(): void {
		$this->expectException(\InvalidArgumentException::class);

		$sequence = $this->createMock(Sequence::class);
		$sequence->expects($this->any())
			->method('getName')
			->willReturn(\str_repeat('a', 31));

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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testEnsureOracleConstraintsBooleanNotNull(): void {
		$this->expectException(\InvalidArgumentException::class);

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
			->willReturn(\str_repeat('a', 30));

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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
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
			->willReturn(\str_repeat('a', 30));

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

		self::invokePrivate($this->migrationService, 'ensureOracleConstraints', [$sourceSchema, $schema, 3]);
	}


	public function testExtractMigrationAttributes(): void {
		$metadataManager = Server::get(MetadataManager::class);
		$this->appManager->loadApp('testing');

		$this->assertEquals($this->getMigrationMetadata(), json_decode(json_encode($metadataManager->extractMigrationAttributes('testing')), true));

		$this->appManager->disableApp('testing');
	}

	public function testDeserializeMigrationMetadata(): void {
		$metadataManager = Server::get(MetadataManager::class);
		$this->assertEquals(
			[
				'core' => [],
				'apps' => [
					'testing' => [
						'30000Date20240102030405' => [
							new DropTable('old_table'),
							new CreateTable('new_table',
								description: 'Table is used to store things, but also to get more things',
								notes:       ['this is a notice', 'and another one, if really needed']
							),
							new AddColumn('my_table'),
							new AddColumn('my_table', 'another_field'),
							new AddColumn('other_table', 'last_one', ColumnType::DATE),
							new AddIndex('my_table'),
							new AddIndex('my_table', IndexType::PRIMARY),
							new DropColumn('other_table'),
							new DropColumn('other_table', 'old_column',
								description: 'field is not used anymore and replaced by \'last_one\''
							),
							new DropIndex('other_table'),
							new ModifyColumn('other_table'),
							new ModifyColumn('other_table', 'this_field'),
							new ModifyColumn('other_table', 'this_field', ColumnType::BIGINT)
						]
					]
				]
			],
			$metadataManager->getMigrationsAttributesFromReleaseMetadata(
				[
					'core' => [],
					'apps' => ['testing' => $this->getMigrationMetadata()]
				]
			)
		);
	}

	private function getMigrationMetadata(): array {
		return [
			'30000Date20240102030405' => [
				[
					'class' => 'OCP\\Migration\\Attributes\\DropTable',
					'table' => 'old_table',
					'description' => '',
					'notes' => [],
					'columns' => []
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\CreateTable',
					'table' => 'new_table',
					'description' => 'Table is used to store things, but also to get more things',
					'notes' =>
						[
							'this is a notice',
							'and another one, if really needed'
						],
					'columns' => []
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddColumn',
					'table' => 'my_table',
					'description' => '',
					'notes' => [],
					'name' => '',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddColumn',
					'table' => 'my_table',
					'description' => '',
					'notes' => [],
					'name' => 'another_field',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => 'last_one',
					'type' => 'date'
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddIndex',
					'table' => 'my_table',
					'description' => '',
					'notes' => [],
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddIndex',
					'table' => 'my_table',
					'description' => '',
					'notes' => [],
					'type' => 'primary'
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\DropColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => '',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\DropColumn',
					'table' => 'other_table',
					'description' => 'field is not used anymore and replaced by \'last_one\'',
					'notes' => [],
					'name' => 'old_column',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\DropIndex',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\ModifyColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => '',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\ModifyColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => 'this_field',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\ModifyColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => 'this_field',
					'type' => 'bigint'
				],
			]
		];
	}
}
