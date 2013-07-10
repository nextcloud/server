<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

class TestTimedJob extends \OC\BackgroundJob\TimedJob {
	public function __construct() {
		$this->setInterval(10);
	}

	public function run($argument) {
		throw new JobRun(); //throw an exception so we can detect if this function is called
	}
}

class TimedJob extends \PHPUnit_Framework_TestCase {
	/**
	 * @var DummyJobList $jobList
	 */
	private $jobList;
	/**
	 * @var \OC\BackgroundJob\TimedJob $job
	 */
	private $job;

	public function setup() {
		$this->jobList = new DummyJobList();
		$this->job = new TestTimedJob();
		$this->jobList->add($this->job);
	}

	public function testShouldRunAfterInterval() {
		$this->job->setLastRun(time() - 12);
		try {
			$this->job->execute($this->jobList);
			$this->fail("job should have run");
		} catch (JobRun $e) {
		}
		$this->assertTrue(true);
	}

	public function testShouldNotRunWithinInterval() {
		$this->job->setLastRun(time() - 5);
		try {
			$this->job->execute($this->jobList);
		} catch (JobRun $e) {
			$this->fail("job should not have run");
		}
		$this->assertTrue(true);
	}

	public function testShouldNotTwice() {
		$this->job->setLastRun(time() - 15);
		try {
			$this->job->execute($this->jobList);
			$this->fail("job should have run the first time");
		} catch (JobRun $e) {
			try {
				$this->job->execute($this->jobList);
			} catch (JobRun $e) {
				$this->fail("job should not have run the second time");
			}
		}
		$this->assertTrue(true);
	}
}
