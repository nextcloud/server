<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Server;

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

	public function testJobShouldBeRemovedNew(): void {
		$job = new TestQueuedJobNew(Server::get(ITimeFactory::class));
		$job->setId(42);
		$this->jobList->add($job);

		$this->assertTrue($this->jobList->has($job, null));
		$job->start($this->jobList);
		$this->assertTrue($job->ran);
	}
}
