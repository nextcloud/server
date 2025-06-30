<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\Repair;
use OC\Repair\Events\RepairErrorEvent;
use OC\Repair\Events\RepairInfoEvent;
use OC\Repair\Events\RepairStepEvent;
use OC\Repair\Events\RepairWarningEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Server;
use Psr\Log\LoggerInterface;

class TestRepairStep implements IRepairStep {
	public function __construct(
		private bool $warning = false,
	) {
	}

	public function getName() {
		return 'Test Name';
	}

	public function run(IOutput $out) {
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
		$dispatcher = Server::get(IEventDispatcher::class);
		$this->repair = new Repair($dispatcher, $this->createMock(LoggerInterface::class));

		$dispatcher->addListener(RepairWarningEvent::class, function (RepairWarningEvent $event): void {
			$this->outputArray[] = 'warning: ' . $event->getMessage();
		});
		$dispatcher->addListener(RepairInfoEvent::class, function (RepairInfoEvent $event): void {
			$this->outputArray[] = 'info: ' . $event->getMessage();
		});
		$dispatcher->addListener(RepairStepEvent::class, function (RepairStepEvent $event): void {
			$this->outputArray[] = 'step: ' . $event->getStepName();
		});
		$dispatcher->addListener(RepairErrorEvent::class, function (RepairErrorEvent $event): void {
			$this->outputArray[] = 'error: ' . $event->getMessage();
		});
	}

	public function testRunRepairStep(): void {
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

	public function testRunRepairStepThatFail(): void {
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

	public function testRunRepairStepsWithException(): void {
		$mock = $this->createMock(TestRepairStep::class);
		$mock->expects($this->any())
			->method('run')
			->willThrowException(new \Exception('Exception text'));
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

	public function testRunRepairStepsContinueAfterWarning(): void {
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
