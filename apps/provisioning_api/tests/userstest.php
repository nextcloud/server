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

namespace OCA\Provisioning_API\Tests;

class UsersTest extends TestCase {
	protected function resetParams() {
		$_GET = null;
		$_POST = null;
	}

	// Test getting the list of users
	public function testGetUsers() {
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

	public function testAddUser() {
		$this->resetParams();
		$_POST['userid'] = $this->getUniqueID();
		$_POST['password'] = 'password';
		$result = \OCA\provisioning_API\Users::addUser(array());
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(\OC_User::userExists($_POST['userid']));
		$this->assertEquals($_POST['userid'], \OC_User::checkPassword($_POST['userid'], $_POST['password']));
		$this->users[] = $_POST['userid'];
	}

	public function testGetUserOnSelf() {
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$params['userid'] = $user;
		$result = \OCA\provisioning_API\Users::getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
	}

	public function testGetUserOnNonExistingUser() {
		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		\OC_User::setUserId($user);
		$params = array();
		$params['userid'] = $this->getUniqueID();
		while(\OC_User::userExists($params['userid'])) {
			$params['userid'] = $this->getUniqueID();
		}
		$result = \OCA\provisioning_API\Users::getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OC_API::RESPOND_NOT_FOUND, $result->getStatusCode());

	}

	public function testGetUserOnOtherUser() {
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

	public function testEditOwnDisplayName() {

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

	public function testAdminEditDisplayNameOfUser() {

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

	public function testUserEditOtherUserDisplayName() {

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

	public function testEditOwnQuota() {
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

	public function testAdminEditOwnQuota() {
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

	public function testAdminEditOtherUserQuota() {
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

	public function testUserEditOtherUserQuota() {
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

	public function testUserEditOwnEmail() {
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

	public function testUserEditOtherUserEmailAsUser() {
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

	public function testUserEditOtherUserEmailAsAdmin() {
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

	public function testDeleteSelf() {
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	public function testDeleteOtherAsUser() {
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	public function testDeleteOtherAsSubAdmin() {
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$group = $this->getUniqueID();
		\OC_Group::createGroup($group);
		\OC_Group::addToGroup($user, $group);
		\OC_Group::addToGroup($user2, $group);
		\OC_SubAdmin::createSubAdmin($user, $group);
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		\OC_Group::deleteGroup($group);
	}

	public function testDeleteOtherAsIrelevantSubAdmin() {
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$group = $this->getUniqueID();
		$group2 = $this->getUniqueID();
		\OC_Group::createGroup($group);
		\OC_Group::createGroup($group2);
		\OC_Group::addToGroup($user, $group);
		\OC_Group::addToGroup($user2, $group2);
		\OC_SubAdmin::createSubAdmin($user, $group);
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		\OC_Group::deleteGroup($group);
		\OC_Group::deleteGroup($group2);
	}

	public function testDeleteOtherAsAdmin() {
		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		\OC_User::setUserId($user);
		$user2 = $this->generateUsers();
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
	}

	public function testDeleteSelfAsAdmin() {
		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		\OC_User::setUserId($user);
		$result = \OCA\provisioning_API\Users::deleteUser(array(
			'userid' => $user,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	public function testGetUsersGroupsOnSelf() {
		$user = $this->generateUsers();
		\OC_User::setUserId($user);
		$group = $this->getUniqueID();
		\OC_Group::createGroup($group);
		\OC_Group::addToGroup($user, $group);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group, reset($data['groups']));
		$this->assertEquals(1, count($data['groups']));
		\OC_Group::deleteGroup($group);
	}

	public function testGetUsersGroupOnOther() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$group = $this->getUniqueID();
		\OC_Group::createGroup($group);
		\OC_Group::addToGroup($user2, $group);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		\OC_Group::deleteGroup($group);
	}

	public function testGetUsersGroupOnOtherAsAdmin() {
		$user1 = $this->generateUsers();
		\OC_Group::addToGroup($user1, 'admin');
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$group = $this->getUniqueID();
		\OC_Group::createGroup($group);
		\OC_Group::addToGroup($user2, $group);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group, reset($data['groups']));
		$this->assertEquals(1, count($data['groups']));
		\OC_Group::deleteGroup($group);
	}

	public function testGetUsersGroupsOnOtherAsSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$group1 = $this->getUniqueID();
		$group2 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_Group::createGroup($group2);
		\OC_Group::addToGroup($user2, $group1);
		\OC_Group::addToGroup($user2, $group2);
		\OC_Group::addToGroup($user1, $group1);
		\OC_SubAdmin::createSubAdmin($user1, $group1);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group1, reset($data['groups']));
		$this->assertEquals(1, count($data['groups']));
		\OC_Group::deleteGroup($group1);
		\OC_Group::deleteGroup($group2);
	}

	public function testGetUsersGroupsOnOtherAsIrelevantSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$group1 = $this->getUniqueID();
		$group2 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_Group::createGroup($group2);
		\OC_Group::addToGroup($user2, $group2);
		\OC_Group::addToGroup($user1, $group1);
		\OC_SubAdmin::createSubAdmin($user1, $group1);
		$result = \OCA\provisioning_API\Users::getUsersGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		\OC_Group::deleteGroup($group1);
		\OC_Group::deleteGroup($group2);
	}

	public function testAddToGroup() {
		$user = $this->generateUsers();
		$group = $this->getUniqueID();
		\OC_Group::createGroup($group);
		\OC_User::setUserId($user);
		$_POST['groupid'] = $group;
		$result = \OCA\provisioning_API\Users::addToGroup(array(
			'userid' => $user,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertFalse(\OC_Group::inGroup($user, $group));
		\OC_Group::deleteGroup($group);
	}

	public function testAddToGroupAsAdmin() {
		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		$group = $this->getUniqueID();
		\OC_Group::createGroup($group);
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user);
		$_POST['groupid'] = $group;
		$result = \OCA\provisioning_API\Users::addToGroup(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(\OC_Group::inGroup($user2, $group));
		\OC_Group::deleteGroup($group);
	}

	public function testAddToGroupAsSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$group1 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_SubAdmin::createSubAdmin($user1, $group1);
		$_POST['groupid'] = $group1;
		$result = \OCA\provisioning_API\Users::addToGroup(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertFalse(\OC_Group::inGroup($user2, $group1));
		\OC_Group::deleteGroup($group1);
	}

	public function testAddToGroupAsIrelevantSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$group1 = $this->getUniqueID();
		$group2 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_Group::createGroup($group2);
		\OC_SubAdmin::createSubAdmin($user1, $group1);
		$_POST['groupid'] = $group2;
		$result = \OCA\provisioning_API\Users::addToGroup(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertFalse(\OC_Group::inGroup($user2, $group2));
		\OC_Group::deleteGroup($group1);
		\OC_Group::deleteGroup($group2);
	}

	// test delete /cloud/users/{userid}/groups
	public function testRemoveFromGroupAsSelf() {
		$user1 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$group1 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_Group::addToGroup($user1, $group1);
		$result = \OCA\provisioning_api\Users::removeFromGroup(array(
			'userid' => $user1,
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertTrue(\OC_Group::inGroup($user1, $group1));
		\OC_Group::deleteGroup($group1);
	}

	public function testRemoveFromGroupAsAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$group1 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_Group::addToGroup($user2, $group1);
		\OC_Group::addToGroup($user1, 'admin');
		$result = \OCA\provisioning_api\Users::removeFromGroup(array(
			'userid' => $user2,
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertFalse(\OC_Group::inGroup($user2, $group1));
		\OC_Group::deleteGroup($group1);
	}

	public function testRemoveFromGroupAsSubAdmin() {
		$user1 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$user2 = $this->generateUsers();
		$group1 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_Group::addToGroup($user1, $group1);
		\OC_Group::addToGroup($user2, $group1);
		\OC_SubAdmin::createSubAdmin($user1, $group1);
		$result = \OCA\provisioning_api\Users::removeFromGroup(array(
			'userid' => $user2,
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertFalse(\OC_Group::inGroup($user2, $group1));
		\OC_Group::deleteGroup($group1);
	}

	public function testRemoveFromGroupAsIrelevantSubAdmin() {
		$user1 = $this->generateUsers();
		\OC_User::setUserId($user1);
		$user2 = $this->generateUsers();
		$group1 = $this->getUniqueID();
		$group2 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_Group::createGroup($group2);
		\OC_Group::addToGroup($user1, $group1);
		\OC_Group::addToGroup($user2, $group2);
		\OC_SubAdmin::createSubAdmin($user1, $group1);
		$result = \OCA\provisioning_api\Users::removeFromGroup(array(
			'userid' => $user2,
			'_delete' => array(
				'groupid' => $group2,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertTrue(\OC_Group::inGroup($user2, $group2));
		\OC_Group::deleteGroup($group1);
		\OC_Group::deleteGroup($group2);
	}

	public function testCreateSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		\OC_Group::addToGroup($user1, 'admin');
		$group1 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		$_POST['groupid'] = $group1;
		$result = \OCA\provisioning_api\Users::addSubAdmin(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(\OC_SubAdmin::isSubAdminofGroup($user2, $group1));
		\OC_Group::deleteGroup($group1);

		$this->resetParams();

		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		\OC_Group::addToGroup($user1, 'admin');
		$_POST['groupid'] = 'admin';
		$result = \OCA\provisioning_api\Users::addSubAdmin(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertEquals(103, $result->getStatusCode());
		$this->assertFalse($result->succeeded());

		$this->resetParams();

		$user1 = $this->generateUsers();
		\OC_User::setUserId($user1);
		\OC_Group::addToGroup($user1, 'admin');
		$group1 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		$_POST['groupid'] = $group1;
		$result = \OCA\provisioning_api\Users::addSubAdmin(array(
			'userid' => $this->getUniqueID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
		\OC_Group::deleteGroup($group1);
	}

	public function testRemoveSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		\OC_Group::addToGroup($user1, 'admin');
		$group1 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_SubAdmin::createSubAdmin($user2, $group1);
		$result = \OCA\provisioning_api\Users::removeSubAdmin(array(
			'userid' => $user2,
			'_delete' => array(
				'groupid' => $group1,
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(!\OC_SubAdmin::isSubAdminofGroup($user2, $group1));
		\OC_Group::deleteGroup($group1);

		$user1 = $this->generateUsers();
		\OC_User::setUserId($user1);
		\OC_Group::addToGroup($user1, 'admin');
		$result = \OCA\provisioning_api\Users::removeSubAdmin(array(
			'userid' => $this->getUniqueID(),
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
		\OC_User::setUserId($user1);
		\OC_Group::addToGroup($user1, 'admin');
		$group1 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
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
		\OC_Group::deleteGroup($group1);
	}

	public function testGetSubAdminGroups() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		\OC_User::setUserId($user1);
		\OC_Group::addToGroup($user1, 'admin');
		$group1 = $this->getUniqueID();
		\OC_Group::createGroup($group1);
		\OC_SubAdmin::createSubAdmin($user2, $group1);
		$result = \OCA\provisioning_api\Users::getUserSubAdminGroups(array(
			'userid' => $user2,
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group1, reset($data));
		\OC_Group::deleteGroup($group1);

		$user1 = $this->generateUsers();
		\OC_User::setUserId($user1);
		\OC_Group::addToGroup($user1, 'admin');
		$group1 = $this->getUniqueID();
		$result = \OCA\provisioning_api\Users::getUserSubAdminGroups(array(
			'userid' => $this->getUniqueID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}
}
