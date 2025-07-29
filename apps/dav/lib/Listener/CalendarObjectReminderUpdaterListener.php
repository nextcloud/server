<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Reminder\Backend as ReminderBackend;
use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarMovedToTrashEvent;
use OCA\DAV\Events\CalendarRestoredEvent;
use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Calendar\Events\CalendarObjectDeletedEvent;
use OCP\Calendar\Events\CalendarObjectMovedToTrashEvent;
use OCP\Calendar\Events\CalendarObjectRestoredEvent;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;
use function sprintf;

/** @template-implements IEventListener<CalendarMovedToTrashEvent|CalendarDeletedEvent|CalendarRestoredEvent|CalendarObjectCreatedEvent|CalendarObjectUpdatedEvent|CalendarObjectMovedToTrashEvent|CalendarObjectRestoredEvent|CalendarObjectDeletedEvent> */
class CalendarObjectReminderUpdaterListener implements IEventListener {

	public function __construct(
		private ReminderBackend $reminderBackend,
		private ReminderService $reminderService,
		private CalDavBackend $calDavBackend,
		private LoggerInterface $logger,
	) {
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
