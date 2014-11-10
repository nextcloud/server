<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

class JobList extends \Test\TestCase {
	/**
	 * @var \OC\BackgroundJob\JobList
	 */
	protected $instance;

	/**
	 * @var \OCP\IConfig | \PHPUnit_Framework_MockObject_MockObject $config
	 */
	protected $config;

	protected function setUp() {
		parent::setUp();

		$conn = \OC::$server->getDatabaseConnection();
		$this->config = $this->getMock('\OCP\IConfig');
		$this->instance = new \OC\BackgroundJob\JobList($conn, $this->config);
	}

	public function argumentProvider() {
		return array(
			array(null),
			array(false),
			array('foobar'),
			array(12),
			array(array(
				'asd' => 5,
				'foo' => 'bar'
			))
		);
	}

	/**
	 * @dataProvider argumentProvider
	 * @param $argument
	 */
	public function testAddRemove($argument) {
		$existingJobs = $this->instance->getAll();
		$job = new TestJob();
		$this->instance->add($job, $argument);

		$jobs = $this->instance->getAll();

		$this->assertCount(count($existingJobs) + 1, $jobs);
		$addedJob = $jobs[count($jobs) - 1];
		$this->assertInstanceOf('\Test\BackgroundJob\TestJob', $addedJob);
		$this->assertEquals($argument, $addedJob->getArgument());

		$this->instance->remove($job, $argument);

		$jobs = $this->instance->getAll();
		$this->assertEquals($existingJobs, $jobs);
	}

	/**
	 * @dataProvider argumentProvider
	 * @param $argument
	 */
	public function testRemoveDifferentArgument($argument) {
		$existingJobs = $this->instance->getAll();
		$job = new TestJob();
		$this->instance->add($job, $argument);

		$jobs = $this->instance->getAll();
		$this->instance->remove($job, 10);
		$jobs2 = $this->instance->getAll();

		$this->assertEquals($jobs, $jobs2);

		$this->instance->remove($job, $argument);

		$jobs = $this->instance->getAll();
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

		$this->instance->remove($job, $argument);
	}

	public function testGetLastJob() {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('backgroundjob', 'lastjob', 0)
			->will($this->returnValue(15));

		$this->assertEquals(15, $this->instance->getLastJob());
	}

	public function testGetNext() {
		$job = new TestJob();
		$this->instance->add($job, 1);
		$this->instance->add($job, 2);

		$jobs = $this->instance->getAll();

		$savedJob1 = $jobs[count($jobs) - 2];
		$savedJob2 = $jobs[count($jobs) - 1];

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('backgroundjob', 'lastjob', 0)
			->will($this->returnValue($savedJob1->getId()));

		$nextJob = $this->instance->getNext();

		$this->assertEquals($savedJob2, $nextJob);

		$this->instance->remove($job, 1);
		$this->instance->remove($job, 2);
	}

	public function testGetNextWrapAround() {
		$job = new TestJob();
		$this->instance->add($job, 1);
		$this->instance->add($job, 2);

		$jobs = $this->instance->getAll();

		$savedJob2 = $jobs[count($jobs) - 1];

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('backgroundjob', 'lastjob', 0)
			->will($this->returnValue($savedJob2->getId()));

		$nextJob = $this->instance->getNext();

		$this->assertEquals($jobs[0], $nextJob);

		$this->instance->remove($job, 1);
		$this->instance->remove($job, 2);
	}

	/**
	 * @dataProvider argumentProvider
	 * @param $argument
	 */
	public function testGetById($argument) {
		$job = new TestJob();
		$this->instance->add($job, $argument);

		$jobs = $this->instance->getAll();

		$addedJob = $jobs[count($jobs) - 1];

		$this->assertEquals($addedJob, $this->instance->getById($addedJob->getId()));

		$this->instance->remove($job, $argument);
	}

	public function testSetLastRun() {
		$job = new TestJob();
		$this->instance->add($job);

		$jobs = $this->instance->getAll();

		$addedJob = $jobs[count($jobs) - 1];

		$timeStart = time();
		$this->instance->setLastRun($addedJob);
		$timeEnd = time();

		$addedJob = $this->instance->getById($addedJob->getId());

		$this->assertGreaterThanOrEqual($timeStart, $addedJob->getLastRun());
		$this->assertLessThanOrEqual($timeEnd, $addedJob->getLastRun());

		$this->instance->remove($job);
	}
}
