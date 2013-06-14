<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

class TestQueuedJob extends \OC\BackgroundJob\QueuedJob {
	public function run($argument) {
		throw new JobRun(); //throw an exception so we can detect if this function is called
	}
}

class QueuedJob extends \PHPUnit_Framework_TestCase {
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
		$this->job = new TestQueuedJob();
		$this->jobList->add($this->job);
	}

	public function testJobShouldBeRemoved() {
		try {
			$this->assertTrue($this->jobList->has($this->job, null));
			$this->job->execute($this->jobList);
			$this->fail("job should have been run");
		} catch (JobRun $e) {
			$this->assertFalse($this->jobList->has($this->job, null));
		}
	}
}
