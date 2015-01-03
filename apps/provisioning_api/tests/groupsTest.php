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

class Test_Provisioning_Api_Groups extends PHPUnit_Framework_TestCase {

	private $users = array();

	/**
	 * Generates a temp user
	 * @param $num int number of users to generate
	 */
	function generateUsers($num=1) {
		for($i=0; $i<$num; $i++) {
			$user = uniqid();
			\OC_User::createUser($user, 'password');
			$this->users[] = $user;
			$users[] = $user;
		}
		return count($users) == 1 ? reset($users) : $users;
	}

	function testGetGroupAsUser() {

		$users = $this->generateUsers(2);
		\OC_User::setUserId($users[0]);

		$group = uniqid();
		\OC_Group::createGroup($group);
		\OC_Group::addToGroup($users[1], $group);

		$result = \OCA\provisioning_api\Groups::getGroup(array(
			'groupid' => $group,
		));

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OC_API::RESPOND_UNAUTHORISED, $result->getStatusCode());

	}

	function testGetGroupAsSubadmin() {

		$users = $this->generateUsers(2);
		\OC_User::setUserId($users[0]);

		$group = uniqid();
		\OC_Group::createGroup($group);
		\OC_Group::addToGroup($users[0], $group);
		\OC_Group::addToGroup($users[1], $group);

		\OC_SubAdmin::createSubAdmin($users[0], $group);

		$result = \OCA\provisioning_api\Groups::getGroup(array(
			'groupid' => $group,
		));

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals(array('users' => $users), $result->getData());

	}

	function testGetGroupAsIrrelevantSubadmin() {

		$users = $this->generateUsers(2);
		\OC_User::setUserId($users[0]);

		$group = uniqid();
		\OC_Group::createGroup($group);
		$group2 = uniqid();
		\OC_Group::createGroup($group2);
		\OC_Group::addToGroup($users[1], $group);
		\OC_Group::addToGroup($users[0], $group2);

		\OC_SubAdmin::createSubAdmin($users[0], $group2);

		$result = \OCA\provisioning_api\Groups::getGroup(array(
			'groupid' => $group,
		));

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OC_API::RESPOND_UNAUTHORISED, $result->getStatusCode());

	}

	function testGetGroupAsAdmin() {

		$users = $this->generateUsers(2);
		\OC_User::setUserId($users[0]);

		$group = uniqid();
		\OC_Group::createGroup($group);

		\OC_Group::addToGroup($users[1], $group);
		\OC_Group::addToGroup($users[0], 'admin');

		$result = \OCA\provisioning_api\Groups::getGroup(array(
			'groupid' => $group,
		));

		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals(array('users' => array($users[1])), $result->getData());

	}

	function testGetSubAdminsOfGroup() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		OC_SubAdmin::createSubAdmin($user2, $group1);
		$result = \OCA\provisioning_api\Groups::getSubAdminsOfGroup(array(
			'groupid' => $group1,
		));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($user2, reset($data));
		OC_Group::deleteGroup($group1);

		$user1 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$result = \OCA\provisioning_api\Groups::getSubAdminsOfGroup(array(
			'groupid' => uniqid(),
		));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}

	/**
	 * Remove all the temporary users
	 */
	function tearDown() {
		foreach($this->users as $user) {
			\OC_User::deleteUser($user);
		}
	}


}
