<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User;

use OC\User\Database;
use OC\User\User;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\Security\Events\ValidatePasswordPolicyEvent;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DatabaseTest
 *
 * @group DB
 */
class DatabaseTest extends Backend {
	/** @var array */
	private $users;
	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var \OC\User\Database */
	protected $backend;

	public function getUser() {
		$user = parent::getUser();
		$this->users[] = $user;
		return $user;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->backend = new Database($this->eventDispatcher);
	}

	protected function tearDown(): void {
		if (!isset($this->users)) {
			return;
		}
		foreach ($this->users as $user) {
			$this->backend->deleteUser($user);
		}
		parent::tearDown();
	}

	public function testVerifyPasswordEvent(): void {
		$user = $this->getUser();
		$this->backend->createUser($user, 'pass1');

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')
			->willReturnCallback(
				function (Event $event): void {
					$this->assertInstanceOf(ValidatePasswordPolicyEvent::class, $event);
					/** @var ValidatePasswordPolicyEvent $event */
					$this->assertSame('newpass', $event->getPassword());
				}
			);

		$this->backend->setPassword($user, 'newpass');
		$this->assertSame($user, $this->backend->checkPassword($user, 'newpass'));
	}


	public function testVerifyPasswordEventFail(): void {
		$this->expectException(HintException::class);
		$this->expectExceptionMessage('password change failed');

		$user = $this->getUser();
		$this->backend->createUser($user, 'pass1');

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')
			->willReturnCallback(
				function (Event $event): void {
					$this->assertInstanceOf(ValidatePasswordPolicyEvent::class, $event);
					/** @var ValidatePasswordPolicyEvent $event */
					$this->assertSame('newpass', $event->getPassword());
					throw new HintException('password change failed', 'password change failed');
				}
			);

		$this->backend->setPassword($user, 'newpass');
		$this->assertSame($user, $this->backend->checkPassword($user, 'newpass'));
	}

	public function testCreateUserInvalidatesCache(): void {
		$user1 = $this->getUniqueID('test_');
		$this->assertFalse($this->backend->userExists($user1));
		$this->backend->createUser($user1, 'pw');
		$this->assertTrue($this->backend->userExists($user1));
	}

	public function testDeleteUserInvalidatesCache(): void {
		$user1 = $this->getUniqueID('test_');
		$this->backend->createUser($user1, 'pw');
		$this->assertTrue($this->backend->userExists($user1));
		$this->backend->deleteUser($user1);
		$this->assertFalse($this->backend->userExists($user1));
		$this->backend->createUser($user1, 'pw2');
		$this->assertTrue($this->backend->userExists($user1));
	}

	public function testSearch(): void {
		parent::testSearch();

		$user1 = $this->getUser();
		$this->backend->createUser($user1, 'pass1');

		$user2 = $this->getUser();
		$this->backend->createUser($user2, 'pass1');

		$user1Obj = new User($user1, $this->backend, $this->createMock(IEventDispatcher::class));
		$user2Obj = new User($user2, $this->backend, $this->createMock(IEventDispatcher::class));
		$emailAddr1 = "$user1@nextcloud.com";
		$emailAddr2 = "$user2@nextcloud.com";

		$user1Obj->setDisplayName('User 1 Display');

		$result = $this->backend->getDisplayNames('display');
		$this->assertCount(1, $result);

		$result = $this->backend->getDisplayNames(strtoupper($user1));
		$this->assertCount(1, $result);

		$user1Obj->setEMailAddress($emailAddr1);
		$user2Obj->setEMailAddress($emailAddr2);

		$result = $this->backend->getUsers('@nextcloud.com');
		$this->assertCount(2, $result);

		$result = $this->backend->getDisplayNames('@nextcloud.com');
		$this->assertCount(2, $result);

		$result = $this->backend->getDisplayNames('@nextcloud.COM');
		$this->assertCount(2, $result);
	}

	public function testUserCount(): void {
		$base = $this->backend->countUsers() ?: 0;
		$users = $this->backend->getUsers();
		self::assertEquals($base, count($users));

		$user = $this->getUser();
		$this->backend->createUser($user, $user);
		self::assertEquals($base + 1, $this->backend->countUsers());
	}
}
