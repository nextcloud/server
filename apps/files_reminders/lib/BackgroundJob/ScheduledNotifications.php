<?php

declare(strict_types=1);

/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FilesReminders\BackgroundJob;

use OCA\FilesReminders\Db\ReminderMapper;
use OCA\FilesReminders\Service\ReminderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\Job;
use Psr\Log\LoggerInterface;

class ScheduledNotifications extends Job {
	public function __construct(
		ITimeFactory $time,
		protected ReminderMapper $reminderMapper,
		protected ReminderService $reminderService,
		protected LoggerInterface $logger,
	) {
		parent::__construct($time);
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
