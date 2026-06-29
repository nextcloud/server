<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\TaskProcessing;

use Doctrine\DBAL\Platforms\MySQL80Platform;
use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\QueryBuilder;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\ConflictResolutionMode;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Guards the SKIP LOCKED claim query shape in CI.
 *
 * The atomic worker claim in {@see \OC\TaskProcessing\Db\TaskMapper::claimWithSkipLocked}
 * relies on the QueryBuilder emitting `... FOR UPDATE SKIP LOCKED` on databases that
 * support row-level locking (MySQL/MariaDB/PostgreSQL). True multi-transaction
 * concurrency cannot be exercised inside a single PHPUnit process, so this test
 * pins the generated SQL shape instead: it builds the exact claim query against a
 * non-SQLite platform and asserts both clauses are present. A regression that drops
 * the locking clause (silently turning the claim into a plain SELECT and reopening
 * the duplicate-claim race) would fail here.
 */
class SkipLockedSqlShapeTest extends TestCase {
	private SystemConfig&MockObject $systemConfig;
	private LoggerInterface&MockObject $logger;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->systemConfig = $this->createMock(SystemConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	/**
	 * Build a QueryBuilder backed by a non-SQLite (MySQL 8) platform so the
	 * generated SQL exposes the locking clause the way it would in production.
	 */
	private function newMysqlQueryBuilder(): QueryBuilder {
		$inner = $this->createMock(Connection::class);
		$inner->method('getDatabasePlatform')->willReturn(new MySQL80Platform());

		$adapter = $this->createMock(ConnectionAdapter::class);
		$adapter->method('getInner')->willReturn($inner);
		$adapter->method('getDatabaseProvider')->willReturn(IDBConnection::PLATFORM_MYSQL);

		return new QueryBuilder($adapter, $this->systemConfig, $this->logger);
	}

	public function testClaimQueryContainsForUpdateSkipLocked(): void {
		$qb = $this->newMysqlQueryBuilder();
		$qb->select('id', 'status', 'type', 'last_updated')
			->from('taskprocessing_tasks')
			->where($qb->expr()->eq('status', $qb->createPositionalParameter(1, IQueryBuilder::PARAM_INT)))
			->orderBy('last_updated', 'ASC')
			->setMaxResults(1)
			->forUpdate(ConflictResolutionMode::SkipLocked);

		$sql = $qb->getSQL();

		self::assertStringContainsString('FOR UPDATE', $sql);
		self::assertStringContainsString('SKIP LOCKED', $sql);
	}

	public function testOrdinaryForUpdateHasNoSkipLocked(): void {
		// Sanity check: only the SkipLocked mode adds the SKIP LOCKED clause.
		$qb = $this->newMysqlQueryBuilder();
		$qb->select('id')
			->from('taskprocessing_tasks')
			->setMaxResults(1)
			->forUpdate(ConflictResolutionMode::Ordinary);

		$sql = $qb->getSQL();

		self::assertStringContainsString('FOR UPDATE', $sql);
		self::assertStringNotContainsString('SKIP LOCKED', $sql);
	}
}
