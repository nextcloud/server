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
 *
 * @group DB
 */
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
}
