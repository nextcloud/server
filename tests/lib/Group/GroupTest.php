<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Group;

use OC\Group\Group;
use OC\User\User;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;

class GroupTest extends \Test\TestCase {
	/** @var IEventDispatcher|MockObject */
	protected $dispatcher;

	protected function setUp(): void {
		parent::setUp();
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
	}

	/**
	 * @param string $uid
	 * @param \OC\User\Backend $backend
	 * @return User
	 */
	private function newUser($uid, \OC\User\Backend $backend) {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($uid);
		$user->method('getBackend')
			->willReturn($backend);

		return $user;
	}

	/**
	 * @return \OC\User\Manager
	 */
	protected function getUserManager() {
		$userManager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$backend = $this->getMockBuilder('\OC\User\Backend')
			->disableOriginalConstructor()
			->getMock();
		$user1 = $this->newUser('user1', $backend);
		$user2 = $this->newUser('user2', $backend);
		$user3 = $this->newUser('user3', $backend);
		$userManager->expects($this->any())
			->method('get')
			->willReturnMap([
				['user1', $user1],
				['user2', $user2],
				['user3', $user3]
			]);
		return $userManager;
	}

	public function testGetUsersSingleBackend(): void {
		$backend = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend], $this->dispatcher, $userManager);

		$backend->expects($this->once())
			->method('usersInGroup')
			->with('group1')
			->willReturn(['user1', 'user2']);

		$users = $group->getUsers();

		$this->assertEquals(2, count($users));
		$user1 = $users['user1'];
		$user2 = $users['user2'];
		$this->assertEquals('user1', $user1->getUID());
		$this->assertEquals('user2', $user2->getUID());
	}

	public function testGetUsersMultipleBackends(): void {
		$backend1 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$backend2 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend1, $backend2], $this->dispatcher, $userManager);

		$backend1->expects($this->once())
			->method('usersInGroup')
			->with('group1')
			->willReturn(['user1', 'user2']);

		$backend2->expects($this->once())
			->method('usersInGroup')
			->with('group1')
			->willReturn(['user2', 'user3']);

		$users = $group->getUsers();

		$this->assertEquals(3, count($users));
		$user1 = $users['user1'];
		$user2 = $users['user2'];
		$user3 = $users['user3'];
		$this->assertEquals('user1', $user1->getUID());
		$this->assertEquals('user2', $user2->getUID());
		$this->assertEquals('user3', $user3->getUID());
	}

	public function testInGroupSingleBackend(): void {
		$backend = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$userBackend = $this->getMockBuilder('\OC\User\Backend')
			->disableOriginalConstructor()
			->getMock();
		$group = new Group('group1', [$backend], $this->dispatcher, $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->willReturn(true);

		$this->assertTrue($group->inGroup($this->newUser('user1', $userBackend)));
	}

	public function testInGroupMultipleBackends(): void {
		$backend1 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$backend2 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$userBackend = $this->getMockBuilder(\OC\User\Backend::class)
			->disableOriginalConstructor()
			->getMock();
		$group = new Group('group1', [$backend1, $backend2], $this->dispatcher, $userManager);

		$backend1->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->willReturn(false);

		$backend2->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->willReturn(true);

		$this->assertTrue($group->inGroup($this->newUser('user1', $userBackend)));
	}

	public function testAddUser(): void {
		$backend = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$userBackend = $this->getMockBuilder('\OC\User\Backend')
			->disableOriginalConstructor()
			->getMock();
		$group = new Group('group1', [$backend], $this->dispatcher, $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->willReturn(false);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend->expects($this->once())
			->method('addToGroup')
			->with('user1', 'group1');

		$group->addUser($this->newUser('user1', $userBackend));
	}

	public function testAddUserAlreadyInGroup(): void {
		$backend = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$userBackend = $this->getMockBuilder('\OC\User\Backend')
			->disableOriginalConstructor()
			->getMock();
		$group = new Group('group1', [$backend], $this->dispatcher, $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->willReturn(true);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend->expects($this->never())
			->method('addToGroup');

		$group->addUser($this->newUser('user1', $userBackend));
	}

	public function testRemoveUser(): void {
		$backend = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$userBackend = $this->getMockBuilder('\OC\User\Backend')
			->disableOriginalConstructor()
			->getMock();
		$group = new Group('group1', [$backend], $this->dispatcher, $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->willReturn(true);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend->expects($this->once())
			->method('removeFromGroup')
			->with('user1', 'group1');

		$group->removeUser($this->newUser('user1', $userBackend));
	}

	public function testRemoveUserNotInGroup(): void {
		$backend = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$userBackend = $this->getMockBuilder(\OC\User\Backend::class)
			->disableOriginalConstructor()
			->getMock();
		$group = new Group('group1', [$backend], $this->dispatcher, $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->willReturn(false);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend->expects($this->never())
			->method('removeFromGroup');

		$group->removeUser($this->newUser('user1', $userBackend));
	}

	public function testRemoveUserMultipleBackends(): void {
		$backend1 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$backend2 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$userBackend = $this->getMockBuilder('\OC\User\Backend')
			->disableOriginalConstructor()
			->getMock();
		$group = new Group('group1', [$backend1, $backend2], $this->dispatcher, $userManager);

		$backend1->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->willReturn(true);
		$backend1->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend1->expects($this->once())
			->method('removeFromGroup')
			->with('user1', 'group1');

		$backend2->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->willReturn(true);
		$backend2->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend2->expects($this->once())
			->method('removeFromGroup')
			->with('user1', 'group1');

		$group->removeUser($this->newUser('user1', $userBackend));
	}

	public function testSearchUsers(): void {
		$backend = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend], $this->dispatcher, $userManager);

		$backend->expects($this->once())
			->method('searchInGroup')
			->with('group1', '2')
			->willReturn(['user2' => new User('user2', null, $this->dispatcher)]);

		$users = $group->searchUsers('2');

		$this->assertEquals(1, count($users));
		$user2 = reset($users);
		$this->assertEquals('user2', $user2->getUID());
	}

	public function testSearchUsersMultipleBackends(): void {
		$backend1 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$backend2 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend1, $backend2], $this->dispatcher, $userManager);

		$backend1->expects($this->once())
			->method('searchInGroup')
			->with('group1', '2')
			->willReturn(['user2' => new User('user2', null, $this->dispatcher)]);
		$backend2->expects($this->once())
			->method('searchInGroup')
			->with('group1', '2')
			->willReturn(['user2' => new User('user2', null, $this->dispatcher)]);

		$users = $group->searchUsers('2');

		$this->assertEquals(1, count($users));
		$user2 = reset($users);
		$this->assertEquals('user2', $user2->getUID());
	}

	public function testSearchUsersLimitAndOffset(): void {
		$backend = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend], $this->dispatcher, $userManager);

		$backend->expects($this->once())
			->method('searchInGroup')
			->with('group1', 'user', 1, 1)
			->willReturn(['user2' => new User('user2', null, $this->dispatcher)]);

		$users = $group->searchUsers('user', 1, 1);

		$this->assertEquals(1, count($users));
		$user2 = reset($users);
		$this->assertEquals('user2', $user2->getUID());
	}

	public function testSearchUsersMultipleBackendsLimitAndOffset(): void {
		$backend1 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$backend2 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend1, $backend2], $this->dispatcher, $userManager);

		$backend1->expects($this->once())
			->method('searchInGroup')
			->with('group1', 'user', 2, 1)
			->willReturn(['user2' => new User('user2', null, $this->dispatcher)]);
		$backend2->expects($this->once())
			->method('searchInGroup')
			->with('group1', 'user', 2, 1)
			->willReturn(['user1' => new User('user1', null, $this->dispatcher)]);

		$users = $group->searchUsers('user', 2, 1);

		$this->assertEquals(2, count($users));
		$user2 = reset($users);
		$user1 = next($users);
		$this->assertEquals('user2', $user2->getUID());
		$this->assertEquals('user1', $user1->getUID());
	}

	public function testCountUsers(): void {
		$backend1 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend1], $this->dispatcher, $userManager);

		$backend1->expects($this->once())
			->method('countUsersInGroup')
			->with('group1', '2')
			->willReturn(3);

		$backend1->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$users = $group->count('2');

		$this->assertSame(3, $users);
	}

	public function testCountUsersMultipleBackends(): void {
		$backend1 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$backend2 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend1, $backend2], $this->dispatcher, $userManager);

		$backend1->expects($this->once())
			->method('countUsersInGroup')
			->with('group1', '2')
			->willReturn(3);
		$backend1->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend2->expects($this->once())
			->method('countUsersInGroup')
			->with('group1', '2')
			->willReturn(4);
		$backend2->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$users = $group->count('2');

		$this->assertSame(7, $users);
	}

	public function testCountUsersNoMethod(): void {
		$backend1 = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend1], $this->dispatcher, $userManager);

		$backend1->expects($this->never())
			->method('countUsersInGroup');
		$backend1->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$users = $group->count('2');

		$this->assertSame(false, $users);
	}

	public function testDelete(): void {
		$backend = $this->getMockBuilder('OC\Group\Database')
			->disableOriginalConstructor()
			->getMock();
		$userManager = $this->getUserManager();
		$group = new Group('group1', [$backend], $this->dispatcher, $userManager);

		$backend->expects($this->once())
			->method('deleteGroup')
			->with('group1');
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$group->delete();
	}
}
