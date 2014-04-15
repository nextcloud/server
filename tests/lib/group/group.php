<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Group;

use OC\User\User;

class Group extends \PHPUnit_Framework_TestCase {
	/**
	 * @return \OC\User\Manager | \OC\User\Manager
	 */
	protected function getUserManager() {
		$userManager = $this->getMock('\OC\User\Manager');
		$user1 = new User('user1', null);
		$user2 = new User('user2', null);
		$user3 = new User('user3', null);
		$userManager->expects($this->any())
			->method('get')
			->will($this->returnValueMap(array(
				array('user1', $user1),
				array('user2', $user2),
				array('user3', $user3)
			)));
		return $userManager;
	}

	public function testGetUsersSingleBackend() {
		$backend = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend), $userManager);

		$backend->expects($this->once())
			->method('usersInGroup')
			->with('group1')
			->will($this->returnValue(array('user1', 'user2')));

		$users = $group->getUsers();

		$this->assertEquals(2, count($users));
		$user1 = $users['user1'];
		$user2 = $users['user2'];
		$this->assertEquals('user1', $user1->getUID());
		$this->assertEquals('user2', $user2->getUID());
	}

	public function testGetUsersMultipleBackends() {
		$backend1 = $this->getMock('OC_Group_Database');
		$backend2 = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend1, $backend2), $userManager);

		$backend1->expects($this->once())
			->method('usersInGroup')
			->with('group1')
			->will($this->returnValue(array('user1', 'user2')));

		$backend2->expects($this->once())
			->method('usersInGroup')
			->with('group1')
			->will($this->returnValue(array('user2', 'user3')));

		$users = $group->getUsers();

		$this->assertEquals(3, count($users));
		$user1 = $users['user1'];
		$user2 = $users['user2'];
		$user3 = $users['user3'];
		$this->assertEquals('user1', $user1->getUID());
		$this->assertEquals('user2', $user2->getUID());
		$this->assertEquals('user3', $user3->getUID());
	}

	public function testInGroupSingleBackend() {
		$backend = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend), $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->will($this->returnValue(true));

		$this->assertTrue($group->inGroup(new User('user1', null)));
	}

	public function testInGroupMultipleBackends() {
		$backend1 = $this->getMock('OC_Group_Database');
		$backend2 = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend1, $backend2), $userManager);

		$backend1->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->will($this->returnValue(false));

		$backend2->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->will($this->returnValue(true));

		$this->assertTrue($group->inGroup(new User('user1', null)));
	}

	public function testAddUser() {
		$backend = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend), $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->will($this->returnValue(false));
		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend->expects($this->once())
			->method('addToGroup')
			->with('user1', 'group1');

		$group->addUser(new User('user1', null));
	}

	public function testAddUserAlreadyInGroup() {
		$backend = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend), $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->will($this->returnValue(true));
		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend->expects($this->never())
			->method('addToGroup');

		$group->addUser(new User('user1', null));
	}

	public function testRemoveUser() {
		$backend = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend), $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->will($this->returnValue(true));
		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend->expects($this->once())
			->method('removeFromGroup')
			->with('user1', 'group1');

		$group->removeUser(new User('user1', null));
	}

	public function testRemoveUserNotInGroup() {
		$backend = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend), $userManager);

		$backend->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->will($this->returnValue(false));
		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend->expects($this->never())
			->method('removeFromGroup');

		$group->removeUser(new User('user1', null));
	}

	public function testRemoveUserMultipleBackends() {
		$backend1 = $this->getMock('OC_Group_Database');
		$backend2 = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend1, $backend2), $userManager);

		$backend1->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->will($this->returnValue(true));
		$backend1->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend1->expects($this->once())
			->method('removeFromGroup')
			->with('user1', 'group1');

		$backend2->expects($this->once())
			->method('inGroup')
			->with('user1', 'group1')
			->will($this->returnValue(true));
		$backend2->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend2->expects($this->once())
			->method('removeFromGroup')
			->with('user1', 'group1');

		$group->removeUser(new User('user1', null));
	}

	public function testSearchUsers() {
		$backend = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend), $userManager);

		$backend->expects($this->once())
			->method('usersInGroup')
			->with('group1', '2')
			->will($this->returnValue(array('user2')));

		$users = $group->searchUsers('2');

		$this->assertEquals(1, count($users));
		$user2 = $users[0];
		$this->assertEquals('user2', $user2->getUID());
	}

	public function testSearchUsersMultipleBackends() {
		$backend1 = $this->getMock('OC_Group_Database');
		$backend2 = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend1, $backend2), $userManager);

		$backend1->expects($this->once())
			->method('usersInGroup')
			->with('group1', '2')
			->will($this->returnValue(array('user2')));
		$backend2->expects($this->once())
			->method('usersInGroup')
			->with('group1', '2')
			->will($this->returnValue(array('user2')));

		$users = $group->searchUsers('2');

		$this->assertEquals(1, count($users));
		$user2 = $users[0];
		$this->assertEquals('user2', $user2->getUID());
	}

	public function testSearchUsersLimitAndOffset() {
		$backend = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend), $userManager);

		$backend->expects($this->once())
			->method('usersInGroup')
			->with('group1', 'user', 1, 1)
			->will($this->returnValue(array('user2')));

		$users = $group->searchUsers('user', 1, 1);

		$this->assertEquals(1, count($users));
		$user2 = $users[0];
		$this->assertEquals('user2', $user2->getUID());
	}

	public function testSearchUsersMultipleBackendsLimitAndOffset() {
		$backend1 = $this->getMock('OC_Group_Database');
		$backend2 = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend1, $backend2), $userManager);

		$backend1->expects($this->once())
			->method('usersInGroup')
			->with('group1', 'user', 2, 1)
			->will($this->returnValue(array('user2')));
		$backend2->expects($this->once())
			->method('usersInGroup')
			->with('group1', 'user', 2, 1)
			->will($this->returnValue(array('user1')));

		$users = $group->searchUsers('user', 2, 1);

		$this->assertEquals(2, count($users));
		$user2 = $users[0];
		$user1 = $users[1];
		$this->assertEquals('user2', $user2->getUID());
		$this->assertEquals('user1', $user1->getUID());
	}

	public function testCountUsers() {
		$backend1 = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend1), $userManager);

		$backend1->expects($this->once())
			->method('countUsersInGroup')
			->with('group1', '2')
			->will($this->returnValue(3));

		$backend1->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$users = $group->count('2');

		$this->assertSame(3, $users);
	}

	public function testCountUsersMultipleBackends() {
		$backend1 = $this->getMock('OC_Group_Database');
		$backend2 = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend1, $backend2), $userManager);

		$backend1->expects($this->once())
			->method('countUsersInGroup')
			->with('group1', '2')
			->will($this->returnValue(3));
		$backend1->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend2->expects($this->once())
			->method('countUsersInGroup')
			->with('group1', '2')
			->will($this->returnValue(4));
		$backend2->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$users = $group->count('2');

		$this->assertSame(7, $users);
	}

	public function testCountUsersNoMethod() {
		$backend1 = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend1), $userManager);

		$backend1->expects($this->never())
			->method('countUsersInGroup');
		$backend1->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$users = $group->count('2');

		$this->assertSame(false, $users);
	}

	public function testDelete() {
		$backend = $this->getMock('OC_Group_Database');
		$userManager = $this->getUserManager();
		$group = new \OC\Group\Group('group1', array($backend), $userManager);

		$backend->expects($this->once())
			->method('deleteGroup')
			->with('group1');
		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$group->delete();
	}
}
