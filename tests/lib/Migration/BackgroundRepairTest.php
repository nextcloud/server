<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Migration;

use OC\BackgroundJob\JobList;
use OC\Migration\BackgroundRepair;
use OC\NeedsUpdateException;
use OC\Repair;
use OC\Repair\Events\RepairStepEvent;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class TestRepairStep implements IRepairStep {
	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'A test repair step';
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @since 9.1.0
	 * @throws \Exception in case of failure
	 */
	public function run(IOutput $output) {
		// TODO: Implement run() method.
	}
}

class BackgroundRepairTest extends TestCase {
	private JobList $jobList;
	private BackgroundRepair $job;
	private LoggerInterface $logger;
	private IEventDispatcher $dispatcher;
	private ITimeFactory $time;
	private Repair $repair;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->getMockBuilder(JobList::class)
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->time->method('getTime')
			->willReturn(999999);
		$this->repair = new Repair($this->dispatcher, $this->logger);
		$this->job = $this->getMockBuilder(BackgroundRepair::class)
			->setConstructorArgs([$this->repair, $this->time, $this->logger, $this->jobList])
			->setMethods(['loadApp'])
			->getMock();
	}

	public function testNoArguments(): void {
		$this->jobList->expects($this->once())->method('remove');
		$this->job->start($this->jobList);
	}

	public function testAppUpgrading(): void {
		$this->jobList->expects($this->never())->method('remove');
		$this->job->expects($this->once())->method('loadApp')->with('test')->willThrowException(new NeedsUpdateException());
		$this->job->setArgument([
			'app' => 'test',
			'step' => 'j'
		]);
		$this->job->start($this->jobList);
	}

	public function testUnknownStep(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$this->jobList->expects($this->once())->method('remove');
		$this->logger->expects($this->once())->method('error');

		$this->job->setArgument([
			'app' => 'test',
			'step' => 'j'
		]);
		$this->job->start($this->jobList);
	}

	public function testWorkingStep(): void {
		$this->dispatcher->expects($this->once())->method('dispatchTyped')
			->with($this->equalTo(new RepairStepEvent('A test repair step')));

		$this->jobList->expects($this->once())->method('remove');

		$this->job->setArgument([
			'app' => 'test',
			'step' => '\Test\Migration\TestRepairStep'
		]);
		$this->job->start($this->jobList);
	}
}
