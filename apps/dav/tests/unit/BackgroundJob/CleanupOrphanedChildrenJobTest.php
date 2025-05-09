<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\CleanupOrphanedChildrenJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CleanupOrphanedChildrenJobTest extends TestCase {
	private CleanupOrphanedChildrenJob $job;

	private ITimeFactory&MockObject $timeFactory;
	private IDBConnection&MockObject $connection;
	private LoggerInterface&MockObject $logger;
	private IJobList&MockObject $jobList;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->job = new CleanupOrphanedChildrenJob(
			$this->timeFactory,
			$this->connection,
			$this->logger,
			$this->jobList,
		);
	}

	private function getArgument(): array {
		return [
			'childTable' => 'childTable',
			'parentTable' => 'parentTable',
			'parentId' => 'parentId',
			'logMessage' => 'logMessage',
		];
	}

	private function getMockQueryBuilder(): IQueryBuilder&MockObject {
		$expr = $this->createMock(IExpressionBuilder::class);
		$qb = $this->createMock(IQueryBuilder::class);
		$qb->method('select')
			->willReturnSelf();
		$qb->method('from')
			->willReturnSelf();
		$qb->method('leftJoin')
			->willReturnSelf();
		$qb->method('where')
			->willReturnSelf();
		$qb->method('setMaxResults')
			->willReturnSelf();
		$qb->method('andWhere')
			->willReturnSelf();
		$qb->method('expr')
			->willReturn($expr);
		$qb->method('delete')
			->willReturnSelf();
		return $qb;
	}

	public function testRunWithoutOrphans(): void {
		$argument = $this->getArgument();
		$selectQb = $this->getMockQueryBuilder();
		$result = $this->createMock(IResult::class);

		$this->connection->expects(self::once())
			->method('getQueryBuilder')
			->willReturn($selectQb);
		$selectQb->expects(self::once())
			->method('executeQuery')
			->willReturn($result);
		$result->expects(self::once())
			->method('fetchAll')
			->willReturn([]);
		$result->expects(self::once())
			->method('closeCursor');
		$this->jobList->expects(self::never())
			->method('add');

		self::invokePrivate($this->job, 'run', [$argument]);
	}

	public function testRunWithPartialBatch(): void {
		$argument = $this->getArgument();
		$selectQb = $this->getMockQueryBuilder();
		$deleteQb = $this->getMockQueryBuilder();
		$result = $this->createMock(IResult::class);

		$qbInvocationCount = self::exactly(2);
		$this->connection->expects($qbInvocationCount)
			->method('getQueryBuilder')
			->willReturnCallback(function () use ($qbInvocationCount, $selectQb, $deleteQb) {
				return match ($qbInvocationCount->getInvocationCount()) {
					1 => $selectQb,
					2 => $deleteQb,
				};
			});
		$selectQb->expects(self::once())
			->method('executeQuery')
			->willReturn($result);
		$result->expects(self::once())
			->method('fetchAll')
			->willReturn([
				['id' => 42],
				['id' => 43],
			]);
		$result->expects(self::once())
			->method('closeCursor');
		$deleteQb->expects(self::once())
			->method('delete')
			->willReturnSelf();
		$deleteQb->expects(self::once())
			->method('executeStatement');
		$this->jobList->expects(self::never())
			->method('add');

		self::invokePrivate($this->job, 'run', [$argument]);
	}

	public function testRunWithFullBatch(): void {
		$argument = $this->getArgument();
		$selectQb = $this->getMockQueryBuilder();
		$deleteQb = $this->getMockQueryBuilder();
		$result = $this->createMock(IResult::class);

		$qbInvocationCount = self::exactly(2);
		$this->connection->expects($qbInvocationCount)
			->method('getQueryBuilder')
			->willReturnCallback(function () use ($qbInvocationCount, $selectQb, $deleteQb) {
				return match ($qbInvocationCount->getInvocationCount()) {
					1 => $selectQb,
					2 => $deleteQb,
				};
			});
		$selectQb->expects(self::once())
			->method('executeQuery')
			->willReturn($result);
		$result->expects(self::once())
			->method('fetchAll')
			->willReturn(array_map(static fn ($i) => ['id' => 42 + $i], range(0, 999)));
		$result->expects(self::once())
			->method('closeCursor');
		$deleteQb->expects(self::once())
			->method('delete')
			->willReturnSelf();
		$deleteQb->expects(self::once())
			->method('executeStatement');
		$this->jobList->expects(self::once())
			->method('add')
			->with(CleanupOrphanedChildrenJob::class, $argument);

		self::invokePrivate($this->job, 'run', [$argument]);
	}
}
