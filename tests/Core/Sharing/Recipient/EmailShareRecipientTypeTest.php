<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Core\Sharing\Recipient;

use OC\Core\Sharing\Recipient\EmailShareRecipientType;
use OC\User\Database;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\Contacts\IManager;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class EmailShareRecipientTypeTest extends TestCase {
	private IUser $user1;

	private EmailShareRecipientType $recipientType;

	private function createUser(IUserManager $userManager, string $uid, string $password): IUser {
		$user = $userManager->createUser($uid, $password);
		$this->assertNotFalse($user);
		return $user;
	}

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend(new Database());

		$this->user1 = $this->createUser($userManager, 'user1', 'password');

		self::loginAsUser($this->user1->getUID());

		$this->recipientType = new EmailShareRecipientType();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->user1->delete();

		parent::tearDown();
	}

	public function testValidateRecipient(): void {
		$this->assertTrue($this->recipientType->validateRecipient('example@example.com'));
		$this->assertFalse($this->recipientType->validateRecipient('example'));
		$this->assertFalse($this->recipientType->validateRecipient('example@example'));
		$this->assertFalse($this->recipientType->validateRecipient('example.com'));
		$this->assertFalse($this->recipientType->validateRecipient('@'));
	}

	public function testGetRecipientDisplayName(): void {
		$this->assertEquals('example@example.com', $this->recipientType->getRecipientDisplayName('example@example.com'));
	}

	public function testSearchRecipients(): void {
		if (Server::get(IDBConnection::class)->getDatabaseProvider() === IDBConnection::PLATFORM_POSTGRES) {
			$this->markTestSkipped('PostgreSQL has unstable test results');
		}

		$cardDavBackend = Server::get(CardDavBackend::class);
		$addressBookId = $cardDavBackend->createAddressBook('principals/users/user1', 'Personal', []);

		$contactsManager = Server::get(IManager::class);
		foreach (['email1@example.com', 'email2@example.com', 'email3@example.com', 'email4@example.com'] as $email) {
			$contactsManager->createOrUpdate(['EMAIL' => $email], (string)$addressBookId);
		}

		$accessContext = new ShareAccessContext(currentUser: $this->user1);

		/** @psalm-suppress ArgumentTypeCoercion */
		$generateRecipient = static fn (string $email): ShareRecipient => new ShareRecipient(
			EmailShareRecipientType::class,
			$email,
			null,
		);

		$this->assertEquals(array_map($generateRecipient(...), ['email1@example.com', 'email2@example.com', 'email3@example.com']), $this->recipientType->searchRecipients($accessContext, 'email', 3, 0));
		$this->assertEquals(array_map($generateRecipient(...), ['email2@example.com', 'email3@example.com', 'email4@example.com']), $this->recipientType->searchRecipients($accessContext, 'email', 3, 1));
		$this->assertEquals(array_map($generateRecipient(...), ['email3@example.com', 'email4@example.com']), $this->recipientType->searchRecipients($accessContext, 'email', 4, 2));

		$this->assertEquals(array_map($generateRecipient(...), ['email1@example.com']), $this->recipientType->searchRecipients($accessContext, 'email', 1, 0));
		$this->assertEquals(array_map($generateRecipient(...), ['email1@example.com', 'email2@example.com']), $this->recipientType->searchRecipients($accessContext, 'email', 2, 0));
		$this->assertEquals(array_map($generateRecipient(...), ['email2@example.com', 'email3@example.com']), $this->recipientType->searchRecipients($accessContext, 'email', 2, 1));
		$this->assertEquals(array_map($generateRecipient(...), ['email3@example.com', 'email4@example.com']), $this->recipientType->searchRecipients($accessContext, 'email', 2, 2));
		$this->assertEquals(array_map($generateRecipient(...), ['email4@example.com']), $this->recipientType->searchRecipients($accessContext, 'email', 2, 3));

		$this->assertEquals(array_map($generateRecipient(...), ['email1@example.com']), $this->recipientType->searchRecipients($accessContext, 'email1', 2, 0));

		$cardDavBackend->deleteAddressBook($addressBookId);
	}
}
