<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\Events\AddressBookCreatedEvent;
use OCA\DAV\Events\AddressBookDeletedEvent;
use OCA\DAV\Events\AddressBookShareUpdatedEvent;
use OCA\DAV\Events\CalendarCreatedEvent;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarMovedToTrashEvent;
use OCA\DAV\Events\CalendarPublishedEvent;
use OCA\DAV\Events\CalendarRestoredEvent;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCA\DAV\Events\CalendarUnpublishedEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardMovedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCP\Calendar\Events\AbstractCalendarObjectEvent;
use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Calendar\Events\CalendarObjectDeletedEvent;
use OCP\Calendar\Events\CalendarObjectMovedEvent;
use OCP\Calendar\Events\CalendarObjectMovedToTrashEvent;
use OCP\Calendar\Events\CalendarObjectRestoredEvent;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use Override;

/**
 * Writes changes to calendars, address books, events and contacts to the audit log
 *
 * @template-implements IEventListener<CalendarCreatedEvent|CalendarDeletedEvent|CalendarMovedToTrashEvent|CalendarRestoredEvent|CalendarPublishedEvent|CalendarUnpublishedEvent|CalendarShareUpdatedEvent|AddressBookCreatedEvent|AddressBookDeletedEvent|AddressBookShareUpdatedEvent|CalendarObjectCreatedEvent|CalendarObjectUpdatedEvent|CalendarObjectDeletedEvent|CalendarObjectMovedToTrashEvent|CalendarObjectRestoredEvent|CalendarObjectMovedEvent|CardCreatedEvent|CardUpdatedEvent|CardDeletedEvent|CardMovedEvent>
 */
class AuditLogListener implements IEventListener {
	public function __construct(
		private readonly IEventDispatcher $dispatcher,
	) {
	}

	#[Override]
	public function handle(Event $event): void {
		if ($event instanceof CalendarCreatedEvent) {
			$this->log('Calendar "%s" of "%s" was created with URI "%s"', [
				'calendarId' => $event->getCalendarId(),
				'owner' => $event->getCalendarData()['principaluri'] ?? null,
				'uri' => $event->getCalendarData()['uri'] ?? null,
			], 'dav.calendar.created');
		} elseif ($event instanceof CalendarMovedToTrashEvent) {
			$this->logCalendar('Calendar "%s" of "%s" was moved to trash', $event->getCalendarId(), $event->getCalendarData(), 'dav.calendar.trashed');
		} elseif ($event instanceof CalendarDeletedEvent) {
			$this->log('Calendar "%s" of "%s" was deleted, removing the shares with "%s"', [
				'calendarId' => $event->getCalendarId(),
				'owner' => $event->getCalendarData()['principaluri'] ?? null,
				'sharees' => $this->implodeShareHrefs($event->getShares()),
			], 'dav.calendar.deleted');
		} elseif ($event instanceof CalendarRestoredEvent) {
			$this->logCalendar('Calendar "%s" of "%s" was restored from trash', $event->getCalendarId(), $event->getCalendarData(), 'dav.calendar.restored');
		} elseif ($event instanceof CalendarPublishedEvent) {
			// The public URI grants anonymous read access and is not logged
			$this->logCalendar('Calendar "%s" of "%s" was published', $event->getCalendarId(), $event->getCalendarData(), 'dav.calendar.published');
		} elseif ($event instanceof CalendarUnpublishedEvent) {
			$this->logCalendar('Calendar "%s" of "%s" was unpublished', $event->getCalendarId(), $event->getCalendarData(), 'dav.calendar.unpublished');
		} elseif ($event instanceof CalendarShareUpdatedEvent) {
			$this->log('Shares of calendar "%s" of "%s" were updated, added: "%s", removed: "%s"', [
				'calendarId' => $event->getCalendarId(),
				'owner' => $event->getCalendarData()['principaluri'] ?? null,
				'added' => $this->implodeShareHrefs($event->getAdded()),
				'removed' => implode(', ', $event->getRemoved()),
			], 'dav.calendar_share.updated');
		} elseif ($event instanceof AddressBookCreatedEvent) {
			$this->log('Address book "%s" of "%s" was created with URI "%s"', [
				'addressBookId' => $event->getAddressBookId(),
				'owner' => $event->getAddressBookData()['principaluri'] ?? null,
				'uri' => $event->getAddressBookData()['uri'] ?? null,
			], 'dav.addressbook.created');
		} elseif ($event instanceof AddressBookDeletedEvent) {
			$this->log('Address book "%s" of "%s" was deleted, removing the shares with "%s"', [
				'addressBookId' => $event->getAddressBookId(),
				'owner' => $event->getAddressBookData()['principaluri'] ?? null,
				'sharees' => $this->implodeShareHrefs($event->getShares()),
			], 'dav.addressbook.deleted');
		} elseif ($event instanceof AddressBookShareUpdatedEvent) {
			$this->log('Shares of address book "%s" of "%s" were updated, added: "%s", removed: "%s"', [
				'addressBookId' => $event->getAddressBookId(),
				'owner' => $event->getAddressBookData()['principaluri'] ?? null,
				'added' => $this->implodeShareHrefs($event->getAdded()),
				'removed' => implode(', ', $event->getRemoved()),
			], 'dav.addressbook_share.updated');
		} elseif ($event instanceof CalendarObjectCreatedEvent) {
			$this->logCalendarObject('created', $event, 'dav.calendar_object.created');
		} elseif ($event instanceof CalendarObjectUpdatedEvent) {
			$this->logCalendarObject('updated', $event, 'dav.calendar_object.updated');
		} elseif ($event instanceof CalendarObjectDeletedEvent) {
			$this->logCalendarObject('deleted', $event, 'dav.calendar_object.deleted');
		} elseif ($event instanceof CalendarObjectMovedToTrashEvent) {
			$this->logCalendarObject('moved to trash', $event, 'dav.calendar_object.trashed');
		} elseif ($event instanceof CalendarObjectRestoredEvent) {
			$this->logCalendarObject('restored from trash', $event, 'dav.calendar_object.restored');
		} elseif ($event instanceof CalendarObjectMovedEvent) {
			$this->log('Calendar object "%s" was moved from calendar "%s" to calendar "%s" of "%s"', [
				'objectUri' => $event->getObjectData()['uri'] ?? null,
				'sourceCalendarId' => $event->getSourceCalendarId(),
				'targetCalendarId' => $event->getTargetCalendarId(),
				'owner' => $event->getTargetCalendarData()['principaluri'] ?? null,
			], 'dav.calendar_object.moved');
		} elseif ($event instanceof CardCreatedEvent) {
			$this->logCard('created', $event->getAddressBookId(), $event->getAddressBookData(), $event->getCardData(), 'dav.card.created');
		} elseif ($event instanceof CardUpdatedEvent) {
			$this->logCard('updated', $event->getAddressBookId(), $event->getAddressBookData(), $event->getCardData(), 'dav.card.updated');
		} elseif ($event instanceof CardDeletedEvent) {
			$this->logCard('deleted', $event->getAddressBookId(), $event->getAddressBookData(), $event->getCardData(), 'dav.card.deleted');
		} elseif ($event instanceof CardMovedEvent) {
			$this->log('Card "%s" was moved from address book "%s" to address book "%s" of "%s"', [
				'cardUri' => $event->getObjectData()['uri'] ?? null,
				'sourceAddressBookId' => $event->getSourceAddressBookId(),
				'targetAddressBookId' => $event->getTargetAddressBookId(),
				'owner' => $event->getTargetAddressBookData()['principaluri'] ?? null,
			], 'dav.card.moved');
		}
	}

	/**
	 * The birthday calendar is generated from the contacts, so its events are not logged.
	 * Only the URI of the object is logged, never its content.
	 */
	private function logCalendarObject(string $change, AbstractCalendarObjectEvent $event, string $operation): void {
		$calendarData = $event->getCalendarData();
		if (($calendarData['uri'] ?? null) === BirthdayService::BIRTHDAY_CALENDAR_URI) {
			return;
		}
		$this->log('Calendar object "%s" in calendar "%s" of "%s" was ' . $change, [
			'objectUri' => $event->getObjectData()['uri'] ?? null,
			'calendarId' => $event->getCalendarId(),
			'owner' => $calendarData['principaluri'] ?? null,
		], $operation);
	}

	/**
	 * The system address book is synced from the user accounts, so its cards are not logged.
	 * Only the URI of the card is logged, never its content.
	 */
	private function logCard(string $change, int $addressBookId, array $addressBookData, array $cardData, string $operation): void {
		if (($addressBookData['principaluri'] ?? null) === 'principals/system/system') {
			return;
		}
		$this->log('Card "%s" in address book "%s" of "%s" was ' . $change, [
			'cardUri' => $cardData['uri'] ?? null,
			'addressBookId' => $addressBookId,
			'owner' => $addressBookData['principaluri'] ?? null,
		], $operation);
	}

	private function logCalendar(string $message, int $calendarId, array $calendarData, string $operation): void {
		$this->log($message, [
			'calendarId' => $calendarId,
			'owner' => $calendarData['principaluri'] ?? null,
		], $operation);
	}

	/**
	 * @param array<array{href: string, ...}> $shares
	 */
	private function implodeShareHrefs(array $shares): string {
		return implode(', ', array_map(static fn (array $share): string => $share['href'], $shares));
	}

	/**
	 * @param array<string, scalar|null> $parameters
	 */
	private function log(string $message, array $parameters, string $operation): void {
		$this->dispatcher->dispatchTyped(new CriticalActionPerformedEvent($message, $parameters, false, $operation));
	}
}
