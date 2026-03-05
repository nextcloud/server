<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\Task;

class TaskProcessingSuccessRate implements ISetupCheck {
	public const MAX_FAILURE_PERCENTAGE = 0.2;

	public const MAX_DAYS = 14;

	public function __construct(
		private IL10N $l10n,
		private IManager $taskProcessingManager,
		private ITimeFactory $timeFactory,
	) {
	}

	public function getCategory(): string {
		return 'ai';
	}

	public function getName(): string {
		return $this->l10n->t('Task Processing pickup speed');
	}

	public function run(): SetupResult {
		$taskCount = 0;
		$lastNDays = 0;
		while ($taskCount === 0 && $lastNDays < self::MAX_DAYS) {
			$lastNDays++;
			// userId: '' means no filter, whereas null would mean guest
			$tasks = $this->taskProcessingManager->getTasks(userId: '', scheduleAfter: $this->timeFactory->now()->getTimestamp() - (60 * 60 * 24 * $lastNDays));
			$taskCount = count($tasks);
		}
		if ($taskCount === 0) {
			return SetupResult::success(
				$this->l10n->n(
					'No scheduled tasks in the last day.',
					'No scheduled tasks in the last %n days.',
					$lastNDays
				)
			);
		}
		$failedCount = 0;
		foreach ($tasks as $task) {
			if ($task->getEndedAt() === null) {
				continue; // task was not picked up yet
			}
			$status = $task->getStatus();
			if ($status === Task::STATUS_FAILED) {
				$failedCount++;
			}
		}

		if (($failedCount / $taskCount) < self::MAX_FAILURE_PERCENTAGE) {
			return SetupResult::success(
				$this->l10n->n(
					'Most tasks were successful in the last day.',
					'Most tasks were successful in the last %n days.',
					$lastNDays
				)
			);
		} else {
			return SetupResult::warning(
				$this->l10n->n(
					'A lot of tasks failed in the last day. Consider checking the nextcloud log for errors and investigating whether the AI provider apps have been set up correctly.',
					'A lot of tasks failed in the last %n days. Consider checking the nextcloud log for errors and investigating whether the AI provider apps have been set up correctly.',
					$lastNDays
				),
				'https://docs.nextcloud.com/server/latest/admin_manual/ai/insight_and_debugging.html'
			);
		}
	}
}
