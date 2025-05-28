<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\DAV\Migration;

use OCA\DAV\Migration\RemoveDeletedUsersCalendarSubscriptions;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IFunctionBuilder;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RemoveDeletedUsersCalendarSubscriptionsTest extends TestCase {
	private IDBConnection&MockObject $dbConnection;
	private IUserManager&MockObject $userManager;
	private IOutput&MockObject $output;
	private RemoveDeletedUsersCalendarSubscriptions $migration;


	protected function setUp(): void {
		parent::setUp();

		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->output = $this->createMock(IOutput::class);

		$this->migration = new RemoveDeletedUsersCalendarSubscriptions($this->dbConnection, $this->userManager);
	}

	public function testGetName(): void {
		$this->assertEquals(
			'Clean up old calendar subscriptions from deleted users that were not cleaned-up',
			$this->migration->getName()
		);
	}

	/**
	 * @dataProvider dataTestRun
	 */
	public function testRun(array $subscriptions, array $userExists, int $deletions): void {
		$qb = $this->createMock(IQueryBuilder::class);

		$qb->method('select')->willReturn($qb);

		$functionBuilder = $this->createMock(IFunctionBuilder::class);

		$qb->method('func')->willReturn($functionBuilder);
		$functionBuilder->method('count')->willReturn($this->createMock(IQueryFunction::class));

		$qb->method('selectDistinct')
			->with(['id', 'principaluri'])
			->willReturn($qb);

		$qb->method('from')
			->with('calendarsubscriptions')
			->willReturn($qb);

		$qb->method('setMaxResults')
			->willReturn($qb);

		$qb->method('setFirstResult')
			->willReturn($qb);

		$result = $this->createMock(IResult::class);

		$qb->method('execute')
			->willReturn($result);

		$result->expects($this->once())
			->method('fetchOne')
			->willReturn(count($subscriptions));

		$result
			->method('fetch')
			->willReturnOnConsecutiveCalls(...$subscriptions);

		$qb->method('delete')
			->with('calendarsubscriptions')
			->willReturn($qb);

		$expr = $this->createMock(IExpressionBuilder::class);

		$qb->method('expr')->willReturn($expr);
		$qb->method('createNamedParameter')->willReturn($this->createMock(IParameter::class));
		$qb->method('where')->willReturn($qb);
		// Only when user exists
		$qb->expects($this->exactly($deletions))->method('executeStatement');

		$this->dbConnection->method('getQueryBuilder')->willReturn($qb);


		$this->output->expects($this->once())->method('startProgress');

		$this->output->expects($subscriptions === [] ? $this->never(): $this->once())->method('advance');
		if (count($subscriptions)) {
			$this->userManager->method('userExists')
				->willReturnCallback(function (string $username) use ($userExists) {
					return $userExists[$username];
				});
		}
		$this->output->expects($this->once())->method('finishProgress');
		$this->output->expects($this->once())->method('info')->with(sprintf('%d calendar subscriptions without an user have been cleaned up', $deletions));

		$this->migration->run($this->output);
	}

	public static function dataTestRun(): array {
		return [
			[[], [], 0],
			[
				[
					[
						'id' => 1,
						'principaluri' => 'users/principals/foo1',
					],
					[
						'id' => 2,
						'principaluri' => 'users/principals/bar1',
					],
					[
						'id' => 3,
						'principaluri' => 'users/principals/bar1',
					],
					[],
				],
				['foo1' => true, 'bar1' => false],
				2
			],
		];
	}
}
