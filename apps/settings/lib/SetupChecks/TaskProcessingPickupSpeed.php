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

class TaskProcessingPickupSpeed implements ISetupCheck {
	public const MAX_SLOW_PERCENTAGE = 0.1;

	public const MAX_DAYS = 14;
	public const MAX_PICKUP_DELAY = 60 * 4;

	public function __construct(
		private IL10N $l10n,
		private IManager $taskProcessingManager,
		private ITimeFactory $timeFactory,
	) {
	}

	#[\Override]
	public function getCategory(): string {
		return 'ai';
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Task Processing pickup speed');
	}

	#[\Override]
	public function run(): SetupResult {
		$lastNDays = 1;
		do {
			$lastNDays++;
			$scheduleAfter = $this->timeFactory->now()->getTimestamp() - (60 * 60 * 24 * $lastNDays);
			$taskCount = $this->taskProcessingManager->countTasks(scheduleAfter: $scheduleAfter);
		} while ($taskCount === 0 && $lastNDays < self::MAX_DAYS);
		if ($taskCount === 0) {
			return SetupResult::success(
				$this->l10n->n(
					'No scheduled tasks in the last day.',
					'No scheduled tasks in the last %n days.',
					$lastNDays
				)
			);
		}
		// Tasks that have not been picked up yet are not counted as slow
		$slowCount = $this->taskProcessingManager->countTasks(scheduleAfter: $scheduleAfter, minPickupDelay: self::MAX_PICKUP_DELAY);

		if (($slowCount / $taskCount) < self::MAX_SLOW_PERCENTAGE) {
			return SetupResult::success(
				$this->l10n->n(
					'The task pickup speed has been ok in the last day.',
					'The task pickup speed has been ok in the last %n days.',
					$lastNDays
				)
			);
		} else {
			return SetupResult::warning(
				$this->l10n->n(
					'The task pickup speed has been slow in the last day. Many tasks took longer than 4 minutes to be picked up. Consider setting up a worker to process tasks in the background.',
					'The task pickup speed has been slow in the last %n days. Many tasks took longer than 4 minutes to be picked up. Consider setting up a worker to process tasks in the background.',
					$lastNDays
				),
				'https://docs.nextcloud.com/server/latest/admin_manual/ai/overview.html#improve-ai-task-pickup-speed'
			);
		}
	}
}
