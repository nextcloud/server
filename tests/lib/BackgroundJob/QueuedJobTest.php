<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

class TestQueuedJobNew extends QueuedJob {
	public bool $ran = false;

	public function run($argument) {
		$this->ran = true;
	}
}

class QueuedJobTest extends \Test\TestCase {
	private DummyJobList $jobList;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = new DummyJobList();
	}

	public function testJobShouldBeRemovedNew() {
		$job = new TestQueuedJobNew(\OCP\Server::get(ITimeFactory::class));
		$job->setId(42);
		$this->jobList->add($job);

		$this->assertTrue($this->jobList->has($job, null));
		$job->start($this->jobList);
		$this->assertTrue($job->ran);
	}
}
