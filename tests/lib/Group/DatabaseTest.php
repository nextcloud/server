<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Group;

use OC\Group\Database;

/**
 * Class Database
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class DatabaseTest extends Backend {
	private $groups = [];

	/**
	 * get a new unique group name
	 * test cases can override this in order to clean up created groups
	 */
	public function getGroupName($name = null): string {
		$name = parent::getGroupName($name);
		$this->groups[] = $name;
		return $name;
	}

	protected function setUp(): void {
		parent::setUp();
		$this->backend = new Database();
	}

	protected function tearDown(): void {
		foreach ($this->groups as $group) {
			$this->backend->deleteGroup($group);
		}
		parent::tearDown();
	}

	public function testAddDoubleNoCache(): void {
		$group = $this->getGroupName();

		$this->backend->createGroup($group);

		$backend = new Database();
		$this->assertNull($backend->createGroup($group));
	}

	public function testAddLongGroupName(): void {
		$groupName = $this->getUniqueID('test_', 100);

		$gidCreated = $this->backend->createGroup($groupName);
		$this->assertEquals(64, strlen($gidCreated));

		$group = $this->backend->getGroupDetails($gidCreated);
		$this->assertEquals(['displayName' => $groupName], $group);
	}

	public function testNestedGroupCrud(): void {
		$parent = $this->getGroupName();
		$child = $this->getGroupName();
		$this->backend->createGroup($parent);
		$this->backend->createGroup($child);

		$this->assertTrue($this->backend->addGroupToGroup($child, $parent));
		$this->assertFalse($this->backend->addGroupToGroup($child, $parent), 'idempotent');
		$this->assertTrue($this->backend->groupInGroup($child, $parent));
		$this->assertSame([$child], $this->backend->getChildGroups($parent));
		$this->assertSame([$parent], $this->backend->getParentGroups($child));

		$this->assertTrue($this->backend->removeGroupFromGroup($child, $parent));
		$this->assertFalse($this->backend->removeGroupFromGroup($child, $parent));
		$this->assertFalse($this->backend->groupInGroup($child, $parent));
		$this->assertSame([], $this->backend->getChildGroups($parent));
	}

	public function testNestedGroupRejectsSelfEdge(): void {
		$gid = $this->getGroupName();
		$this->backend->createGroup($gid);

		$this->expectException(\InvalidArgumentException::class);
		$this->backend->addGroupToGroup($gid, $gid);
	}

	public function testNestedGroupRejectsCycle(): void {
		$a = $this->getGroupName();
		$b = $this->getGroupName();
		$c = $this->getGroupName();
		$this->backend->createGroup($a);
		$this->backend->createGroup($b);
		$this->backend->createGroup($c);

		// a -> b -> c
		$this->backend->addGroupToGroup($b, $a);
		$this->backend->addGroupToGroup($c, $b);

		// Adding a under c would close the cycle a -> b -> c -> a.
		$this->expectException(\InvalidArgumentException::class);
		$this->backend->addGroupToGroup($a, $c);
	}

	public function testDeleteGroupCleansNestedEdges(): void {
		$parent = $this->getGroupName();
		$child = $this->getGroupName();
		$this->backend->createGroup($parent);
		$this->backend->createGroup($child);
		$this->backend->addGroupToGroup($child, $parent);

		$this->backend->deleteGroup($parent);
		// The child group still exists but has no parents anymore
		$this->assertSame([], $this->backend->getParentGroups($child));
	}
}
