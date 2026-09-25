<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\Sequence;
use OC\DB\Connection;
use OC\DB\PgSqlTools;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PgSqlToolsTest extends TestCase {
	private IConfig&MockObject $config;
	private Connection&MockObject $conn;
	private PgSqlTools $pgSqlTools;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValueString')
			->with('dbtableprefix', 'oc_')
			->willReturn('oc_');
		$this->conn = $this->createMock(Connection::class);
		$this->pgSqlTools = new PgSqlTools($this->config);
	}

	public function testOrphanedSequenceIsSkipped(): void {
		$schemaManager = $this->createMock(PostgreSQLSchemaManager::class);
		$schemaManager->method('listSequences')->willReturn([
			new Sequence('oc_preview_locations_id_seq'),
		]);

		$configuration = $this->createMock(\Doctrine\DBAL\Configuration::class);
		$this->conn->method('getConfiguration')->willReturn($configuration);
		$this->conn->method('createSchemaManager')->willReturn($schemaManager);
		$this->conn->method('getDatabase')->willReturn('nextcloud');

		$infoResult = $this->createMock(Result::class);
		$infoResult->method('fetchAssociative')->willReturn(false);

		$this->conn->expects($this->once())
			->method('executeQuery')
			->willReturn($infoResult);

		$this->pgSqlTools->resynchronizeDatabaseSequences($this->conn);
	}

	public function testSequenceWithValidColumnIsSynced(): void {
		$schemaManager = $this->createMock(PostgreSQLSchemaManager::class);
		$schemaManager->method('listSequences')->willReturn([
			new Sequence('oc_users_id_seq'),
		]);

		$configuration = $this->createMock(\Doctrine\DBAL\Configuration::class);
		$this->conn->method('getConfiguration')->willReturn($configuration);
		$this->conn->method('createSchemaManager')->willReturn($schemaManager);
		$this->conn->method('getDatabase')->willReturn('nextcloud');

		$infoResult = $this->createMock(Result::class);
		$infoResult->method('fetchAssociative')->willReturn([
			'table_schema' => 'public',
			'table_name' => 'oc_users',
			'column_name' => 'id',
		]);

		$maxResult = $this->createMock(Result::class);
		$maxResult->method('fetchOne')->willReturn(42);

		$matcher = $this->exactly(3);
		$this->conn->expects($matcher)
			->method('executeQuery')
			->willReturnCallback(function (string $sql) use ($matcher, $infoResult, $maxResult) {
				match ($matcher->numberOfInvocations()) {
					1 => $this->assertStringContainsString('information_schema', $sql),
					2 => $this->assertStringContainsString('MAX(id)', $sql),
					3 => $this->assertStringContainsString("setval('oc_users_id_seq', 42)", $sql),
				};
				return match ($matcher->numberOfInvocations()) {
					1 => $infoResult,
					2 => $maxResult,
					3 => $this->createMock(Result::class),
				};
			});

		$this->pgSqlTools->resynchronizeDatabaseSequences($this->conn);
	}

	public function testEmptyTableDoesNotResetSequence(): void {
		$schemaManager = $this->createMock(PostgreSQLSchemaManager::class);
		$schemaManager->method('listSequences')->willReturn([
			new Sequence('oc_empty_table_id_seq'),
		]);

		$configuration = $this->createMock(\Doctrine\DBAL\Configuration::class);
		$this->conn->method('getConfiguration')->willReturn($configuration);
		$this->conn->method('createSchemaManager')->willReturn($schemaManager);
		$this->conn->method('getDatabase')->willReturn('nextcloud');

		$infoResult = $this->createMock(Result::class);
		$infoResult->method('fetchAssociative')->willReturn([
			'table_schema' => 'public',
			'table_name' => 'oc_empty_table',
			'column_name' => 'id',
		]);

		$maxResult = $this->createMock(Result::class);
		$maxResult->method('fetchOne')->willReturn(null);

		$matcher = $this->exactly(2);
		$this->conn->expects($matcher)
			->method('executeQuery')
			->willReturnCallback(function () use ($matcher, $infoResult, $maxResult) {
				return match ($matcher->numberOfInvocations()) {
					1 => $infoResult,
					2 => $maxResult,
				};
			});

		$this->pgSqlTools->resynchronizeDatabaseSequences($this->conn);
	}

	public function testMultipleSequencesMixedState(): void {
		$schemaManager = $this->createMock(PostgreSQLSchemaManager::class);
		$schemaManager->method('listSequences')->willReturn([
			new Sequence('oc_orphaned_id_seq'),
			new Sequence('oc_valid_id_seq'),
			new Sequence('oc_another_orphan_id_seq'),
		]);

		$configuration = $this->createMock(\Doctrine\DBAL\Configuration::class);
		$this->conn->method('getConfiguration')->willReturn($configuration);
		$this->conn->method('createSchemaManager')->willReturn($schemaManager);
		$this->conn->method('getDatabase')->willReturn('nextcloud');

		$orphanResult = $this->createMock(Result::class);
		$orphanResult->method('fetchAssociative')->willReturn(false);

		$validInfoResult = $this->createMock(Result::class);
		$validInfoResult->method('fetchAssociative')->willReturn([
			'table_schema' => 'public',
			'table_name' => 'oc_valid',
			'column_name' => 'id',
		]);

		$maxResult = $this->createMock(Result::class);
		$maxResult->method('fetchOne')->willReturn(10);

		$callCount = 0;
		$this->conn->method('executeQuery')
			->willReturnCallback(function (string $sql) use (&$callCount, $orphanResult, $validInfoResult, $maxResult) {
				$callCount++;
				if (str_contains($sql, 'information_schema')) {
					if ($callCount === 1 || $callCount === 5) {
						return $orphanResult;
					}
					return $validInfoResult;
				}
				if (str_contains($sql, 'MAX')) {
					return $maxResult;
				}
				return $this->createMock(Result::class);
			});

		$this->pgSqlTools->resynchronizeDatabaseSequences($this->conn);
	}
}
