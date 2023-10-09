<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\Repair;
use OC\Repair\Events\RepairErrorEvent;
use OC\Repair\Events\RepairInfoEvent;
use OC\Repair\Events\RepairStepEvent;
use OC\Repair\Events\RepairWarningEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class TestRepairStep implements IRepairStep {
	private bool $warning;

	public function __construct(bool $warning = false) {
		$this->warning = $warning;
	}

	public function getName() {
		return 'Test Name';
	}

	public function run(\OCP\Migration\IOutput $out) {
		if ($this->warning) {
			$out->warning('Simulated warning');
		} else {
			$out->info('Simulated info');
		}
	}
}

class RepairTest extends TestCase {
	private Repair $repair;

	/** @var string[] */
	private array $outputArray = [];

	protected function setUp(): void {
		parent::setUp();
		$dispatcher = \OC::$server->get(IEventDispatcher::class);
		$this->repair = new \OC\Repair([], $dispatcher, $this->createMock(LoggerInterface::class));

		$dispatcher->addListener(RepairWarningEvent::class, function (RepairWarningEvent $event) {
			$this->outputArray[] = 'warning: ' . $event->getMessage();
		});
		$dispatcher->addListener(RepairInfoEvent::class, function (RepairInfoEvent $event) {
			$this->outputArray[] = 'info: ' . $event->getMessage();
		});
		$dispatcher->addListener(RepairStepEvent::class, function (RepairStepEvent $event) {
			$this->outputArray[] = 'step: ' . $event->getStepName();
		});
		$dispatcher->addListener(RepairErrorEvent::class, function (RepairErrorEvent $event) {
			$this->outputArray[] = 'error: ' . $event->getMessage();
		});
	}

	public function testRunRepairStep() {
		$this->repair->addStep(new TestRepairStep(false));
		$this->repair->run();

		$this->assertEquals(
			[
				'step: Test Name',
				'info: Simulated info',
			],
			$this->outputArray
		);
	}

	public function testRunRepairStepThatFail() {
		$this->repair->addStep(new TestRepairStep(true));
		$this->repair->run();

		$this->assertEquals(
			[
				'step: Test Name',
				'warning: Simulated warning',
			],
			$this->outputArray
		);
	}

	public function testRunRepairStepsWithException() {
		$mock = $this->createMock(TestRepairStep::class);
		$mock->expects($this->any())
			->method('run')
			->will($this->throwException(new \Exception('Exception text')));
		$mock->expects($this->any())
			->method('getName')
			->willReturn('Exception Test');

		$this->repair->addStep($mock);
		$this->repair->addStep(new TestRepairStep(false));

		$thrown = false;
		try {
			$this->repair->run();
		} catch (\Exception $e) {
			$thrown = true;
		}

		$this->assertFalse($thrown);
		// jump out after exception
		$this->assertEquals(
			[
				'step: Exception Test',
				'error: Exception text',
				'step: Test Name',
				'info: Simulated info',
			],
			$this->outputArray
		);
	}

	public function testRunRepairStepsContinueAfterWarning() {
		$this->repair->addStep(new TestRepairStep(true));
		$this->repair->addStep(new TestRepairStep(false));
		$this->repair->run();

		$this->assertEquals(
			[
				'step: Test Name',
				'warning: Simulated warning',
				'step: Test Name',
				'info: Simulated info',
			],
			$this->outputArray
		);
	}
}
