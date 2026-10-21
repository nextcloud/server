<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\ShareUser;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\ContactsManager;
use OCP\Contacts\IManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;
use Test\Traits\UserTrait;

#[Group('DB')]
final class ShareUserTest extends TestCase {
	use UserTrait;

	private ISharingRegistry $registry;

	private IUser $user;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(ISharingRegistry::class);
		$this->registry->clear();

		$this->user = $this->createUser('user', 'password');
		$this->userBackend->setDisplayName($this->user->getUID(), 'Local User');
	}

	#[\Override]
	public function tearDown(): void {
		$this->registry->clear();

		parent::tearDown();
	}

	public function testFormatLocalUser(): void {
		$user = new ShareUser($this->user->getUID(), null);

		$this->assertEquals([
			'user_id' => 'user',
			'instance' => null,
			'display_name' => 'Local User',
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/user/64',
				'dark' => 'http://localhost/index.php/avatar/user/64/dark',
			],
		], $user->format(Server::get(IUserManager::class)));
	}

	public function testFormatRemoteUser(): void {
		$cardDavBackend = Server::get(CardDavBackend::class);
		$contactsManager = Server::get(IManager::class);
		$addressBookId = $cardDavBackend->createAddressBook('principals/users/' . $this->user->getUID(), 'Personal', []);
		Server::get(ContactsManager::class)->setupContactsProvider($contactsManager, $this->user->getUID(), Server::get(IURLGenerator::class));

		$contactsManager->createOrUpdate(['CLOUD' => $this->user->getUID() . '@https://example.com', 'FN' => 'Remote User'], (string)$addressBookId);

		$user = new ShareUser($this->user->getUID(), 'https://example.com');

		$this->assertEquals([
			'user_id' => 'user',
			'instance' => 'https://example.com',
			'display_name' => 'Remote User',
			'icon' => [
				'light' => 'http://localhost/index.php/avatar/user@https%253A%2F%2Fexample.com/64',
				'dark' => 'http://localhost/index.php/avatar/user@https%253A%2F%2Fexample.com/64/dark',
			],
		], $user->format(Server::get(IUserManager::class)));

		$cardDavBackend->deleteAddressBook($addressBookId);
	}
}
