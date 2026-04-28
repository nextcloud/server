<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\TaskProcessingWorkerIsRunning;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\SetupCheck\SetupResult;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\Task;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TaskProcessingWorkerIsRunningTest extends TestCase {
	private IL10N&MockObject $l10n;
	private ITimeFactory&MockObject $timeFactory;
	private IManager&MockObject $taskProcessingManager;
	private IAppConfig&MockObject $appConfig;

	private TaskProcessingWorkerIsRunning $check;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->timeFactory = $this->getMockBuilder(ITimeFactory::class)->getMock();
		$this->taskProcessingManager = $this->getMockBuilder(IManager::class)->getMock();
		$this->appConfig = $this->getMockBuilder(IAppConfig::class)->getMock();

		$this->check = new TaskProcessingWorkerIsRunning(
			$this->l10n,
			$this->taskProcessingManager,
			$this->timeFactory,
			$this->appConfig
		);
	}

	public function testPass(): void {
		$tasks = [];
		for ($i = 0; $i < 10; $i++) {
			$task = new Task('test', ['test' => 'test'], 'settings', 'user' . $i);
			$task->setStartedAt($this->timeFactory->now()->getTimestamp());
			$task->setScheduledAt($this->timeFactory->now()->getTimestamp());
			$task->setEndedAt($this->timeFactory->now()->getTimestamp());
			$task->setStatus(Task::STATUS_SUCCESSFUL);
			$tasks[] = $task;
		}
		$this->taskProcessingManager->method('getTasks')->willReturn($tasks);

		$this->appConfig->method('getValueString')->willReturn((string)$this->timeFactory->now()->getTimestamp());

		$this->assertEquals(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}

	public function testFail(): void {
		$tasks = [];
		for ($i = 0; $i < 10; $i++) {
			$task = new Task('test', ['test' => 'test'], 'settings', 'user' . $i);
			$task->setStartedAt($this->timeFactory->now()->getTimestamp());
			$task->setScheduledAt($this->timeFactory->now()->getTimestamp());
			$task->setEndedAt($this->timeFactory->now()->getTimestamp());
			$task->setStatus(Task::STATUS_SUCCESSFUL);
			$tasks[] = $task;
		}
		$this->taskProcessingManager->method('getTasks')->willReturn($tasks);

		$this->appConfig->method('getValueString')->willReturn((string)($this->timeFactory->now()->getTimestamp() - 60 * 10));

		$this->assertEquals(SetupResult::WARNING, $this->check->run()->getSeverity());
	}
}
