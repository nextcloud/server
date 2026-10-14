<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\AddressBookEventListener;
use OCA\DAV\Events\AddressBookCreatedEvent;
use OCA\DAV\Events\AddressBookDeletedEvent;
use OCA\DAV\Events\AddressBookShareUpdatedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AddressBookEventListenerTest extends TestCase {
	private const ADDRESS_BOOK_DATA = [
		'id' => 7,
		'uri' => 'contacts',
		'principaluri' => 'principals/users/alice',
	];

	private IAuditLogger&MockObject $logger;
	private AddressBookEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new AddressBookEventListener($this->logger);
	}

	public static function dataEvents(): array {
		return [
			'created' => [
				new AddressBookCreatedEvent(7, self::ADDRESS_BOOK_DATA),
				'Address book "7" of "principals/users/alice" was created with URI "contacts"',
				'dav.addressbook.created',
			],
			'deleted' => [
				new AddressBookDeletedEvent(7, self::ADDRESS_BOOK_DATA, [
					['href' => 'principal:principals/users/bob', 'commonName' => 'Bob', 'status' => 1, 'readOnly' => true],
				]),
				'Address book "7" of "principals/users/alice" was deleted, removing the shares with "principal:principals/users/bob"',
				'dav.addressbook.deleted',
			],
			'share updated' => [
				new AddressBookShareUpdatedEvent(7, self::ADDRESS_BOOK_DATA, [],
					[['href' => 'principal:principals/groups/staff', 'commonName' => 'Staff', 'readOnly' => true]],
					['principal:principals/users/bob', 'principal:principals/users/carol'],
				),
				'Shares of address book "7" of "principals/users/alice" were updated, added: "principal:principals/groups/staff", removed: "principal:principals/users/bob, principal:principals/users/carol"',
				'dav.addressbook_share.updated',
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
