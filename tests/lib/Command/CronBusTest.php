<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Command;

use OC\Command\CronBus;
use OCP\BackgroundJob\IJobList;
use Test\BackgroundJob\DummyJobList;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class CronBusTest extends AsyncBusTestCase {
	/**
	 * @var IJobList
	 */
	private $jobList;


	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->jobList = new DummyJobList();
	}

	#[\Override]
	protected function createBus() {
		return new CronBus($this->jobList);
	}

	#[\Override]
	protected function runJobs() {
		$jobs = $this->jobList->getAll();
		foreach ($jobs as $job) {
			$job->start($this->jobList);
		}
	}
}
