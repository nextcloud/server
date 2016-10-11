<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

class TestQueuedJob extends \OC\BackgroundJob\QueuedJob {
	private $testCase;

	/**
	 * @param QueuedJobTest $testCase
	 */
	public function __construct($testCase) {
		$this->testCase = $testCase;
	}

	public function run($argument) {
		$this->testCase->markRun();
	}
}

class QueuedJobTest extends \Test\TestCase {
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
		$this->job = new TestQueuedJob($this);
		$this->jobList->add($this->job);
		$this->jobRun = false;
	}

	public function testJobShouldBeRemoved() {
		$this->assertTrue($this->jobList->has($this->job, null));
		$this->job->execute($this->jobList);
		$this->assertTrue($this->jobRun);
	}
}
