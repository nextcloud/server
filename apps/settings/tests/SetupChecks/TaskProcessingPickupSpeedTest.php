<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\TaskProcessingPickupSpeed;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use OCP\SetupCheck\SetupResult;
use OCP\TaskProcessing\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TaskProcessingPickupSpeedTest extends TestCase {
	private IL10N&MockObject $l10n;
	private ITimeFactory&MockObject $timeFactory;
	private IManager&MockObject $taskProcessingManager;

	private TaskProcessingPickupSpeed $check;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->taskProcessingManager = $this->createMock(IManager::class);

		$this->check = new TaskProcessingPickupSpeed(
			$this->l10n,
			$this->taskProcessingManager,
			$this->timeFactory,
		);
	}

	/**
	 * @param int $taskCount Tasks scheduled in the window
	 * @param int $slowCount Tasks of those that were picked up too late
	 */
	private function mockCounts(int $taskCount, int $slowCount): void {
		$this->taskProcessingManager->method('countTasks')
			->willReturnCallback(function (?int $status = null, array $taskTypeIds = [], ?int $scheduleAfter = null, ?int $minPickupDelay = null) use ($taskCount, $slowCount): int {
				if ($minPickupDelay === null) {
					return $taskCount;
				}
				$this->assertSame(TaskProcessingPickupSpeed::MAX_PICKUP_DELAY, $minPickupDelay);
				return $slowCount;
			});
	}

	public function testPass(): void {
		// 5% of the tasks were picked up too late
		$this->mockCounts(100, 5);
		$this->timeFactory->method('now')->willReturn(new \DateTimeImmutable());

		$this->assertEquals(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}

	public function testFail(): void {
		// 30% of the tasks were picked up too late
		$this->mockCounts(100, 30);
		$this->timeFactory->method('now')->willReturn(new \DateTimeImmutable());

		$this->assertEquals(SetupResult::WARNING, $this->check->run()->getSeverity());
	}

	public function testWidensTheWindowWhileThereAreNoTasks(): void {
		$this->timeFactory->method('now')->willReturn(new \DateTimeImmutable());
		$this->taskProcessingManager->expects($this->never())->method('getTasks');
		$this->taskProcessingManager->expects($this->exactly(TaskProcessingPickupSpeed::MAX_DAYS - 1))
			->method('countTasks')
			->willReturn(0);

		$this->assertEquals(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}
}
