<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Tests\Core\Sharing\Recipient;

use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OC\User\Database;
use OCA\Sharing\SharingBackend;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class UserShareRecipientTypeTest extends TestCase {
	private IDBConnection $dbConnection;

	private ISharingManager $manager;

	private IUser $user1;

	private IUser $user2;

	private IUser $user3;

	private IUser $user4;

	private UserShareRecipientType $recipientType;

	private const array DISPLAY_NAMES = [
		'user1' => 'User 1',
		'user2' => 'User 2',
		'user3' => 'User 3',
		'user4' => 'User 4',
	];

	private function createUser(IUserManager $userManager, string $uid, string $password): IUser {
		$user = $userManager->createUser($uid, $password);
		$this->assertNotFalse($user);
		$this->assertTrue($user->setDisplayName(self::DISPLAY_NAMES[$uid]));
		$user->setSystemEMailAddress($uid . '@example.com');
		return $user;
	}

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->dbConnection = Server::get(IDBConnection::class);

		$this->manager = Server::get(ISharingManager::class);

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend(new Database());

		$this->user1 = $this->createUser($userManager, 'user1', 'password');
		$this->user2 = $this->createUser($userManager, 'user2', 'password');
		$this->user3 = $this->createUser($userManager, 'user3', 'password');
		$this->user4 = $this->createUser($userManager, 'user4', 'password');

		$this->recipientType = new UserShareRecipientType(Server::get(IEventDispatcher::class), $this->dbConnection, $userManager, $this->manager);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->user1->delete();
		$this->user2->delete();
		$this->user3->delete();
		$this->user4->delete();

		parent::tearDown();
	}

	public function testValidateRecipient(): void {
		$this->assertTrue($this->recipientType->validateRecipient('user1'));
		$this->assertFalse($this->recipientType->validateRecipient('invalid'));
	}

	public function testGetRecipientValues(): void {
		$this->assertEquals(['user1'], $this->recipientType->getRecipients($this->user1, null));
	}

	public function testGetRecipientDisplayName(): void {
		$this->assertEquals('User 1', $this->recipientType->getRecipientDisplayName($this->user1->getUID()));
	}

	public function testSearchRecipients(): void {
		if (in_array(Server::get(IDBConnection::class)->getDatabaseProvider(), [IDBConnection::PLATFORM_POSTGRES, IDBConnection::PLATFORM_ORACLE], true)) {
			$this->markTestSkipped('PostgreSQL and Oracle have unstable test results');
		}

		$accessContext = new ShareAccessContext(currentUser: $this->user1);
		self::loginAsUser($this->user1->getUID());

		$generateRecipient = static fn (IUser $user): ShareRecipient => new ShareRecipient(
			UserShareRecipientType::class,
			$user->getUID(),
			null,
		);

		// The UserPlugin already removes the current user (user1 here), leading to one result less than requested.
		// This is an issue of the Collaborators API and can't be easily fixed.
		// If the following tests fail, because different numbers of results are returned: congratulations, you fixed the problem!

		$this->assertEquals(array_map($generateRecipient(...), [$this->user2, $this->user3, $this->user4]), $this->recipientType->searchRecipients($accessContext, 'user', 3, 0));
		$this->assertEquals(array_map($generateRecipient(...), [$this->user2, $this->user3, $this->user4]), $this->recipientType->searchRecipients($accessContext, 'user', 4, 0));
		// Wrong: Offset not applied correctly
		$this->assertEquals(array_map($generateRecipient(...), [$this->user2, $this->user3, $this->user4]), $this->recipientType->searchRecipients($accessContext, 'user', 4, 1));
		$this->assertEquals(array_map($generateRecipient(...), [$this->user3, $this->user4]), $this->recipientType->searchRecipients($accessContext, 'user', 4, 2));

		$this->assertEquals(array_map($generateRecipient(...), [$this->user2]), $this->recipientType->searchRecipients($accessContext, 'user', 1, 0));
		$this->assertEquals(array_map($generateRecipient(...), [$this->user2, $this->user3]), $this->recipientType->searchRecipients($accessContext, 'user', 2, 0));
		// Wrong: Offset not applied correctly
		$this->assertEquals(array_map($generateRecipient(...), [$this->user2, $this->user3, $this->user4]), $this->recipientType->searchRecipients($accessContext, 'user', 2, 1));
		$this->assertEquals(array_map($generateRecipient(...), [$this->user3, $this->user4]), $this->recipientType->searchRecipients($accessContext, 'user', 2, 2));
		$this->assertEquals(array_map($generateRecipient(...), [$this->user4]), $this->recipientType->searchRecipients($accessContext, 'user', 2, 3));

		$this->assertEquals(array_map($generateRecipient(...), [$this->user2]), $this->recipientType->searchRecipients($accessContext, 'user2', 2, 0));
		$this->assertEquals(array_map($generateRecipient(...), [$this->user2]), $this->recipientType->searchRecipients($accessContext, 'user2@example.com', 2, 0));
	}

	public function testDelete(): void {
		$registry = Server::get(ISharingRegistry::class);
		$registry->clear();
		$registry->registerSharingBackend(Server::get(SharingBackend::class));
		$registry->registerRecipientType($this->recipientType);

		$accessContext = new ShareAccessContext(currentUser: $this->user1);

		$this->dbConnection->beginTransaction();
		$id = $this->manager->createShare($accessContext);
		$this->manager->addShareRecipient($accessContext, $id, new ShareRecipient($this->recipientType::class, $this->user2->getUID(), null));
		$this->dbConnection->commit();

		$before = $this->manager->generateTimestamp();
		$this->user2->delete();
		$after = $this->manager->generateTimestamp();

		$this->dbConnection->beginTransaction();
		$share = $this->manager->getShare($accessContext, $id);
		$this->assertGreaterThanOrEqual($before, $share->lastUpdated);
		$this->assertLessThanOrEqual($after, $share->lastUpdated);
		$this->assertEquals([], $share->recipients);

		$this->manager->deleteShare($accessContext, $id);
		$this->dbConnection->commit();
		$registry->clear();
	}
}
