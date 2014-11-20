<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

class Job extends \Test\TestCase {
	private $run = false;

	protected function setUp() {
		parent::setUp();
		$this->run = false;
	}

	public function testRemoveAfterException() {
		$jobList = new DummyJobList();
		$job = new TestJob($this, function () {
			throw new \Exception();
		});
		$jobList->add($job);

		$this->assertCount(1, $jobList->getAll());
		$job->execute($jobList);
		$this->assertTrue($this->run);
		$this->assertCount(0, $jobList->getAll());
	}

	public function markRun() {
		$this->run = true;
	}
}
