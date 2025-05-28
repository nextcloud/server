<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\DAV\Migration;

use OCA\DAV\BackgroundJob\RefreshWebcalJob;
use OCA\DAV\Migration\RefreshWebcalJobRegistrar;
use OCP\BackgroundJob\IJobList;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RefreshWebcalJobRegistrarTest extends TestCase {
	private IDBConnection&MockObject $db;
	private IJobList&MockObject $jobList;
	private RefreshWebcalJobRegistrar $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->db = $this->createMock(IDBConnection::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->migration = new RefreshWebcalJobRegistrar($this->db, $this->jobList);
	}

	public function testGetName(): void {
		$this->assertEquals($this->migration->getName(), 'Registering background jobs to update cache for webcal calendars');
	}

	public function testRun(): void {
		$output = $this->createMock(IOutput::class);

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$statement = $this->createMock(IResult::class);

		$this->db->expects($this->once())
			->method('getQueryBuilder')
			->willReturn($queryBuilder);

		$queryBuilder->expects($this->once())
			->method('select')
			->with(['principaluri', 'uri'])
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->once())
			->method('from')
			->with('calendarsubscriptions')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->once())
			->method('execute')
			->willReturn($statement);

		$statement->expects($this->exactly(4))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->willReturnOnConsecutiveCalls(
				[
					'principaluri' => 'foo1',
					'uri' => 'bar1',
				],
				[
					'principaluri' => 'foo2',
					'uri' => 'bar2',
				],
				[
					'principaluri' => 'foo3',
					'uri' => 'bar3',
				],
				null
			);

		$this->jobList->expects($this->exactly(3))
			->method('has')
			->willReturnMap([
				[RefreshWebcalJob::class, [
					'principaluri' => 'foo1',
					'uri' => 'bar1',
				], false],
				[RefreshWebcalJob::class, [
					'principaluri' => 'foo2',
					'uri' => 'bar2',
				], true ],
				[RefreshWebcalJob::class, [
					'principaluri' => 'foo3',
					'uri' => 'bar3',
				], false],
			]);

		$calls = [
			[RefreshWebcalJob::class, [
				'principaluri' => 'foo1',
				'uri' => 'bar1',
			]],
			[RefreshWebcalJob::class, [
				'principaluri' => 'foo3',
				'uri' => 'bar3',
			]]
		];
		$this->jobList->expects($this->exactly(2))
			->method('add')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$output->expects($this->once())
			->method('info')
			->with('Added 2 background jobs to update webcal calendars');

		$this->migration->run($output);
	}
}
