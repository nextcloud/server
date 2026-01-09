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

	public function testPass(): void {
		$tasks = [];
		for ($i = 0; $i < 100; $i++) {
			$task = new Task('test', ['test' => 'test'], 'settings', 'user' . $i);
			$task->setStartedAt(0);
			$task->setEndedAt(1);
			if ($i < 15) {
				$task->setStatus(Task::STATUS_FAILED); // 15% get status FAILED
			} else {
				$task->setStatus(Task::STATUS_SUCCESSFUL);
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
			$task->setEndedAt(1);
			if ($i < 30) {
				$task->setStatus(Task::STATUS_FAILED); // 30% get status FAILED
			} else {
				$task->setStatus(Task::STATUS_SUCCESSFUL);
			}
			$tasks[] = $task;
		}
		$this->taskProcessingManager->method('getTasks')->willReturn($tasks);

		$this->assertEquals(SetupResult::WARNING, $this->check->run()->getSeverity());
	}
}
