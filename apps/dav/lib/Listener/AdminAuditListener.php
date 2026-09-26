<?php

declare(strict_types=1);

/*
 * @copyright 2024 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2024 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectMovedEvent;
use OCA\DAV\Events\CalendarObjectMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Log\Audit\CriticalActionPerformedEvent;

/**
 * @template-extends IEventListener<CalendarObjectCreatedEvent|CalendarObjectUpdatedEvent|CalendarObjectMovedEvent|CalendarObjectDeletedEvent|Event>
 */
class AdminAuditListener implements IEventListener {

	private IEventDispatcher $eventDispatcher;

	public function __construct(IEventDispatcher $eventDispatcher) {
		$this->eventDispatcher = $eventDispatcher;
	}

	public function handle(Event $event): void {
		if ($event instanceof CalendarObjectCreatedEvent) {
			$this->eventDispatcher->dispatchTyped(
				new CriticalActionPerformedEvent(
					'Calendar event %s created in calendar %s of principal %s',
					[
						$event->getObjectData()['uri'] ?? '?',
						$event->getCalendarData()['uri'] ?? '?',
						$event->getCalendarData()['principaluri'] ?? '?',
					],
				)
			);
		}
		if ($event instanceof CalendarObjectUpdatedEvent) {
			$this->eventDispatcher->dispatchTyped(
				new CriticalActionPerformedEvent(
					'Calendar event %s in calendar %s of principal %s updated',
					[
						$event->getObjectData()['uri'] ?? '?',
						$event->getCalendarData()['uri'] ?? '?',
						$event->getCalendarData()['principaluri'] ?? '?',
					],
				)
			);
		}
		if ($event instanceof CalendarObjectMovedEvent) {
			$this->eventDispatcher->dispatchTyped(
				new CriticalActionPerformedEvent(
					'Calendar event %s moved from calendar %s of principal %s updated to calendar %s of principal %s',
					[
						$event->getObjectData()['uri'] ?? '?',
						$event->getSourceCalendarData()['uri'] ?? '?',
						$event->getSourceCalendarData()['principaluri'] ?? '?',
						$event->getTargetCalendarData()['uri'] ?? '?',
						$event->getTargetCalendarData()['principaluri'] ?? '?',
					],
				)
			);
		}
		if ($event instanceof CalendarObjectMovedToTrashEvent) {
			$this->eventDispatcher->dispatchTyped(
				new CriticalActionPerformedEvent(
					'Calendar event %s in calendar %s of principal %s moved to trash',
					[
						$event->getObjectData()['uri'] ?? '?',
						$event->getCalendarData()['uri'] ?? '?',
						$event->getCalendarData()['principaluri'] ?? '?',
					],
				)
			);
		}
		if ($event instanceof CalendarObjectDeletedEvent) {
			$this->eventDispatcher->dispatchTyped(
				new CriticalActionPerformedEvent(
					'Calendar event %s moved from calendar %s of principal %s updated to calendar %s of principal %s',
					[
						$event->getObjectData()['uri'] ?? '?',
						$event->getSourceCalendarData()['uri'] ?? '?',
						$event->getSourceCalendarData()['principaluri'] ?? '?',
						$event->getTargetCalendarData()['uri'] ?? '?',
						$event->getTargetCalendarData()['principaluri'] ?? '?',
					],
				)
			);
		}
	}
}
