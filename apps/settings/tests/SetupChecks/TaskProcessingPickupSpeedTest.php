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
use OCP\TaskProcessing\Task;
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

	public function testPass(): void {
		$tasks = [];
		for ($i = 0; $i < 100; $i++) {
			$task = new Task('test', ['test' => 'test'], 'settings', 'user' . $i);
			$task->setStartedAt(0);
			if ($i < 15) {
				$task->setScheduledAt(60 * 5); // 15% get 5mins
			} else {
				$task->setScheduledAt(60); // the rest gets 1min
			}
			$tasks[] = $task;
		}
		$this->taskProcessingManager->method('getTasks')->willReturn($tasks);

		$this->assertEquals(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}

	public function testFail(): void {
		$tasks = [];
		for ($i = 0; $i < 100; $i++) {
			$task = new Task('test', ['test' => 'test'], 'settings', 'user' . $i);
			$task->setStartedAt(0);
			if ($i < 30) {
				$task->setScheduledAt(60 * 5); // 30% get 5mins
			} else {
				$task->setScheduledAt(60); // the rest gets 1min
			}
			$tasks[] = $task;
		}
		$this->taskProcessingManager->method('getTasks')->willReturn($tasks);

		$this->assertEquals(SetupResult::WARNING, $this->check->run()->getSeverity());
	}
}
