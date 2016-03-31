<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Class Test_Group_Database
 *
 * @group DB
 */
class Test_Group_Database extends Test_Group_Backend {
	private $groups = array();

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

	protected function setUp() {
		parent::setUp();
		$this->backend = new OC_Group_Database();
	}

	protected function tearDown() {
		foreach ($this->groups as $group) {
			$this->backend->deleteGroup($group);
		}
		parent::tearDown();
	}

	public function testAddDoubleNoCache() {
		$group = $this->getGroupName();

		$this->backend->createGroup($group);

		$backend = new OC_Group_Database();
		$this->assertFalse($backend->createGroup($group));
	}
}
