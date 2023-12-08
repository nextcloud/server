<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Migration;

use OC\BackgroundJob\JobList;
use OC\Migration\BackgroundRepair;
use OC\NeedsUpdateException;
use OC\Repair\Events\RepairStepEvent;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use PHPUnit\Framework\MockObject\MockObject;
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
	/** @var JobList|MockObject */
	private $jobList;

	/** @var BackgroundRepair|MockObject */
	private $job;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IEventDispatcher|MockObject $dispatcher  */
	private $dispatcher;

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject $dispatcher  */
	private $time;

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
		$this->job = $this->getMockBuilder(BackgroundRepair::class)
			->setConstructorArgs([$this->dispatcher, $this->time, $this->logger, $this->jobList])
			->setMethods(['loadApp'])
			->getMock();
	}

	public function testNoArguments() {
		$this->jobList->expects($this->once())->method('remove');
		$this->job->start($this->jobList);
	}

	public function testAppUpgrading() {
		$this->jobList->expects($this->never())->method('remove');
		$this->job->expects($this->once())->method('loadApp')->with('test')->willThrowException(new NeedsUpdateException());
		$this->job->setArgument([
			'app' => 'test',
			'step' => 'j'
		]);
		$this->job->start($this->jobList);
	}

	public function testUnknownStep() {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$this->jobList->expects($this->once())->method('remove');
		$this->logger->expects($this->once())->method('error');

		$this->job->setArgument([
			'app' => 'test',
			'step' => 'j'
		]);
		$this->job->start($this->jobList);
	}

	public function testWorkingStep() {
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
