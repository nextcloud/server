<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Group;

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
	 *
	 * @return string
	 */
	public function getGroupName($name = null) {
		$name = parent::getGroupName($name);
		$this->groups[] = $name;
		return $name;
	}

	protected function setUp(): void {
		parent::setUp();
		$this->backend = new \OC\Group\Database();
	}

	protected function tearDown(): void {
		foreach ($this->groups as $group) {
			$this->backend->deleteGroup($group);
		}
		parent::tearDown();
	}

	public function testAddDoubleNoCache() {
		$group = $this->getGroupName();

		$this->backend->createGroup($group);

		$backend = new \OC\Group\Database();
		$this->assertFalse($backend->createGroup($group));
	}
}
