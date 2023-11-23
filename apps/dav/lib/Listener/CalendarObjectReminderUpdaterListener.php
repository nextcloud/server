<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Reminder\Backend as ReminderBackend;
use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectRestoredEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CalendarRestoredEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;
use function sprintf;

class CalendarObjectReminderUpdaterListener implements IEventListener {

	/** @var ReminderBackend */
	private $reminderBackend;

	/** @var ReminderService */
	private $reminderService;

	/** @var CalDavBackend */
	private $calDavBackend;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(ReminderBackend $reminderBackend,
		ReminderService $reminderService,
		CalDavBackend $calDavBackend,
		LoggerInterface $logger) {
		$this->reminderBackend = $reminderBackend;
		$this->reminderService = $reminderService;
		$this->calDavBackend = $calDavBackend;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if ($event instanceof CalendarMovedToTrashEvent) {
			try {
				$this->reminderBackend->cleanRemindersForCalendar(
					$event->getCalendarId()
				);

				$this->logger->debug(
					sprintf('Reminders of calendar %d cleaned up after move into trashbin', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with reminders shouldn't abort the calendar move, so we just log it
				$this->logger->error('Error cleaning up reminders of a calendar moved into trashbin: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarDeletedEvent) {
			try {
				$this->reminderBackend->cleanRemindersForCalendar(
					$event->getCalendarId()
				);

				$this->logger->debug(
					sprintf('Reminders of calendar %d cleaned up', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the calendar deletion, so we just log it
				$this->logger->error('Error cleaning up reminders of a deleted calendar: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarRestoredEvent) {
			try {
				$objects = $this->calDavBackend->getCalendarObjects($event->getCalendarId());
				$this->logger->debug(sprintf('Restoring calendar reminder objects for %d items', count($objects)));
				foreach ($objects as $object) {
					$fullObject = $this->calDavBackend->getCalendarObject(
						$event->getCalendarId(),
						$object['uri']
					);
					$this->reminderService->onCalendarObjectCreate($fullObject);
				}

				$this->logger->debug(
					sprintf('Reminders of calendar %d restored', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with reminders shouldn't abort the calendar deletion, so we just log it
				$this->logger->error('Error restoring reminders of a calendar: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarObjectCreatedEvent) {
			try {
				$this->reminderService->onCalendarObjectCreate(
					$event->getObjectData()
				);

				$this->logger->debug(
					sprintf('Reminders of calendar object of calendar %d created', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with reminders shouldn't abort the calendar object creation, so we just log it
				$this->logger->error('Error creating reminders of a calendar object: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarObjectUpdatedEvent) {
			try {
				$this->reminderService->onCalendarObjectEdit(
					$event->getObjectData()
				);

				$this->logger->debug(
					sprintf('Reminders of calendar object of calendar %d cleaned up', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the calendar object deletion, so we just log it
				$this->logger->error('Error cleaning up reminders of a calendar object: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarObjectMovedToTrashEvent) {
			try {
				$this->reminderService->onCalendarObjectDelete(
					$event->getObjectData()
				);

				$this->logger->debug(
					sprintf('Reminders of restored calendar object of calendar %d deleted', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with reminders shouldn't abort the calendar object deletion, so we just log it
				$this->logger->error('Error deleting reminders of a calendar object: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarObjectRestoredEvent) {
			try {
				$this->reminderService->onCalendarObjectCreate(
					$event->getObjectData()
				);

				$this->logger->debug(
					sprintf('Reminders of restored calendar object of calendar %d restored', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with reminders shouldn't abort the calendar object deletion, so we just log it
				$this->logger->error('Error restoring reminders of a calendar object: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarObjectDeletedEvent) {
			try {
				$this->reminderService->onCalendarObjectDelete(
					$event->getObjectData()
				);

				$this->logger->debug(
					sprintf('Reminders of calendar object of calendar %d cleaned up', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the calendar object deletion, so we just log it
				$this->logger->error('Error cleaning up reminders of a deleted calendar object: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}
	}
}
