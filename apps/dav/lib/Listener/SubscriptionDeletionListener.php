<?php

declare(strict_types=1);

/**
 * @copyright 2021 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\Listener;

use OCA\DAV\BackgroundJob\RefreshWebcalJob;
use OCA\DAV\CalDAV\Reminder\Backend as ReminderBackend;
use OCA\DAV\Events\SubscriptionDeletedEvent;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<\OCA\DAV\Events\SubscriptionDeletedEvent>
 */
class SubscriptionDeletionListener implements IEventListener {
	private IJobList $jobList;
	private ReminderBackend $reminderBackend;
	private LoggerInterface $logger;

	public function __construct(IJobList $jobList, ReminderBackend $reminderBackend,
								LoggerInterface $logger) {
		$this->jobList = $jobList;
		$this->reminderBackend = $reminderBackend;
		$this->logger = $logger;
	}

	/**
	 * In case the user has set their default calendar to the deleted one
	 */
	public function handle(Event $event): void {
		if (!($event instanceof SubscriptionDeletedEvent)) {
			// Not what we subscribed to
			return;
		}

		$subscriptionId = $event->getSubscriptionId();
		$subscriptionData = $event->getSubscriptionData();

		$this->logger->debug('Removing refresh webcal job for subscription ' . $subscriptionId);
		$this->jobList->remove(RefreshWebcalJob::class, [
			'principaluri' => $subscriptionData['principaluri'],
			'uri' => $subscriptionData['uri']
		]);

		$this->logger->debug('Cleaning all reminders for subscription ' . $subscriptionId);
		$this->reminderBackend->cleanRemindersForCalendar($subscriptionId);
	}
}
