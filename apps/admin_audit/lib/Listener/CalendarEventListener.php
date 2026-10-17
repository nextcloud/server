<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCA\AdminAudit\Operation;
use OCA\DAV\Events\CalendarCreatedEvent;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarMovedToTrashEvent;
use OCA\DAV\Events\CalendarPublishedEvent;
use OCA\DAV\Events\CalendarRestoredEvent;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCA\DAV\Events\CalendarUnpublishedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Override;

/**
 * @template-implements IEventListener<CalendarCreatedEvent|CalendarDeletedEvent|CalendarMovedToTrashEvent|CalendarRestoredEvent|CalendarPublishedEvent|CalendarUnpublishedEvent|CalendarShareUpdatedEvent>
 */
class CalendarEventListener extends Action implements IEventListener {
	#[Override]
	public function handle(Event $event): void {
		if ($event instanceof CalendarCreatedEvent) {
			$this->log(Operation::CalendarCreated, 'Calendar "%s" of "%s" was created with URI "%s"',
				[
					'calendarId' => $event->getCalendarId(),
					'owner' => $event->getCalendarData()['principaluri'] ?? null,
					'uri' => $event->getCalendarData()['uri'] ?? null,
				],
				['calendarId', 'owner', 'uri']
			);
		} elseif ($event instanceof CalendarMovedToTrashEvent) {
			$this->logCalendar(Operation::CalendarTrashed, 'Calendar "%s" of "%s" was moved to trash', $event->getCalendarId(), $event->getCalendarData());
		} elseif ($event instanceof CalendarDeletedEvent) {
			$this->log(Operation::CalendarDeleted, 'Calendar "%s" of "%s" was deleted, removing the shares with "%s"',
				[
					'calendarId' => $event->getCalendarId(),
					'owner' => $event->getCalendarData()['principaluri'] ?? null,
					'sharees' => self::implodeShareHrefs($event->getShares()),
				],
				['calendarId', 'owner', 'sharees']
			);
		} elseif ($event instanceof CalendarRestoredEvent) {
			$this->logCalendar(Operation::CalendarRestored, 'Calendar "%s" of "%s" was restored from trash', $event->getCalendarId(), $event->getCalendarData());
		} elseif ($event instanceof CalendarPublishedEvent) {
			// The public URI grants anonymous read access and is not logged
			$this->logCalendar(Operation::CalendarPublished, 'Calendar "%s" of "%s" was published', $event->getCalendarId(), $event->getCalendarData());
		} elseif ($event instanceof CalendarUnpublishedEvent) {
			$this->logCalendar(Operation::CalendarUnpublished, 'Calendar "%s" of "%s" was unpublished', $event->getCalendarId(), $event->getCalendarData());
		} elseif ($event instanceof CalendarShareUpdatedEvent) {
			$this->log(Operation::CalendarShareUpdated, 'Shares of calendar "%s" of "%s" were updated, added: "%s", removed: "%s"',
				[
					'calendarId' => $event->getCalendarId(),
					'owner' => $event->getCalendarData()['principaluri'] ?? null,
					'added' => self::implodeShareHrefs($event->getAdded()),
					'removed' => implode(', ', $event->getRemoved()),
				],
				['calendarId', 'owner', 'added', 'removed']
			);
		}
	}

	private function logCalendar(Operation $operation, string $text, int $calendarId, array $calendarData): void {
		$this->log($operation, $text,
			[
				'calendarId' => $calendarId,
				'owner' => $calendarData['principaluri'] ?? null,
			],
			['calendarId', 'owner']
		);
	}

	/**
	 * @param array<array{href: string, ...}> $shares
	 */
	public static function implodeShareHrefs(array $shares): string {
		return implode(', ', array_map(static fn (array $share): string => $share['href'], $shares));
	}
}
