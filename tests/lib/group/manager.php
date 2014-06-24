<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Group;

use OC\User\User;

class Manager extends \PHPUnit_Framework_TestCase {
	public function testGet() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$group = $manager->get('group1');
		$this->assertNotNull($group);
		$this->assertEquals('group1', $group->getGID());
	}

	public function testGetNoBackend() {
		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);

		$this->assertNull($manager->get('group1'));
	}

	public function testGetNotExists() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->once())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(false));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$this->assertNull($manager->get('group1'));
	}

	public function testGetDeleted() {
		$backend = new \OC_Group_Dummy();
		$backend->createGroup('group1');

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$group = $manager->get('group1');
		$group->delete();
		$this->assertNull($manager->get('group1'));
	}

	public function testGetMultipleBackends() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend1
		 */
		$backend1 = $this->getMock('\OC_Group_Database');
		$backend1->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(false));

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend2
		 */
		$backend2 = $this->getMock('\OC_Group_Database');
		$backend2->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend1);
		$manager->addBackend($backend2);

		$group = $manager->get('group1');
		$this->assertNotNull($group);
		$this->assertEquals('group1', $group->getGID());
	}

	public function testCreate() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backendGroupCreated = false;
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnCallback(function () use (&$backendGroupCreated) {
				return $backendGroupCreated;
			}));
		$backend->expects($this->once())
			->method('implementsActions')
			->will($this->returnValue(true));
		$backend->expects($this->once())
			->method('createGroup')
			->will($this->returnCallback(function () use (&$backendGroupCreated) {
				$backendGroupCreated = true;
			}));;

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$group = $manager->createGroup('group1');
		$this->assertEquals('group1', $group->getGID());
	}

	public function testCreateExists() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));
		$backend->expects($this->never())
			->method('createGroup');

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$group = $manager->createGroup('group1');
		$this->assertEquals('group1', $group->getGID());
	}

	public function testSearch() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->once())
			->method('getGroups')
			->with('1')
			->will($this->returnValue(array('group1')));
		$backend->expects($this->once())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$groups = $manager->search('1');
		$this->assertEquals(1, count($groups));
		$group1 = reset($groups);
		$this->assertEquals('group1', $group1->getGID());
	}

	public function testSearchMultipleBackends() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend1
		 */
		$backend1 = $this->getMock('\OC_Group_Database');
		$backend1->expects($this->once())
			->method('getGroups')
			->with('1')
			->will($this->returnValue(array('group1')));
		$backend1->expects($this->any())
			->method('groupExists')
			->will($this->returnValue(true));

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend2
		 */
		$backend2 = $this->getMock('\OC_Group_Database');
		$backend2->expects($this->once())
			->method('getGroups')
			->with('1')
			->will($this->returnValue(array('group12', 'group1')));
		$backend2->expects($this->any())
			->method('groupExists')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend1);
		$manager->addBackend($backend2);

		$groups = $manager->search('1');
		$this->assertEquals(2, count($groups));
		$group1 = reset($groups);
		$group12 = next($groups);
		$this->assertEquals('group1', $group1->getGID());
		$this->assertEquals('group12', $group12->getGID());
	}

	public function testSearchMultipleBackendsLimitAndOffset() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend1
		 */
		$backend1 = $this->getMock('\OC_Group_Database');
		$backend1->expects($this->once())
			->method('getGroups')
			->with('1', 2, 1)
			->will($this->returnValue(array('group1')));
		$backend1->expects($this->any())
			->method('groupExists')
			->will($this->returnValue(true));

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend2
		 */
		$backend2 = $this->getMock('\OC_Group_Database');
		$backend2->expects($this->once())
			->method('getGroups')
			->with('1', 2, 1)
			->will($this->returnValue(array('group12')));
		$backend2->expects($this->any())
			->method('groupExists')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend1);
		$manager->addBackend($backend2);

		$groups = $manager->search('1', 2, 1);
		$this->assertEquals(2, count($groups));
		$group1 = reset($groups);
		$group12 = next($groups);
		$this->assertEquals('group1', $group1->getGID());
		$this->assertEquals('group12', $group12->getGID());
	}

	public function testGetUserGroups() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->once())
			->method('getUserGroups')
			->with('user1')
			->will($this->returnValue(array('group1')));
		$backend->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$userBackend = $this->getMock('\OC_User_Backend');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$groups = $manager->getUserGroups(new User('user1', $userBackend));
		$this->assertEquals(1, count($groups));
		$group1 = reset($groups);
		$this->assertEquals('group1', $group1->getGID());
	}

	public function testGetUserGroupsMultipleBackends() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend1
		 */
		$backend1 = $this->getMock('\OC_Group_Database');
		$backend1->expects($this->once())
			->method('getUserGroups')
			->with('user1')
			->will($this->returnValue(array('group1')));
		$backend1->expects($this->any())
			->method('groupExists')
			->will($this->returnValue(true));

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend2
		 */
		$backend2 = $this->getMock('\OC_Group_Database');
		$backend2->expects($this->once())
			->method('getUserGroups')
			->with('user1')
			->will($this->returnValue(array('group1', 'group2')));
		$backend1->expects($this->any())
			->method('groupExists')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$userBackend = $this->getMock('\OC_User_Backend');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend1);
		$manager->addBackend($backend2);

		$groups = $manager->getUserGroups(new User('user1', $userBackend));
		$this->assertEquals(2, count($groups));
		$group1 = reset($groups);
		$group2 = next($groups);
		$this->assertEquals('group1', $group1->getGID());
		$this->assertEquals('group2', $group2->getGID());
	}

	public function testDisplayNamesInGroupMultipleUserBackends() {
		$userBackend = $this->getMock('\OC_User_Backend');

		$user1 = new User('user1', $userBackend);
		$user2 = new User('user2', $userBackend);
		$user3 = new User('user3', $userBackend);
		$user4 = new User('user33', $userBackend);

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend1
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->exactly(1))
			->method('groupExists')
			->with('testgroup')
			->will($this->returnValue(true));

		$backend->expects($this->once())
			->method('usersInGroup')
			->with('testgroup', '', -1, 0)
			->will($this->returnValue(array('user2', 'user33')));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$userBackend = $this->getMock('\OC_User_Backend');
		$userManager->expects($this->once())
			->method('search')
			->with('user3')
			->will($this->returnValue(array('user3' => $user3, 'user33' => $user4)));

		$userManager->expects($this->any())
			->method('get')
			->will($this->returnCallback(function($uid) use ($userBackend) {
				switch($uid) {
					case 'user1' : return new User('user1', $userBackend);
					case 'user2' : return new User('user2', $userBackend);
					case 'user3' : return new User('user3', $userBackend);
					case 'user33': return new User('user33', $userBackend);
					default:
						return null;
				}
			}));

		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$users = $manager->displayNamesInGroup('testgroup', 'user3');
		$this->assertEquals(1, count($users));
		$this->assertTrue(isset($users['user33']));
	}

	public function testGetUserGroupsWithAddUser() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$expectedGroups = array();
		$backend->expects($this->any())
			->method('getUserGroups')
			->with('user1')
			->will($this->returnCallback(function () use (&$expectedGroups) {
				return $expectedGroups;
			}));
		$backend->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));
		$backend->expects($this->once())
			->method('implementsActions')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		// prime cache
		$user1 = new User('user1', null);
		$groups = $manager->getUserGroups($user1);
		$this->assertEquals(array(), $groups);

		// add user
		$group = $manager->get('group1');
		$group->addUser($user1);
		$expectedGroups = array('group1');

		// check result
		$groups = $manager->getUserGroups($user1);
		$this->assertEquals(1, count($groups));
		$group1 = reset($groups);
		$this->assertEquals('group1', $group1->getGID());
	}

	public function testGetUserGroupsWithRemoveUser() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$expectedGroups = array('group1');
		$backend->expects($this->any())
			->method('getUserGroups')
			->with('user1')
			->will($this->returnCallback(function () use (&$expectedGroups) {
				return $expectedGroups;
			}));
		$backend->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));
		$backend->expects($this->once())
			->method('implementsActions')
			->will($this->returnValue(true));
		$backend->expects($this->once())
			->method('inGroup')
			->will($this->returnValue(true));
		$backend->expects($this->once())
			->method('removeFromGroup')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		// prime cache
		$user1 = new User('user1', null);
		$groups = $manager->getUserGroups($user1);
		$this->assertEquals(1, count($groups));
		$group1 = reset($groups);
		$this->assertEquals('group1', $group1->getGID());

		// remove user
		$group = $manager->get('group1');
		$group->removeUser($user1);
		$expectedGroups = array();

		// check result
		$groups = $manager->getUserGroups($user1);
		$this->assertEquals(array(), $groups);
	}
}
