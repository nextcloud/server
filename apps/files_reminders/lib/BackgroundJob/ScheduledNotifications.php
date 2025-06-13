<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\BackgroundJob;

use OCA\FilesReminders\Db\ReminderMapper;
use OCA\FilesReminders\Service\ReminderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class ScheduledNotifications extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		protected ReminderMapper $reminderMapper,
		protected ReminderService $reminderService,
		protected LoggerInterface $logger,
	) {
		parent::__construct($time);

		$this->setInterval(60);
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function run($argument) {
		$reminders = $this->reminderMapper->findOverdue();
		foreach ($reminders as $reminder) {
			try {
				$this->reminderService->send($reminder);
			} catch (DoesNotExistException $e) {
				$this->logger->debug('Could not send notification for reminder with id ' . $reminder->getId());
			}
		}
	}
}
