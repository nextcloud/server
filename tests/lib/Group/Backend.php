<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Group;

/**
 * Class Backend
 *
 * @group DB
 */
abstract class Backend extends \Test\TestCase {
	/**
	 * @var \OC\Group\Backend $backend
	 */
	protected $backend;

	/**
	 * get a new unique group name
	 * test cases can override this in order to clean up created groups
	 *
	 * @return string
	 */
	public function getGroupName($name = null) {
		if (is_null($name)) {
			return $this->getUniqueID('test_');
		} else {
			return $name;
		}
	}

	/**
	 * get a new unique user name
	 * test cases can override this in order to clean up created user
	 *
	 * @return string
	 */
	public function getUserName() {
		return $this->getUniqueID('test_');
	}

	public function testAddRemove(): void {
		//get the number of groups we start with, in case there are exising groups
		$startCount = count($this->backend->getGroups());

		$name1 = $this->getGroupName();
		$name2 = $this->getGroupName();
		$this->backend->createGroup($name1);
		$count = count($this->backend->getGroups()) - $startCount;
		$this->assertEquals(1, $count);
		$this->assertTrue((array_search($name1, $this->backend->getGroups()) !== false));
		$this->assertFalse((array_search($name2, $this->backend->getGroups()) !== false));
		$this->backend->createGroup($name2);
		$count = count($this->backend->getGroups()) - $startCount;
		$this->assertEquals(2, $count);
		$this->assertTrue((array_search($name1, $this->backend->getGroups()) !== false));
		$this->assertTrue((array_search($name2, $this->backend->getGroups()) !== false));

		$this->backend->deleteGroup($name2);
		$count = count($this->backend->getGroups()) - $startCount;
		$this->assertEquals(1, $count);
		$this->assertTrue((array_search($name1, $this->backend->getGroups()) !== false));
		$this->assertFalse((array_search($name2, $this->backend->getGroups()) !== false));
	}

	public function testUser(): void {
		$group1 = $this->getGroupName();
		$group2 = $this->getGroupName();
		$this->backend->createGroup($group1);
		$this->backend->createGroup($group2);

		$user1 = $this->getUserName();
		$user2 = $this->getUserName();

		$this->assertFalse($this->backend->inGroup($user1, $group1));
		$this->assertFalse($this->backend->inGroup($user2, $group1));
		$this->assertFalse($this->backend->inGroup($user1, $group2));
		$this->assertFalse($this->backend->inGroup($user2, $group2));

		$this->assertTrue($this->backend->addToGroup($user1, $group1));

		$this->assertTrue($this->backend->inGroup($user1, $group1));
		$this->assertFalse($this->backend->inGroup($user2, $group1));
		$this->assertFalse($this->backend->inGroup($user1, $group2));
		$this->assertFalse($this->backend->inGroup($user2, $group2));

		$this->assertFalse($this->backend->addToGroup($user1, $group1));

		$this->assertEquals([$user1], $this->backend->usersInGroup($group1));
		$this->assertEquals([], $this->backend->usersInGroup($group2));

		$this->assertEquals([$group1], $this->backend->getUserGroups($user1));
		$this->assertEquals([], $this->backend->getUserGroups($user2));

		$this->backend->deleteGroup($group1);
		$this->assertEquals([], $this->backend->getUserGroups($user1));
		$this->assertEquals([], $this->backend->usersInGroup($group1));
		$this->assertFalse($this->backend->inGroup($user1, $group1));
	}

	public function testSearchGroups(): void {
		$name1 = $this->getGroupName('foobarbaz');
		$name2 = $this->getGroupName('bazfoobarfoo');
		$name3 = $this->getGroupName('notme');

		$this->backend->createGroup($name1);
		$this->backend->createGroup($name2);
		$this->backend->createGroup($name3);

		$result = $this->backend->getGroups('foobar');
		$this->assertSame(2, count($result));
	}

	public function testSearchUsers(): void {
		$group = $this->getGroupName();
		$this->backend->createGroup($group);

		$name1 = 'foobarbaz';
		$name2 = 'bazbarfoo';
		$name3 = 'notme';

		$this->backend->addToGroup($name1, $group);
		$this->backend->addToGroup($name2, $group);
		$this->backend->addToGroup($name3, $group);

		$result = $this->backend->usersInGroup($group, 'bar');
		$this->assertSame(2, count($result));

		$result = $this->backend->countUsersInGroup($group, 'bar');
		$this->assertSame(2, $result);
	}

	public function testAddDouble(): void {
		$group = $this->getGroupName();

		$this->backend->createGroup($group);
		$this->backend->createGroup($group);

		$this->addToAssertionCount(1);
	}
}
