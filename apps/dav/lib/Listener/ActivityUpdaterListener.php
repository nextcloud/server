<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\Activity\Backend as ActivityBackend;
use OCA\DAV\Events\CalendarCreatedEvent;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CalendarUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;
use function sprintf;

class ActivityUpdaterListener implements IEventListener {

	/** @var ActivityBackend */
	private $activityBackend;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(ActivityBackend $activityBackend,
								LoggerInterface $logger) {
		$this->activityBackend = $activityBackend;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if ($event instanceof CalendarCreatedEvent) {
			try {
				$this->activityBackend->onCalendarAdd(
					$event->getCalendarData()
				);

				$this->logger->debug(
					sprintf('Activity generated for new calendar %d', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the calendar creation, so we just log it
				$this->logger->error('Error generating activities for a new calendar: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarUpdatedEvent) {
			try {
				$this->activityBackend->onCalendarUpdate(
					$event->getCalendarData(),
					$event->getShares(),
					$event->getMutations()
				);

				$this->logger->debug(
					sprintf('Activity generated for changed calendar %d', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the calendar update, so we just log it
				$this->logger->error('Error generating activities for changed calendar: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarDeletedEvent) {
			try {
				$this->activityBackend->onCalendarDelete(
					$event->getCalendarData(),
					$event->getShares()
				);

				$this->logger->debug(
					sprintf('Activity generated for deleted calendar %d', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the calendar deletion, so we just log it
				$this->logger->error('Error generating activities for a deleted calendar: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarObjectCreatedEvent) {
			try {
				$this->activityBackend->onTouchCalendarObject(
					\OCA\DAV\CalDAV\Activity\Provider\Event::SUBJECT_OBJECT_ADD,
					$event->getCalendarData(),
					$event->getShares(),
					$event->getObjectData()
				);

				$this->logger->debug(
					sprintf('Activity generated for new calendar object in calendar %d', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the calendar object creation, so we just log it
				$this->logger->error('Error generating activity for a new calendar object: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarObjectUpdatedEvent) {
			try {
				$this->activityBackend->onTouchCalendarObject(
					\OCA\DAV\CalDAV\Activity\Provider\Event::SUBJECT_OBJECT_UPDATE,
					$event->getCalendarData(),
					$event->getShares(),
					$event->getObjectData()
				);

				$this->logger->debug(
					sprintf('Activity generated for deleted calendar object %d', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the calendar deletion, so we just log it
				$this->logger->error('Error generating activity for a deleted calendar object: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CalendarObjectDeletedEvent) {
			try {
				$this->activityBackend->onTouchCalendarObject(
					\OCA\DAV\CalDAV\Activity\Provider\Event::SUBJECT_OBJECT_DELETE,
					$event->getCalendarData(),
					$event->getShares(),
					$event->getObjectData()
				);

				$this->logger->debug(
					sprintf('Activity generated for deleted calendar object %d', $event->getCalendarId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the calendar deletion, so we just log it
				$this->logger->error('Error generating activity for a deleted calendar object: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}
	}
}
