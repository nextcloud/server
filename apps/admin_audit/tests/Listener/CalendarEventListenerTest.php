<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\CalendarEventListener;
use OCA\DAV\Events\CalendarCreatedEvent;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarMovedToTrashEvent;
use OCA\DAV\Events\CalendarPublishedEvent;
use OCA\DAV\Events\CalendarRestoredEvent;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCA\DAV\Events\CalendarUnpublishedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalendarEventListenerTest extends TestCase {
	private const CALENDAR_DATA = [
		'id' => 42,
		'uri' => 'personal',
		'principaluri' => 'principals/users/alice',
	];

	private IAuditLogger&MockObject $logger;
	private CalendarEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new CalendarEventListener($this->logger);
	}

	public static function dataEvents(): array {
		$shares = [
			['href' => 'principal:principals/users/bob', 'commonName' => 'Bob', 'status' => 1, 'readOnly' => true, '{http://owncloud.org/ns}principal' => 'principals/users/bob', '{http://owncloud.org/ns}group-share' => false],
			['href' => 'principal:principals/groups/staff', 'commonName' => 'Staff', 'status' => 1, 'readOnly' => false, '{http://owncloud.org/ns}principal' => 'principals/groups/staff', '{http://owncloud.org/ns}group-share' => true],
		];

		return [
			'created' => [
				new CalendarCreatedEvent(42, self::CALENDAR_DATA),
				'Calendar "42" of "principals/users/alice" was created with URI "personal"',
				'dav.calendar.created',
			],
			'trashed' => [
				new CalendarMovedToTrashEvent(42, self::CALENDAR_DATA, $shares),
				'Calendar "42" of "principals/users/alice" was moved to trash',
				'dav.calendar.trashed',
			],
			'deleted' => [
				new CalendarDeletedEvent(42, self::CALENDAR_DATA, $shares),
				'Calendar "42" of "principals/users/alice" was deleted, removing the shares with "principal:principals/users/bob, principal:principals/groups/staff"',
				'dav.calendar.deleted',
			],
			'restored' => [
				new CalendarRestoredEvent(42, self::CALENDAR_DATA, []),
				'Calendar "42" of "principals/users/alice" was restored from trash',
				'dav.calendar.restored',
			],
			'published' => [
				new CalendarPublishedEvent(42, self::CALENDAR_DATA, 'SECRETPUBLICURI'),
				'Calendar "42" of "principals/users/alice" was published',
				'dav.calendar.published',
			],
			'unpublished' => [
				new CalendarUnpublishedEvent(42, self::CALENDAR_DATA),
				'Calendar "42" of "principals/users/alice" was unpublished',
				'dav.calendar.unpublished',
			],
			'share updated' => [
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
			'unshare' => [
				new CalendarShareUpdatedEvent(42, self::CALENDAR_DATA, [], [], ['principal:principals/users/bob']),
				'Shares of calendar "42" of "principals/users/alice" were updated, added: "", removed: "principal:principals/users/bob"',
				'dav.calendar_share.updated',
			],
		];
	}

	#[DataProvider('dataEvents')]
	public function testEvent(Event $event, string $message, string $operation): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$message,
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === $operation),
			);

		$this->listener->handle($event);
	}
}
