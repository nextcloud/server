<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Listener;

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
use OCA\DAV\Listener\AuditLogListener;
use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Calendar\Events\CalendarObjectDeletedEvent;
use OCP\Calendar\Events\CalendarObjectMovedEvent;
use OCP\Calendar\Events\CalendarObjectMovedToTrashEvent;
use OCP\Calendar\Events\CalendarObjectRestoredEvent;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AuditLogListenerTest extends TestCase {
	private const CALENDAR_DATA = [
		'id' => 42,
		'uri' => 'personal',
		'principaluri' => 'principals/users/alice',
	];

	private const ADDRESS_BOOK_DATA = [
		'id' => 7,
		'uri' => 'contacts',
		'principaluri' => 'principals/users/alice',
	];

	private const OBJECT_DATA = [
		'id' => 3,
		'uri' => 'meeting.ics',
		'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:Secret meeting\r\nEND:VEVENT\r\nEND:VCALENDAR",
	];

	private const CARD_DATA = [
		'id' => 5,
		'uri' => 'bob.vcf',
		'carddata' => "BEGIN:VCARD\r\nFN:Bob Secret\r\nEND:VCARD",
	];

	private IEventDispatcher&MockObject $dispatcher;
	private AuditLogListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->listener = new AuditLogListener($this->dispatcher);
	}

	public static function dataEvents(): array {
		$shares = [
			['href' => 'principal:principals/users/bob', 'commonName' => 'Bob', 'status' => 1, 'readOnly' => true, '{http://owncloud.org/ns}principal' => 'principals/users/bob', '{http://owncloud.org/ns}group-share' => false],
			['href' => 'principal:principals/groups/staff', 'commonName' => 'Staff', 'status' => 1, 'readOnly' => false, '{http://owncloud.org/ns}principal' => 'principals/groups/staff', '{http://owncloud.org/ns}group-share' => true],
		];

		return [
			'calendar created' => [
				new CalendarCreatedEvent(42, self::CALENDAR_DATA),
				'Calendar "42" of "principals/users/alice" was created with URI "personal"',
				'dav.calendar.created',
			],
			'calendar trashed' => [
				new CalendarMovedToTrashEvent(42, self::CALENDAR_DATA, $shares),
				'Calendar "42" of "principals/users/alice" was moved to trash',
				'dav.calendar.trashed',
			],
			'calendar deleted' => [
				new CalendarDeletedEvent(42, self::CALENDAR_DATA, $shares),
				'Calendar "42" of "principals/users/alice" was deleted, removing the shares with "principal:principals/users/bob, principal:principals/groups/staff"',
				'dav.calendar.deleted',
			],
			'calendar restored' => [
				new CalendarRestoredEvent(42, self::CALENDAR_DATA, []),
				'Calendar "42" of "principals/users/alice" was restored from trash',
				'dav.calendar.restored',
			],
			'calendar published' => [
				new CalendarPublishedEvent(42, self::CALENDAR_DATA, 'SECRETPUBLICURI'),
				'Calendar "42" of "principals/users/alice" was published',
				'dav.calendar.published',
			],
			'calendar unpublished' => [
				new CalendarUnpublishedEvent(42, self::CALENDAR_DATA),
				'Calendar "42" of "principals/users/alice" was unpublished',
				'dav.calendar.unpublished',
			],
			'calendar shares updated' => [
				new CalendarShareUpdatedEvent(42, self::CALENDAR_DATA, [],
					[
						['href' => 'principal:principals/users/bob', 'commonName' => 'Bob', 'readOnly' => true],
						['href' => 'principal:principals/users/carol', 'commonName' => 'Carol', 'readOnly' => false],
					],
					['principal:principals/groups/staff'],
				),
				'Shares of calendar "42" of "principals/users/alice" were updated, added: "principal:principals/users/bob, principal:principals/users/carol", removed: "principal:principals/groups/staff"',
				'dav.calendar_share.updated',
			],
			'calendar unshared' => [
				new CalendarShareUpdatedEvent(42, self::CALENDAR_DATA, [], [], ['principal:principals/users/bob']),
				'Shares of calendar "42" of "principals/users/alice" were updated, added: "", removed: "principal:principals/users/bob"',
				'dav.calendar_share.updated',
			],
			'address book created' => [
				new AddressBookCreatedEvent(7, self::ADDRESS_BOOK_DATA),
				'Address book "7" of "principals/users/alice" was created with URI "contacts"',
				'dav.addressbook.created',
			],
			'address book deleted' => [
				new AddressBookDeletedEvent(7, self::ADDRESS_BOOK_DATA, [
					['href' => 'principal:principals/users/bob', 'commonName' => 'Bob', 'status' => 1, 'readOnly' => true],
				]),
				'Address book "7" of "principals/users/alice" was deleted, removing the shares with "principal:principals/users/bob"',
				'dav.addressbook.deleted',
			],
			'address book shares updated' => [
				new AddressBookShareUpdatedEvent(7, self::ADDRESS_BOOK_DATA, [],
					[['href' => 'principal:principals/groups/staff', 'commonName' => 'Staff', 'readOnly' => true]],
					['principal:principals/users/bob', 'principal:principals/users/carol'],
				),
				'Shares of address book "7" of "principals/users/alice" were updated, added: "principal:principals/groups/staff", removed: "principal:principals/users/bob, principal:principals/users/carol"',
				'dav.addressbook_share.updated',
			],
			'calendar object created' => [
				new CalendarObjectCreatedEvent(42, self::CALENDAR_DATA, [], self::OBJECT_DATA),
				'Calendar object "meeting.ics" in calendar "42" of "principals/users/alice" was created',
				'dav.calendar_object.created',
			],
			'calendar object updated' => [
				new CalendarObjectUpdatedEvent(42, self::CALENDAR_DATA, [], self::OBJECT_DATA, self::OBJECT_DATA),
				'Calendar object "meeting.ics" in calendar "42" of "principals/users/alice" was updated',
				'dav.calendar_object.updated',
			],
			'calendar object deleted' => [
				new CalendarObjectDeletedEvent(42, self::CALENDAR_DATA, [], self::OBJECT_DATA),
				'Calendar object "meeting.ics" in calendar "42" of "principals/users/alice" was deleted',
				'dav.calendar_object.deleted',
			],
			'calendar object trashed' => [
				new CalendarObjectMovedToTrashEvent(42, self::CALENDAR_DATA, [], self::OBJECT_DATA),
				'Calendar object "meeting.ics" in calendar "42" of "principals/users/alice" was moved to trash',
				'dav.calendar_object.trashed',
			],
			'calendar object restored' => [
				new CalendarObjectRestoredEvent(42, self::CALENDAR_DATA, [], self::OBJECT_DATA),
				'Calendar object "meeting.ics" in calendar "42" of "principals/users/alice" was restored from trash',
				'dav.calendar_object.restored',
			],
			'calendar object moved' => [
				new CalendarObjectMovedEvent(42, self::CALENDAR_DATA, 43, ['id' => 43, 'uri' => 'work', 'principaluri' => 'principals/users/alice'], [], [], self::OBJECT_DATA),
				'Calendar object "meeting.ics" was moved from calendar "42" to calendar "43" of "principals/users/alice"',
				'dav.calendar_object.moved',
			],
			'card created' => [
				new CardCreatedEvent(7, self::ADDRESS_BOOK_DATA, [], self::CARD_DATA),
				'Card "bob.vcf" in address book "7" of "principals/users/alice" was created',
				'dav.card.created',
			],
			'card updated' => [
				new CardUpdatedEvent(7, self::ADDRESS_BOOK_DATA, [], self::CARD_DATA),
				'Card "bob.vcf" in address book "7" of "principals/users/alice" was updated',
				'dav.card.updated',
			],
			'card deleted' => [
				new CardDeletedEvent(7, self::ADDRESS_BOOK_DATA, [], self::CARD_DATA),
				'Card "bob.vcf" in address book "7" of "principals/users/alice" was deleted',
				'dav.card.deleted',
			],
			'card moved' => [
				new CardMovedEvent(7, self::ADDRESS_BOOK_DATA, 8, ['id' => 8, 'uri' => 'work', 'principaluri' => 'principals/users/alice'], [], [], self::CARD_DATA),
				'Card "bob.vcf" was moved from address book "7" to address book "8" of "principals/users/alice"',
				'dav.card.moved',
			],
		];
	}

	#[DataProvider('dataEvents')]
	public function testEvent(Event $event, string $renderedMessage, string $operation): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $auditEvent): bool => $auditEvent->getOperation() === $operation
				&& vsprintf($auditEvent->getLogMessage(), array_values($auditEvent->getParameters())) === $renderedMessage));

		$this->listener->handle($event);
	}

	public function testPublicUriIsNotLogged(): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $auditEvent): bool => !in_array('SECRETPUBLICURI', $auditEvent->getParameters(), true)));

		$this->listener->handle(new CalendarPublishedEvent(42, self::CALENDAR_DATA, 'SECRETPUBLICURI'));
	}

	public function testBirthdayCalendarObjectsAreNotLogged(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$birthdayCalendar = ['id' => 9, 'uri' => BirthdayService::BIRTHDAY_CALENDAR_URI, 'principaluri' => 'principals/users/alice'];
		$this->listener->handle(new CalendarObjectCreatedEvent(9, $birthdayCalendar, [], self::OBJECT_DATA));
		$this->listener->handle(new CalendarObjectDeletedEvent(9, $birthdayCalendar, [], self::OBJECT_DATA));
	}

	public function testSystemAddressBookCardsAreNotLogged(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');

		$systemAddressBook = ['id' => 1, 'uri' => 'system', 'principaluri' => 'principals/system/system'];
		$this->listener->handle(new CardCreatedEvent(1, $systemAddressBook, [], self::CARD_DATA));
		$this->listener->handle(new CardUpdatedEvent(1, $systemAddressBook, [], self::CARD_DATA));
	}

	public function testObjectContentIsNotLogged(): void {
		$this->dispatcher->expects($this->exactly(2))
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $auditEvent): bool => !str_contains(json_encode($auditEvent->getParameters()), 'Secret')));

		$this->listener->handle(new CalendarObjectUpdatedEvent(42, self::CALENDAR_DATA, [], self::OBJECT_DATA, self::OBJECT_DATA));
		$this->listener->handle(new CardUpdatedEvent(7, self::ADDRESS_BOOK_DATA, [], self::CARD_DATA));
	}
}
