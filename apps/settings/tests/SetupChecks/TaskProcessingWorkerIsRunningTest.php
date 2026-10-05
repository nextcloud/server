<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\TaskProcessingWorkerIsRunning;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use OCP\TaskProcessing\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TaskProcessingWorkerIsRunningTest extends TestCase {
	private IL10N&MockObject $l10n;
	private ITimeFactory&MockObject $timeFactory;
	private IManager&MockObject $taskProcessingManager;
	private IAppConfig&MockObject $appConfig;
	private IURLGenerator&MockObject $urlGenerator;

	private TaskProcessingWorkerIsRunning $check;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->timeFactory = $this->getMockBuilder(ITimeFactory::class)->getMock();
		$this->taskProcessingManager = $this->getMockBuilder(IManager::class)->getMock();
		$this->appConfig = $this->getMockBuilder(IAppConfig::class)->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->getMock();

		$this->check = new TaskProcessingWorkerIsRunning(
			$this->l10n,
			$this->taskProcessingManager,
			$this->timeFactory,
			$this->appConfig,
			$this->urlGenerator,
		);
	}

	public function testPass(): void {
		$this->taskProcessingManager->method('countTasks')->willReturn(10);
		$this->timeFactory->method('now')->willReturn(new \DateTimeImmutable());
		$this->appConfig->method('getValueString')->willReturn((string)$this->timeFactory->now()->getTimestamp());

		$this->assertEquals(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}

	public function testFail(): void {
		$this->taskProcessingManager->method('countTasks')->willReturn(10);
		$this->timeFactory->method('now')->willReturn(new \DateTimeImmutable());
		$this->appConfig->method('getValueString')->willReturn((string)($this->timeFactory->now()->getTimestamp() - 60 * 10));

		$this->assertEquals(SetupResult::WARNING, $this->check->run()->getSeverity());
	}

	public function testTasksAreOnlyCounted(): void {
		$now = new \DateTimeImmutable();
		$this->timeFactory->method('now')->willReturn($now);
		// The tasks themselves are never needed, only whether there are any
		$this->taskProcessingManager->expects($this->never())->method('getTasks');
		$this->taskProcessingManager->expects($this->once())
			->method('countTasks')
			->willReturnCallback(function (?int $status = null, array $taskTypeIds = [], ?int $scheduleAfter = null, ?int $minPickupDelay = null) use ($now): int {
				$this->assertNull($status);
				$this->assertSame([], $taskTypeIds);
				$this->assertSame($now->getTimestamp() - 60 * 60 * 24 * TaskProcessingWorkerIsRunning::HAS_TASKS_IN_LAST_X_DAYS, $scheduleAfter);
				$this->assertNull($minPickupDelay);
				return 0;
			});
		$this->appConfig->expects($this->never())->method('getValueString');

		$this->assertEquals(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}
}
