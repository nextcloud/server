<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class TestTimedJobNew extends TimedJob {
	public bool $ran = false;

	public function __construct(ITimeFactory $timeFactory) {
		parent::__construct($timeFactory);
		$this->setInterval(10);
	}

	public function run($argument) {
		$this->ran = true;
	}
}

class TimedJobTest extends \Test\TestCase {
	private DummyJobList $jobList;
	private ITimeFactory $time;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = new DummyJobList();
		$this->time = \OCP\Server::get(ITimeFactory::class);
	}

	public function testShouldRunAfterIntervalNew() {
		$job = new TestTimedJobNew($this->time);
		$job->setId(42);
		$this->jobList->add($job);

		$job->setLastRun(time() - 12);
		$job->start($this->jobList);
		$this->assertTrue($job->ran);
	}

	public function testShouldNotRunWithinIntervalNew() {
		$job = new TestTimedJobNew($this->time);
		$job->setId(42);
		$this->jobList->add($job);

		$job->setLastRun(time() - 5);
		$job->start($this->jobList);
		$this->assertFalse($job->ran);
	}

	public function testShouldNotTwiceNew() {
		$job = new TestTimedJobNew($this->time);
		$job->setId(42);
		$this->jobList->add($job);

		$job->setLastRun(time() - 15);
		$job->start($this->jobList);
		$this->assertTrue($job->ran);
		$job->ran = false;
		$job->start($this->jobList);
		$this->assertFalse($job->ran);
	}
}
