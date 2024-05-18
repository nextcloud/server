<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;

class JobTest extends \Test\TestCase {
	private $run = false;
	private ITimeFactory $timeFactory;
	private LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->run = false;
		$this->timeFactory = \OCP\Server::get(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		\OC::$server->registerService(LoggerInterface::class, fn ($c) => $this->logger);
	}

	public function testRemoveAfterException() {
		$jobList = new DummyJobList();
		$e = new \Exception();
		$job = new TestJob($this->timeFactory, $this, function () use ($e) {
			throw $e;
		});
		$jobList->add($job);

		$this->logger->expects($this->once())
			->method('error');

		$this->assertCount(1, $jobList->getAll());
		$job->start($jobList);
		$this->assertTrue($this->run);
		$this->assertCount(1, $jobList->getAll());
	}

	public function testRemoveAfterError() {
		$jobList = new DummyJobList();
		$job = new TestJob($this->timeFactory, $this, function () {
			$test = null;
			$test->someMethod();
		});
		$jobList->add($job);

		$this->logger->expects($this->once())
			->method('error');

		$this->assertCount(1, $jobList->getAll());
		$job->start($jobList);
		$this->assertTrue($this->run);
		$this->assertCount(1, $jobList->getAll());
	}

	public function markRun() {
		$this->run = true;
	}
}
