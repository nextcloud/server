<?php

/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Tom <tom@owncloud.com>
 * @author Thomas Müller <deepdiver@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Provisioning_API\Tests;

abstract class TestCase extends \Test\TestCase {
	protected $users = array();

	protected function setUp() {
		parent::setUp();
		\OC_Group::createGroup('admin');
	}

	/**
	 * Generates a temp user
	 * @param int $num number of users to generate
	 * @return array
	 */
	protected function generateUsers($num = 1) {
		$users = array();
		for ($i = 0; $i < $num; $i++) {
			$user = $this->getUniqueID();
			\OC_User::createUser($user, 'password');
			$this->users[] = $user;
			$users[] = $user;
		}
		return count($users) == 1 ? reset($users) : $users;
	}

	protected function tearDown() {
		foreach($this->users as $user) {
			\OC_User::deleteUser($user);
		}

		\OC_Group::deleteGroup('admin');

		parent::tearDown();
	}
}
