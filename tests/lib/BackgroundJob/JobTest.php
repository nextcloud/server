<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Server;
use Psr\Log\LoggerInterface;

class JobTest extends \Test\TestCase {
	private $run = false;
	private ITimeFactory $timeFactory;
	private LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->run = false;
		$this->timeFactory = Server::get(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		\OC::$server->registerService(LoggerInterface::class, fn ($c) => $this->logger);
	}

	public function testRemoveAfterException(): void {
		$jobList = new DummyJobList();
		$e = new \Exception();
		$job = new TestJob($this->timeFactory, $this, function () use ($e): void {
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

	public function testRemoveAfterError(): void {
		$jobList = new DummyJobList();
		$job = new TestJob($this->timeFactory, $this, function (): void {
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
