<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Tests\Core\Sharing\Recipient;

use OC\Core\Sharing\Recipient\GroupShareRecipientType;
use OC\Group\Database;
use OCA\Sharing\SharingBackend;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
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
final class GroupShareRecipientTypeTest extends TestCase {
	private IDBConnection $dbConnection;

	private ISharingManager $manager;

	private IUser $user1;

	private IGroup $group1;

	private IGroup $group2;

	private IGroup $group3;

	private GroupShareRecipientType $recipientType;

	private const array DISPLAY_NAMES = [
		'group1' => 'Group 1',
		'group2' => 'Group 2',
		'group3' => 'Group 3',
	];

	private function createUser(IUserManager $userManager, string $uid, string $password): IUser {
		$user = $userManager->createUser($uid, $password);
		$this->assertNotFalse($user);
		return $user;
	}

	private function createGroup(IGroupManager $groupManager, string $gid): IGroup {
		$group = $groupManager->createGroup($gid);
		$this->assertNotNull($group);
		$this->assertTrue($group->setDisplayName(self::DISPLAY_NAMES[$gid]));
		return $group;
	}

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->dbConnection = Server::get(IDBConnection::class);

		$this->manager = Server::get(ISharingManager::class);

		$userManager = Server::get(IUserManager::class);
		$this->user1 = $this->createUser($userManager, 'user1', 'password');

		$groupManager = Server::get(IGroupManager::class);
		$groupManager->clearBackends();
		$groupManager->addBackend(new Database());

		$this->group1 = $this->createGroup($groupManager, 'group1');
		$this->group2 = $this->createGroup($groupManager, 'group2');
		$this->group3 = $this->createGroup($groupManager, 'group3');

		$this->group1->addUser($this->user1);

		$this->recipientType = new GroupShareRecipientType(Server::get(IEventDispatcher::class), $this->dbConnection, $this->manager);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->user1->delete();
		$this->group1->delete();
		$this->group2->delete();
		$this->group3->delete();

		parent::tearDown();
	}

	public function testValidateRecipient(): void {
		$this->assertTrue($this->recipientType->validateRecipient('group1'));
		$this->assertFalse($this->recipientType->validateRecipient('invalid'));
	}

	public function testGetRecipientValues(): void {
		$this->assertEquals(['group1'], $this->recipientType->getRecipients($this->user1, null));
	}

	public function testGetRecipientDisplayName(): void {
		// Clear display name cache, because setting the display name on the group doesn't update it in the cache of the manager
		self::invokePrivate(self::invokePrivate(Server::get(IGroupManager::class), 'displayNameCache'), 'clear');

		$this->assertEquals('Group 1', $this->recipientType->getRecipientDisplayName($this->group1->getGID()));
	}

	public function testSearchRecipients(): void {
		$accessContext = new ShareAccessContext(currentUser: $this->user1);
		self::loginAsUser($this->user1->getUID());

		$generateRecipient = static fn (IGroup $group): ShareRecipient => new ShareRecipient(
			GroupShareRecipientType::class,
			$group->getGID(),
			null,
		);

		$this->assertEquals(array_map($generateRecipient(...), [$this->group1, $this->group2, $this->group3]), $this->recipientType->searchRecipients($accessContext, 'group', 3, 0));
		$this->assertEquals(array_map($generateRecipient(...), [$this->group1]), $this->recipientType->searchRecipients($accessContext, 'group', 1, 0));
		$this->assertEquals(array_map($generateRecipient(...), [$this->group2, $this->group3]), $this->recipientType->searchRecipients($accessContext, 'group', 3, 1));
		$this->assertEquals(array_map($generateRecipient(...), [$this->group2]), $this->recipientType->searchRecipients($accessContext, 'group', 1, 1));

		$this->assertEquals(array_map($generateRecipient(...), [$this->group1]), $this->recipientType->searchRecipients($accessContext, 'group1', 1, 0));
	}

	public function testDelete(): void {
		$registry = Server::get(ISharingRegistry::class);
		$registry->clear();
		$registry->registerSharingBackend(Server::get(SharingBackend::class));
		$registry->registerRecipientType($this->recipientType);

		$accessContext = new ShareAccessContext(currentUser: $this->user1);

		$this->dbConnection->beginTransaction();
		$id = $this->manager->createShare($accessContext);
		$this->manager->addShareRecipient($accessContext, $id, new ShareRecipient($this->recipientType::class, $this->group1->getGID(), null));
		$this->dbConnection->commit();

		$before = $this->manager->generateTimestamp();
		$this->group1->delete();
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
