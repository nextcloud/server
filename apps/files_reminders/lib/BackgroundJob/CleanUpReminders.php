<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\BackgroundJob;

use OCA\FilesReminders\Service\ReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class CleanUpReminders extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private ReminderService $reminderService,
	) {
		parent::__construct($time);

		$this->setInterval(24 * 60 * 60); // 1 day
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function run($argument) {
		$this->reminderService->cleanUp(500);
	}
}
