<?php

/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Tom <tom@owncloud.com>
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

class Test_Provisioning_Api_Users extends PHPUnit_Framework_TestCase {

	private $users = array();

	/**
	 * Generates a temp user
	 * @param $num int number of users to generate
	 */
	function generateUsers($num=1) {
		$users = array();
		for($i=0; $i<$num; $i++) {
			$user = uniqid();
			\OC_User::createUser($user, 'password');
			$this->users[] = $user;
			$users[] = $user;
		}
		return count($users) == 1 ? reset($users) : $users;
	}

	function resetParams() {
		$_GET = null;
		$_POST = null;
	}

	// Test getting the list of users
	function testGetUsers() {
		$result = \OCA\provisioning_API\Users::getUsers(array());
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$count = $result->getData();
		$count = count($count['users']);
		$this->assertEquals(count(\OC_User::getUsers()), $count);

		$user = $this->generateUsers();
		$_GET['search'] = $user;
		$result = \OCA\provisioning_API\Users::getUsers(array());
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($user, reset($data['users']));

		// Add several users
		$this->generateUsers(10);
		$this->resetParams();
		$_GET['limit'] = 2;
		$result = \OCA\provisioning_API\Users::getUsers(array());
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$count = $result->getData();
		$count = count($count['users']);
		$this->assertEquals(2, $count);

		$this->resetParams();
		$_GET['limit'] = 1;
		$_GET['offset'] = 1;
		$result = \OCA\provisioning_API\Users::getUsers(array());
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals(\OC_User::getUsers('', 1, 1), $data['users']);
	}

	function testAddUser() {
		$this->resetParams();
		$_POST['userid'] = uniqid();
		$_POST['password'] = 'password';
		$result = \OCA\provisioning_API\Users::addUser(array());
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(\OC_User::userExists($_POST['userid']));
		$this->assertEquals($_POST['userid'], \OC_User::checkPassword($_POST['userid'], $_POST['password']));
		$this->users[] = $_POST['userid'];
	}

	function testGetUserOnSelf() {
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$params['userid'] = $user;
		$result = \OCA\provisioning_API\Users::getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
	}

	function testGetUserOnNonExistingUser() {
		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		\OC_User::setUserId($user);
		$params = array();
		$params['userid'] = uniqid();
		while(\OC_User::userExists($params['userid'])) {
			$params['userid'] = uniqid();
		}
		$result = \OCA\provisioning_API\Users::getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OC_API::RESPOND_NOT_FOUND, $result->getStatusCode());

	}

	function testGetUserOnOtherUser() {
		$users = $this->generateUsers(2);
		$params['userid'] = $users[0];
		\OC_User::setUserId($users[1]);
		$result = \OCA\provisioning_API\Users::getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());

		// Now as as admin
		$users = $this->generateUsers(2);
		$params['userid'] = $users[0];
		\OC_Group::addToGroup($users[1], 'admin');
		\OC_User::setUserId($users[1]);
		$result = \OCA\provisioning_API\Users::getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals(\OC::$server->getConfig()->getUserValue($users[0], 'core', 'enabled', 'true'), $data['enabled']);
	}

	function testEditOwnDisplayName() {

		// Test editing own name
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $user,
				'_put' => array(
					'key' => 'display',
					'value' => 'newname',
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals('newname', \OC_User::getDisplayName($user));

	}

	function testAdminEditDisplayNameOfUser() {

		// Test admin editing users name
		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		\OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $user2,
				'_put' => array(
					'key' => 'display',
					'value' => 'newname',
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals('newname', \OC_User::getDisplayName($user2));

	}

	function testUserEditOtherUserDisplayName() {

		// Test editing other users name
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $user2,
				'_put' => array(
					'key' => 'display',
					'value' => 'newname',
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());

	}

	function testEditOwnQuota() {
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $user,
				'_put' => array(
					'key' => 'quota',
					'value' => '20G',
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	function testAdminEditOwnQuota() {
		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		\OC_User::setUserId($user);
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $user,
				'_put' => array(
					'key' => 'quota',
					'value' => '20G',
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
	}

	function testAdminEditOtherUserQuota() {
		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		\OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $user2,
				'_put' => array(
					'key' => 'quota',
					'value' => '20G',
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
	}

	function testUserEditOtherUserQuota() {
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $user2,
				'_put' => array(
					'key' => 'quota',
					'value' => '20G',
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	function testUserEditOwnEmail() {
		$user = $this->generateUsers();
		$email = 'test@example.com';
		\OC_User::setUserId($user);
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $user,
				'_put' => array(
					'key' => 'email',
					'value' => $email,
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals($email, \OC::$server->getConfig()->getUserValue($user, 'settings', 'email', null));
	}

	function testUserEditOtherUserEmailAsUser() {
		$users = $this->generateUsers(2);
		$email = 'test@example.com';
		\OC_User::setUserId($users[0]);
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $users[1],
				'_put' => array(
					'key' => 'email',
					'value' => $email,
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	function testUserEditOtherUserEmailAsAdmin() {
		$users = $this->generateUsers(2);
		$email = 'test@example.com';
		\OC_User::setUserId($users[0]);
		\OC_Group::addToGroup($users[0], 'admin');
		$result = \OCA\provisioning_API\Users::editUser(
			array(
				'userid' => $users[1],
				'_put' => array(
					'key' => 'email',
					'value' => $email,
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals($email, \OC::$server->getConfig()->getUserValue($users[1], 'settings', 'email', null));
	}

	function testDeleteSelf() {
		$user = $this->generateUsers();
		OC_User::setUserId($user);
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	function testDeleteOtherAsUser() {
		$user = $this->generateUsers();
		OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	function testDeleteOtherAsSubAdmin() {
		$user = $this->generateUsers();
		OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$group = uniqid();
		OC_Group::createGroup($group);
		OC_Group::addToGroup($user, $group);
		OC_Group::addToGroup($user2, $group);
		OC_SubAdmin::createSubAdmin($user, $group);
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		OC_Group::deleteGroup($group);
	}

	function testDeleteOtherAsIrelevantSubAdmin() {
		$user = $this->generateUsers();
		OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$group = uniqid();
		$group2 = uniqid();
		OC_Group::createGroup($group);
		OC_Group::createGroup($group2);
		OC_Group::addToGroup($user, $group);
		OC_Group::addToGroup($user2, $group2);
		OC_SubAdmin::createSubAdmin($user, $group);
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		OC_Group::deleteGroup($group);
		OC_Group::deleteGroup($group2);
	}

	function testDeleteOtherAsAdmin() {
		$user = $this->generateUsers();
		OC_Group::addToGroup($user, 'admin');
		OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
	}

	function testDeleteSelfAsAdmin() {
		$user = $this->generateUsers();
		OC_Group::addToGroup($user, 'admin');
		OC_User::setUserId($user);
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	function testGetUsersGroupsOnSelf() {
		$user = $this->generateUsers();
		OC_User::setUserId($user);
		$group = uniqid();
		OC_Group::createGroup($group);
		OC_Group::addToGroup($user, $group);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group, reset($data['groups']));
		$this->assertEquals(1, count($data['groups']));
		OC_Group::deleteGroup($group);
	}

	function testGetUsersGroupOnOther() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		$group = uniqid();
		OC_Group::createGroup($group);
		OC_Group::addToGroup($user2, $group);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$data = $result->getData();
		OC_Group::deleteGroup($group);
	}

	function testGetUsersGroupOnOtherAsAdmin() {
		$user1 = $this->generateUsers();
		OC_Group::addToGroup($user1, 'admin');
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		$group = uniqid();
		OC_Group::createGroup($group);
		OC_Group::addToGroup($user2, $group);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group, reset($data['groups']));
		$this->assertEquals(1, count($data['groups']));
		OC_Group::deleteGroup($group);
	}

	function testGetUsersGroupsOnOtherAsSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		$group1 = uniqid();
		$group2 = uniqid();
		OC_Group::createGroup($group1);
		OC_Group::createGroup($group2);
		OC_Group::addToGroup($user2, $group1);
		OC_Group::addToGroup($user2, $group2);
		OC_Group::addToGroup($user1, $group1);
		OC_SubAdmin::createSubAdmin($user1, $group1);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group1, reset($data['groups']));
		$this->assertEquals(1, count($data['groups']));
		OC_Group::deleteGroup($group1);
		OC_Group::deleteGroup($group2);
	}

	function testGetUsersGroupsOnOtherAsIrelevantSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		$group1 = uniqid();
		$group2 = uniqid();
		OC_Group::createGroup($group1);
		OC_Group::createGroup($group2);
		OC_Group::addToGroup($user2, $group2);
		OC_Group::addToGroup($user1, $group1);
		OC_SubAdmin::createSubAdmin($user1, $group1);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		OC_Group::deleteGroup($group1);
		OC_Group::deleteGroup($group2);
	}

	function testAddToGroup() {
		$user = $this->generateUsers();
		$group = uniqid();
		OC_Group::createGroup($group);
		OC_User::setUserId($user);
		$_POST['groupid'] = $group;
		$result = \OCA\provisioning_API\Users::addToGroup(array(
			'userid' => $user,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertFalse(OC_Group::inGroup($user, $group));
		OC_Group::deleteGroup($group);
	}

	function testAddToGroupAsAdmin() {
		$user = $this->generateUsers();
		OC_Group::addToGroup($user, 'admin');
		$group = uniqid();
		OC_Group::createGroup($group);
		$user2 = $this->generateUsers();
		OC_User::setUserId($user);
		$_POST['groupid'] = $group;
		$result = \OCA\provisioning_API\Users::addToGroup(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(OC_Group::inGroup($user2, $group));
		OC_Group::deleteGroup($group);
	}

	function testAddToGroupAsSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		OC_SubAdmin::createSubAdmin($user1, $group1);
		$_POST['groupid'] = $group1;
		$result = \OCA\provisioning_API\Users::addToGroup(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertFalse(OC_Group::inGroup($user2, $group1));
		OC_Group::deleteGroup($group1);
	}

	function testAddToGroupAsIrelevantSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		$group1 = uniqid();
		$group2 = uniqid();
		OC_Group::createGroup($group1);
		OC_Group::createGroup($group2);
		OC_SubAdmin::createSubAdmin($user1, $group1);
		$_POST['groupid'] = $group2;
		$result = \OCA\provisioning_API\Users::addToGroup(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertFalse(OC_Group::inGroup($user2, $group2));
		OC_Group::deleteGroup($group1);
		OC_Group::deleteGroup($group2);
	}

	// test delete /cloud/users/{userid}/groups
	function testRemoveFromGroupAsSelf() {
		$user1 = $this->generateUsers();
		OC_User::setUserId($user1);
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		OC_Group::addToGroup($user1, $group1);
		$result = \OCA\provisioning_api\Users::removeFromGroup(array(
			'userid' => $user1,
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertTrue(OC_Group::inGroup($user1, $group1));
		OC_Group::deleteGroup($group1);
	}

	function testRemoveFromGroupAsAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		OC_Group::addToGroup($user2, $group1);
		OC_Group::addToGroup($user1, 'admin');
		$result = \OCA\provisioning_api\Users::removeFromGroup(array(
			'userid' => $user2,
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertFalse(OC_Group::inGroup($user2, $group1));
		OC_Group::deleteGroup($group1);
	}

	function testRemoveFromGroupAsSubAdmin() {
		$user1 = $this->generateUsers();
		OC_User::setUserId($user1);
		$user2 = $this->generateUsers();
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		OC_Group::addToGroup($user1, $group1);
		OC_Group::addToGroup($user2, $group1);
		OC_SubAdmin::createSubAdmin($user1, $group1);
		$result = \OCA\provisioning_api\Users::removeFromGroup(array(
			'userid' => $user2,
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertFalse(OC_Group::inGroup($user2, $group1));
		OC_Group::deleteGroup($group1);
	}

	function testRemoveFromGroupAsIrelevantSubAdmin() {
		$user1 = $this->generateUsers();
		OC_User::setUserId($user1);
		$user2 = $this->generateUsers();
		$group1 = uniqid();
		$group2 = uniqid();
		OC_Group::createGroup($group1);
		OC_Group::createGroup($group2);
		OC_Group::addToGroup($user1, $group1);
		OC_Group::addToGroup($user2, $group2);
		OC_SubAdmin::createSubAdmin($user1, $group1);
		$result = \OCA\provisioning_api\Users::removeFromGroup(array(
			'userid' => $user2,
			'_delete' => array(
				'groupid' => $group2,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertTrue(OC_Group::inGroup($user2, $group2));
		OC_Group::deleteGroup($group1);
		OC_Group::deleteGroup($group2);
	}

	function testCreateSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		$_POST['groupid'] = $group1;
		$result = \OCA\provisioning_api\Users::addSubAdmin(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(OC_SubAdmin::isSubAdminofGroup($user2, $group1));
		OC_Group::deleteGroup($group1);

		$this->resetParams();

		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$_POST['groupid'] = 'admin';
		$result = \OCA\provisioning_api\Users::addSubAdmin(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertEquals(103, $result->getStatusCode());
		$this->assertFalse($result->succeeded());

		$this->resetParams();

		$user1 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		$_POST['groupid'] = $group1;
		$result = \OCA\provisioning_api\Users::addSubAdmin(array(
			'userid' => uniqid(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
		OC_Group::deleteGroup($group1);
	}

	function testRemoveSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		OC_SubAdmin::createSubAdmin($user2, $group1);
		$result = \OCA\provisioning_api\Users::removeSubAdmin(array(
			'userid' => $user2,
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(!OC_SubAdmin::isSubAdminofGroup($user2, $group1));
		OC_Group::deleteGroup($group1);

		$user1 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$result = \OCA\provisioning_api\Users::removeSubAdmin(array(
			'userid' => uniqid(),
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertEquals(101, $result->getStatusCode());
		$this->assertFalse($result->succeeded());

		$this->resetParams();

		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		$_POST['groupid'] = $group1;
		$result = \OCA\provisioning_api\Users::removeSubAdmin(array(
			'userid' => $user2,
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
		OC_Group::deleteGroup($group1);
	}

	function testGetSubAdminGroups() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$group1 = uniqid();
		OC_Group::createGroup($group1);
		OC_SubAdmin::createSubAdmin($user2, $group1);
		$result = \OCA\provisioning_api\Users::getUserSubAdminGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group1, reset($data));
		OC_Group::deleteGroup($group1);

		$user1 = $this->generateUsers();
		OC_User::setUserId($user1);
		OC_Group::addToGroup($user1, 'admin');
		$group1 = uniqid();
		$result = \OCA\provisioning_api\Users::getUserSubAdminGroups(array(
			'userid' => uniqid(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}

	// Remove all the temporary users
	function tearDown() {
		foreach($this->users as $user) {
			\OC_User::deleteUser($user);
		}
	}

}
