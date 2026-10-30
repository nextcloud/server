<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\TaskProcessingSuccessRate;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use OCP\SetupCheck\SetupResult;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\Task;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TaskProcessingSuccessRateTest extends TestCase {
	private IL10N&MockObject $l10n;
	private ITimeFactory&MockObject $timeFactory;
	private IManager&MockObject $taskProcessingManager;

	private TaskProcessingSuccessRate $check;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->timeFactory = $this->getMockBuilder(ITimeFactory::class)->getMock();
		$this->taskProcessingManager = $this->getMockBuilder(IManager::class)->getMock();

		$this->check = new TaskProcessingSuccessRate(
			$this->l10n,
			$this->taskProcessingManager,
			$this->timeFactory,
		);
	}

	/**
	 * @param int $taskCount Tasks scheduled in the window
	 * @param int $failedCount Tasks of those that failed
	 */
	private function mockCounts(int $taskCount, int $failedCount): void {
		$this->taskProcessingManager->method('countTasks')
			->willReturnCallback(function (?int $status = null, array $taskTypeIds = [], ?int $scheduleAfter = null, ?int $minPickupDelay = null) use ($taskCount, $failedCount): int {
				return $status === Task::STATUS_FAILED ? $failedCount : $taskCount;
			});
	}

	public function testPass(): void {
		// 5% of the tasks failed
		$this->mockCounts(100, 5);
		$this->timeFactory->method('now')->willReturn(new \DateTimeImmutable());

		$this->assertEquals(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}

	public function testFail(): void {
		// 30% of the tasks failed
		$this->mockCounts(100, 30);
		$this->timeFactory->method('now')->willReturn(new \DateTimeImmutable());

		$this->assertEquals(SetupResult::WARNING, $this->check->run()->getSeverity());
	}

	public function testWidensTheWindowWhileThereAreNoTasks(): void {
		$this->timeFactory->method('now')->willReturn(new \DateTimeImmutable());
		$this->taskProcessingManager->expects($this->never())->method('getTasks');
		$this->taskProcessingManager->expects($this->exactly(TaskProcessingSuccessRate::MAX_DAYS))
			->method('countTasks')
			->willReturn(0);

		$this->assertEquals(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}
}
