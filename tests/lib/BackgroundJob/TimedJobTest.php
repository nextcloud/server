<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

class TestTimedJob extends \OC\BackgroundJob\TimedJob {
	private $testCase;

	/**
	 * @param TimedJobTest $testCase
	 */
	public function __construct($testCase) {
		$this->setInterval(10);
		$this->testCase = $testCase;
	}

	public function run($argument) {
		$this->testCase->markRun();
	}
}

class TimedJobTest extends \Test\TestCase {
	/**
	 * @var DummyJobList $jobList
	 */
	private $jobList;
	/**
	 * @var \OC\BackgroundJob\TimedJob $job
	 */
	private $job;

	private $jobRun = false;

	public function markRun() {
		$this->jobRun = true;
	}

	protected function setup() {
		parent::setUp();

		$this->jobList = new DummyJobList();
		$this->job = new TestTimedJob($this);
		$this->jobList->add($this->job);
		$this->jobRun = false;
	}

	public function testShouldRunAfterInterval() {
		$this->job->setLastRun(time() - 12);
		$this->job->execute($this->jobList);
		$this->assertTrue($this->jobRun);
	}

	public function testShouldNotRunWithinInterval() {
		$this->job->setLastRun(time() - 5);
		$this->job->execute($this->jobList);
		$this->assertFalse($this->jobRun);
	}

	public function testShouldNotTwice() {
		$this->job->setLastRun(time() - 15);
		$this->job->execute($this->jobList);
		$this->assertTrue($this->jobRun);
		$this->jobRun = false;
		$this->job->execute($this->jobList);
		$this->assertFalse($this->jobRun);
	}
}
