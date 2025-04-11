<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\CleanupInvitationTokenJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Test\TestCase;

class CleanupInvitationTokenJobTest extends TestCase {
	/** @var IDBConnection | \PHPUnit\Framework\MockObject\MockObject */
	private $dbConnection;

	/** @var ITimeFactory | \PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;

	/** @var CleanupInvitationTokenJob */
	private $backgroundJob;

	protected function setUp(): void {
		parent::setUp();

		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->backgroundJob = new CleanupInvitationTokenJob(
			$this->dbConnection, $this->timeFactory);
	}

	public function testRun(): void {
		$this->timeFactory->expects($this->once())
			->method('getTime')
			->with()
			->willReturn(1337);

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$expr = $this->createMock(IExpressionBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->willReturn($queryBuilder);
		$queryBuilder->method('expr')
			->willReturn($expr);
		$queryBuilder->method('createNamedParameter')
			->willReturnMap([
				[1337, \PDO::PARAM_STR, null, 'namedParameter1337']
			]);

		$function = 'function1337';
		$expr->expects($this->once())
			->method('lt')
			->with('expiration', 'namedParameter1337')
			->willReturn($function);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->willReturn($queryBuilder);

		$queryBuilder->expects($this->once())
			->method('delete')
			->with('calendar_invitations')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->once())
			->method('where')
			->with($function)
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->once())
			->method('execute')
			->with()
			->willReturn($stmt);

		$this->backgroundJob->run([]);
	}
}
