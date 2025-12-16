<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use OC\BackgroundJob\JobList;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Snowflake\IGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class JobList
 *
 * @package Test\BackgroundJob
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class JobListTest extends TestCase {
	protected JobList $instance;
	protected IDBConnection $connection;
	protected IConfig&MockObject $config;
	protected ITimeFactory&MockObject $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->clearJobsList();
		$this->config = $this->createMock(IConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->instance = new JobList(
			$this->connection,
			$this->config,
			$this->timeFactory,
			Server::get(LoggerInterface::class),
			Server::get(IGenerator::class),
		);
	}

	protected function clearJobsList(): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete('jobs');
		$query->executeStatement();
	}

	protected function getAllSorted(): array {
		$iterator = $this->instance->getJobsIterator(null, null, 0);
		$jobs = [];

		foreach ($iterator as $job) {
			$jobs[] = clone $job;
		}

		return $jobs;
	}

	public static function argumentProvider(): array {
		return [
			[null],
			[false],
			['foobar'],
			[12],
			[[
				'asd' => 5,
				'foo' => 'bar'
			]]
		];
	}

	#[DataProvider('argumentProvider')]
	public function testAddRemove(mixed $argument): void {
		$existingJobs = $this->getAllSorted();
		$job = new TestJob();
		$this->instance->add($job, $argument);

		$jobs = $this->getAllSorted();

		$this->assertCount(count($existingJobs) + 1, $jobs);
		$addedJob = $jobs[count($jobs) - 1];
		$this->assertInstanceOf('\Test\BackgroundJob\TestJob', $addedJob);
		$this->assertEquals($argument, $addedJob->getArgument());

		$this->instance->remove($job, $argument);

		$jobs = $this->getAllSorted();
		$this->assertEquals($existingJobs, $jobs);
	}

	#[DataProvider('argumentProvider')]
	public function testRemoveDifferentArgument(mixed $argument): void {
		$existingJobs = $this->getAllSorted();
		$job = new TestJob();
		$this->instance->add($job, $argument);

		$jobs = $this->getAllSorted();
		$this->instance->remove($job, 10);
		$jobs2 = $this->getAllSorted();

		$this->assertEquals($jobs, $jobs2);

		$this->instance->remove($job, $argument);

		$jobs = $this->getAllSorted();
		$this->assertEquals($existingJobs, $jobs);
	}

	#[DataProvider('argumentProvider')]
	public function testHas(mixed $argument): void {
		$job = new TestJob();
		$this->assertFalse($this->instance->has($job, $argument));
		$this->instance->add($job, $argument);

		$this->assertTrue($this->instance->has($job, $argument));

		$this->instance->remove($job, $argument);

		$this->assertFalse($this->instance->has($job, $argument));
	}

	#[DataProvider('argumentProvider')]
	public function testHasDifferentArgument(mixed $argument): void {
		$job = new TestJob();
		$this->instance->add($job, $argument);

		$this->assertFalse($this->instance->has($job, 10));
	}

	protected function createTempJob($class,
		$argument,
		int $reservedTime = 0,
		int $lastChecked = 0,
		int $lastRun = 0): string {
		if ($lastChecked === 0) {
			$lastChecked = time();
		}
		$id = Server::get(IGenerator::class)->nextId();

		$query = $this->connection->getQueryBuilder();
		$query->insert('jobs')
			->values([
				'id' => $query->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				'class' => $query->createNamedParameter($class),
				'argument' => $query->createNamedParameter($argument),
				'last_run' => $query->createNamedParameter($lastRun, IQueryBuilder::PARAM_INT),
				'last_checked' => $query->createNamedParameter($lastChecked, IQueryBuilder::PARAM_INT),
				'reserved_at' => $query->createNamedParameter($reservedTime, IQueryBuilder::PARAM_INT),
			]);
		$query->executeStatement();
		return $id;
	}

	public function testGetNext(): void {
		$job = new TestJob();
		$this->createTempJob(get_class($job), 1, 0, 12345);
		$this->createTempJob(get_class($job), 2, 0, 12346);

		$jobs = $this->getAllSorted();
		$savedJob1 = $jobs[0];

		$this->timeFactory->expects($this->atLeastOnce())
			->method('getTime')
			->willReturn(123456789);
		$nextJob = $this->instance->getNext();

		$this->assertEquals($savedJob1, $nextJob);
	}

	public function testGetNextSkipReserved(): void {
		$job = new TestJob();
		$this->createTempJob(get_class($job), 1, 123456789, 12345);
		$this->createTempJob(get_class($job), 2, 0, 12346);

		$this->timeFactory->expects($this->atLeastOnce())
			->method('getTime')
			->willReturn(123456789);
		$nextJob = $this->instance->getNext();

		$this->assertEquals(get_class($job), get_class($nextJob));
		$this->assertEquals(2, $nextJob->getArgument());
	}

	public function testGetNextSkipTimed(): void {
		$job = new TestTimedJobNew($this->timeFactory);
		$jobId = $this->createTempJob(get_class($job), 1, 123456789, 12345, 123456789 - 5);
		$this->timeFactory->expects(self::atLeastOnce())
			->method('getTime')
			->willReturn(123456789);

		$nextJob = $this->instance->getNext();

		self::assertNull($nextJob);
		$job = $this->instance->getById($jobId);
		self::assertInstanceOf(TestTimedJobNew::class, $job);
		self::assertEquals(123456789 - 5, $job->getLastRun());
	}

	public function testGetNextSkipNonExisting(): void {
		$job = new TestJob();
		$this->createTempJob('\OC\Non\Existing\Class', 1, 0, 12345);
		$this->createTempJob(get_class($job), 2, 0, 12346);

		$this->timeFactory->expects($this->atLeastOnce())
			->method('getTime')
			->willReturn(123456789);
		$nextJob = $this->instance->getNext();

		$this->assertEquals(get_class($job), get_class($nextJob));
		$this->assertEquals(2, $nextJob->getArgument());
	}

	/**
	 * @param $argument
	 */
	#[DataProvider('argumentProvider')]
	public function testGetById($argument): void {
		$job = new TestJob();
		$this->instance->add($job, $argument);

		$jobs = $this->getAllSorted();

		$addedJob = $jobs[count($jobs) - 1];

		$this->assertEquals($addedJob, $this->instance->getById($addedJob->getId()));
	}

	public function testSetLastRun(): void {
		$job = new TestJob();
		$this->instance->add($job);

		$jobs = $this->getAllSorted();

		$addedJob = $jobs[count($jobs) - 1];

		$timeStart = time();
		$this->instance->setLastRun($addedJob);
		$timeEnd = time();

		$addedJob = $this->instance->getById($addedJob->getId());

		$this->assertGreaterThanOrEqual($timeStart, $addedJob->getLastRun());
		$this->assertLessThanOrEqual($timeEnd, $addedJob->getLastRun());
	}

	public function testHasReservedJobs(): void {
		$this->clearJobsList();

		$this->timeFactory->expects($this->atLeastOnce())
			->method('getTime')
			->willReturn(123456789);

		$job = new TestJob($this->timeFactory, $this, function (): void {
		});

		$job2 = new TestJob($this->timeFactory, $this, function (): void {
		});

		$this->instance->add($job, 1);
		$this->instance->add($job2, 2);

		$this->assertCount(2, iterator_to_array($this->instance->getJobsIterator(null, 10, 0)));

		$this->assertFalse($this->instance->hasReservedJob());
		$this->assertFalse($this->instance->hasReservedJob(TestJob::class));

		$job = $this->instance->getNext();
		$this->assertNotNull($job);
		$this->assertTrue($this->instance->hasReservedJob());
		$this->assertTrue($this->instance->hasReservedJob(TestJob::class));
		$job = $this->instance->getNext();
		$this->assertNotNull($job);
		$this->assertTrue($this->instance->hasReservedJob());
		$this->assertTrue($this->instance->hasReservedJob(TestJob::class));
	}

	public function testHasReservedJobsAndParallelAwareJob(): void {
		$this->clearJobsList();

		$this->timeFactory->expects($this->atLeastOnce())
			->method('getTime')
			->willReturnCallback(function () use (&$time) {
				return time();
			});

		$job = new TestParallelAwareJob($this->timeFactory, $this, function (): void {
		});

		$job2 = new TestParallelAwareJob($this->timeFactory, $this, function (): void {
		});

		$this->instance->add($job, 1);
		$this->instance->add($job2, 2);

		$this->assertCount(2, iterator_to_array($this->instance->getJobsIterator(null, 10, 0)));

		$this->assertFalse($this->instance->hasReservedJob());
		$this->assertFalse($this->instance->hasReservedJob(TestParallelAwareJob::class));

		$job = $this->instance->getNext();
		$this->assertNotNull($job);
		$this->assertTrue($this->instance->hasReservedJob());
		$this->assertTrue($this->instance->hasReservedJob(TestParallelAwareJob::class));
		$job = $this->instance->getNext();
		$this->assertNull($job); // Job doesn't allow parallel runs
	}
}
