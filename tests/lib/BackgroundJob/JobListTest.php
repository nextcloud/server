<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class JobList
 *
 * @group DB
 * @package Test\BackgroundJob
 */
class JobListTest extends TestCase {
	/** @var \OC\BackgroundJob\JobList */
	protected $instance;

	/** @var \OCP\IDBConnection */
	protected $connection;

	/** @var \OCP\IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var \OCP\AppFramework\Utility\ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $timeFactory;
	private bool $ran = false;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->clearJobsList();
		$this->config = $this->createMock(IConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->instance = new \OC\BackgroundJob\JobList(
			$this->connection,
			$this->config,
			$this->timeFactory,
			\OC::$server->get(LoggerInterface::class),
		);
	}

	protected function clearJobsList() {
		$query = $this->connection->getQueryBuilder();
		$query->delete('jobs');
		$query->execute();
	}

	protected function getAllSorted() {
		$iterator = $this->instance->getJobsIterator(null, null, 0);
		$jobs = [];

		foreach ($iterator as $job) {
			$jobs[] = clone $job;
		}

		usort($jobs, function (IJob $job1, IJob $job2) {
			return $job1->getId() - $job2->getId();
		});

		return $jobs;
	}

	public function argumentProvider() {
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

	/**
	 * @dataProvider argumentProvider
	 * @param $argument
	 */
	public function testAddRemove($argument) {
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

	/**
	 * @dataProvider argumentProvider
	 * @param $argument
	 */
	public function testRemoveDifferentArgument($argument) {
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

	/**
	 * @dataProvider argumentProvider
	 * @param $argument
	 */
	public function testHas($argument) {
		$job = new TestJob();
		$this->assertFalse($this->instance->has($job, $argument));
		$this->instance->add($job, $argument);

		$this->assertTrue($this->instance->has($job, $argument));

		$this->instance->remove($job, $argument);

		$this->assertFalse($this->instance->has($job, $argument));
	}

	/**
	 * @dataProvider argumentProvider
	 * @param $argument
	 */
	public function testHasDifferentArgument($argument) {
		$job = new TestJob();
		$this->instance->add($job, $argument);

		$this->assertFalse($this->instance->has($job, 10));
	}

	protected function createTempJob($class, $argument, $reservedTime = 0, $lastChecked = 0) {
		if ($lastChecked === 0) {
			$lastChecked = time();
		}

		$query = $this->connection->getQueryBuilder();
		$query->insert('jobs')
			->values([
				'class' => $query->createNamedParameter($class),
				'argument' => $query->createNamedParameter($argument),
				'last_run' => $query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
				'last_checked' => $query->createNamedParameter($lastChecked, IQueryBuilder::PARAM_INT),
				'reserved_at' => $query->createNamedParameter($reservedTime, IQueryBuilder::PARAM_INT),
			]);
		$query->execute();
	}

	public function testGetNext() {
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

	public function testGetNextSkipReserved() {
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

	public function testGetNextSkipNonExisting() {
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
	 * @dataProvider argumentProvider
	 * @param $argument
	 */
	public function testGetById($argument) {
		$job = new TestJob();
		$this->instance->add($job, $argument);

		$jobs = $this->getAllSorted();

		$addedJob = $jobs[count($jobs) - 1];

		$this->assertEquals($addedJob, $this->instance->getById($addedJob->getId()));
	}

	public function testSetLastRun() {
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

	public function testHasReservedJobs() {
		$this->clearJobsList();

		$this->timeFactory->expects($this->atLeastOnce())
			->method('getTime')
			->willReturn(123456789);

		$job = new TestJob($this->timeFactory, $this, function () {
		});

		$job2 = new TestJob($this->timeFactory, $this, function () {
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

	public function testHasReservedJobsAndParallelAwareJob() {
		$this->clearJobsList();

		$this->timeFactory->expects($this->atLeastOnce())
			->method('getTime')
			->willReturnCallback(function () use (&$time) {
				return time();
			});

		$job = new TestParallelAwareJob($this->timeFactory, $this, function () {
		});

		$job2 = new TestParallelAwareJob($this->timeFactory, $this, function () {
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

	public function markRun() {
		$this->ran = true;
	}
}
