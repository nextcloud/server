<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use OCP\TaskProcessing\IManager;

class TaskProcessingWorkerIsRunning implements ISetupCheck {

	public const HAS_TASKS_IN_LAST_X_DAYS = 7;
	public const IS_RUNNING_IN_LAST_X_MINUTES = 5;

	public function __construct(
		private readonly IL10N $l10n,
		private readonly IManager $taskProcessingManager,
		private readonly ITimeFactory $timeFactory,
		private readonly IAppConfig $appConfig,
	) {
	}

	public function getCategory(): string {
		return 'ai';
	}

	public function getName(): string {
		return $this->l10n->t('Task Processing worker status');
	}

	public function run(): SetupResult {
		$lastNDays = self::HAS_TASKS_IN_LAST_X_DAYS;
		$tasks = $this->taskProcessingManager->getTasks(userId: '', scheduleAfter: $this->timeFactory->now()->getTimestamp() - (60 * 60 * 24 * $lastNDays));
		$taskCount = count($tasks);
		if ($taskCount === 0) {
			// In case taskprocessing is not used at all
			return SetupResult::success(
				$this->l10n->n(
					'No scheduled tasks in the last day.',
					'No scheduled tasks in the last %n days.',
					$lastNDays
				)
			);
		}
		$lastIteration = (int)$this->appConfig->getValueString('core', 'ai.taskprocessing_worker_last_iteration', lazy: true);
		if ($lastIteration > $this->timeFactory->now()->getTimestamp() - (60 * self::IS_RUNNING_IN_LAST_X_MINUTES)) {
			return SetupResult::success(
				$this->l10n->n('The Task Processing worker has run in the last minute.', 'The Task Processing worker has run in the last %n minutes.', self::IS_RUNNING_IN_LAST_X_MINUTES)
			);
		}

		if ($lastIteration > 0) {
			return SetupResult::warning(
				$this->l10n->t('The Task Processing worker does not seem to be running. The last run was at %s.', [date('Y-m-d H:i:s', $lastIteration)])
			);
		}

		return SetupResult::warning(
			$this->l10n->t('The Task Processing worker does not seem to be running. It seems it has never run so far.')
		);
	}
}
