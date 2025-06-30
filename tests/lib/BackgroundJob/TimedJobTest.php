<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Server;

class TimedJobTest extends \Test\TestCase {
	private DummyJobList $jobList;
	private ITimeFactory $time;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = new DummyJobList();
		$this->time = Server::get(ITimeFactory::class);
	}

	public function testShouldRunAfterIntervalNew(): void {
		$job = new TestTimedJobNew($this->time);
		$job->setId(42);
		$this->jobList->add($job);

		$job->setLastRun(time() - 12);
		$job->start($this->jobList);
		$this->assertTrue($job->ran);
	}

	public function testShouldNotRunWithinIntervalNew(): void {
		$job = new TestTimedJobNew($this->time);
		$job->setId(42);
		$this->jobList->add($job);

		$job->setLastRun(time() - 5);
		$job->start($this->jobList);
		$this->assertFalse($job->ran);
	}

	public function testShouldNotTwiceNew(): void {
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
