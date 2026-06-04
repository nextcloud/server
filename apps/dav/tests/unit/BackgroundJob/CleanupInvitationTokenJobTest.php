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
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CleanupInvitationTokenJobTest extends TestCase {
	private IDBConnection&MockObject $dbConnection;
	private ITimeFactory&MockObject $timeFactory;
	private CleanupInvitationTokenJob $backgroundJob;

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
			->method('executeStatement')
			->with()
			->willReturn(1);

		$this->backgroundJob->run([]);
	}
}
