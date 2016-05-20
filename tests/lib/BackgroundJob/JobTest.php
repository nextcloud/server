<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

class JobTest extends \Test\TestCase {
	private $run = false;

	protected function setUp() {
		parent::setUp();
		$this->run = false;
	}

	public function testRemoveAfterException() {
		$jobList = new DummyJobList();
		$e = new \Exception();
		$job = new TestJob($this, function () use ($e) {
			throw $e;
		});
		$jobList->add($job);

		$logger = $this->getMockBuilder('OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();
		$logger->expects($this->once())
			->method('logException')
			->with($e);

		$this->assertCount(1, $jobList->getAll());
		$job->execute($jobList, $logger);
		$this->assertTrue($this->run);
		$this->assertCount(1, $jobList->getAll());
	}

	public function markRun() {
		$this->run = true;
	}
}
