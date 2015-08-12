<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Provisioning_API\Tests;

use OCP\IUserManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;

class UsersTest extends TestCase {
	
	/** @var IUserManager */
	protected $userManager;

	/** @var IConfig */
	protected $config;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserSession */
	protected $userSession;

	protected function resetParams() {
		$_GET = null;
		$_POST = null;
	}

	protected function setup() {
		parent::setup();

		$this->userManager = \OC::$server->getUserManager();
		$this->config = \OC::$server->getConfig();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->userSession = \OC::$server->getUserSession();
		$this->api = new \OCA\Provisioning_Api\Users(
			$this->userManager, 
			$this->config, 
			$this->groupManager,
			$this->userSession
		);
	}

	// Test getting the list of users
	public function testGetUsers() {
		$result = $this->api->getUsers();
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$count = $result->getData();
		$count = count($count['users']);
		$this->assertEquals(count($this->userManager->search('', null, null)), $count);

		$user = $this->generateUsers();
		$_GET['search'] = $user->getUID();
		$result = $this->api->getUsers();
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($user->getUID(), reset($data['users']));

		// Add several users
		$this->generateUsers(10);
		$this->resetParams();
		$_GET['limit'] = 2;
		$result = $this->api->getUsers();
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$count = $result->getData();
		$count = count($count['users']);
		$this->assertEquals(2, $count);

		$this->resetParams();
		$_GET['limit'] = 1;
		$_GET['offset'] = 1;
		$result = $this->api->getUsers(array());
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals(array_keys($this->userManager->search('', 1, 1)), $data['users']);
	}

	public function testAddUser() {
		$this->resetParams();
		$_POST['userid'] = $this->getUniqueID();
		$_POST['password'] = 'password';
		$result = $this->api->addUser();
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue($this->userManager->userExists($_POST['userid']));
		$this->assertEquals($_POST['userid'], $this->userManager->checkPassword($_POST['userid'], $_POST['password'])->getUID());
		$this->users[] = $this->userManager->get($_POST['userid']);
	}

	public function testAddUserTwice() {
		$this->resetParams();
		$_POST['userid'] = $this->getUniqueID();
		$_POST['password'] = 'password';
		$this->api->addUser();
		$result = $this->api->addUser();
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
		$this->assertEquals('User already exists', $result->getMeta()['message']);
	}

	public function testAddUserFails() {
		$uid = $this->getUniqueID();

		$userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();

		$userManager->expects($this->once())
			->method('userExists')
			->with($uid)
			->willReturn(false);
		$userManager->expects($this->once())
			->method('createUser')
			->with($uid, 'password')
			->will($this->throwException(new \Exception));

		$api = new \OCA\Provisioning_Api\Users(
			$userManager, 
			$this->config, 
			$this->groupManager,
			$this->userSession
		);

		$this->resetParams();
		$_POST['userid'] = $uid;
		$_POST['password'] = 'password';
		$result = $api->addUser();
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
		$this->assertEquals('Bad request', $result->getMeta()['message']);
	}

	public function testGetUserOnSelf() {
		$user = $this->generateUsers();
		$user->setDisplayName('foobar');
		$this->userSession->setUser($user);
		$params['userid'] = $user->getUID();
		$result = $this->api->getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		
		$this->assertEquals('foobar', $data['displayname']);
	}

	public function testGetUserOnNonExistingUser() {
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);
		$params = array();
		$params['userid'] = $this->getUniqueID();
		while($this->userManager->userExists($params['userid'])) {
			$params['userid'] = $this->getUniqueID();
		}
		$result = $this->api->getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OCP\API::RESPOND_NOT_FOUND, $result->getStatusCode());

	}

	public function testGetUserOnOtherUser() {
		$users = $this->generateUsers(2);
		$params['userid'] = $users[0];
		$this->userSession->setUser($users[1]);
		$result = $this->api->getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());

		// Now as as admin
		$users = $this->generateUsers(2);
		$params['userid'] = $users[0]->getUID();
		// login to generate home
		$this->userSession->setUser($users[0]);
		$this->groupManager->get('admin')->addUser($users[1]);
		$this->userSession->setUser($users[1]);
		$result = $this->api->getUser($params);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals(\OC::$server->getConfig()->getUserValue($users[0]->getUID(), 'core', 'enabled', 'true'), $data['enabled']);
	}

	public function testEditOwnDisplayName() {
		// Test editing own name
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$result = $this->api->editUser(
			array(
				'userid' => $user->getUID(),
				'_put' => array(
					'key' => 'display',
					'value' => 'newname',
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals('newname', $user->getDisplayName());

	}

	public function testAdminEditDisplayNameOfUser() {
		// Test admin editing users name
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);
		$user2 = $this->generateUsers();
		$result = $this->api->editUser(
			[
				'userid' => $user2->getUID(),
				'_put' => [
					'key' => 'display',
					'value' => 'newname',
				],
			]
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals('newname', $user2->getDisplayName());

	}

	public function testUserEditOtherUserDisplayName() {
		// Test editing other users name
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$user2 = $this->generateUsers();
		$result = $this->api->editUser(
			array(
				'userid' => $user2->getUID(),
				'_put' => array(
					'key' => 'display',
					'value' => 'newname',
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());

	}

	/**
	 * @dataProvider providesQuotas
	 * @param $expected
	 * @param $quota
	 */
	public function testEditOwnQuota($expected, $quota) {
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$result = $this->api->editUser(
			[
				'userid' => $user->getUID(),
				'_put' => [
					'key' => 'quota',
					'value' => $quota,
				],
			]
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(997, $result->getStatusCode());
	}

	/**
	 * @dataProvider providesQuotas
	 * @param $expected
	 * @param $quota
	 */
	public function testEditOwnQuotaAsAdmin($expected, $quota) {
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);
		$result = $this->api->editUser(
			[
				'userid' => $user->getUID(),
				'_put' => [
					'key' => 'quota',
					'value' => $quota,
				],
			]
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertEquals($expected, $result->succeeded());
	}

	public function providesQuotas() {
		return [
			[true, '20G'],
			[true, '1234567'],
			[true, 'none'],
			[true, 'default'],
			[false, 'qwertzu'],
			[true, 0],
			[true, -1]
		];
	}

	public function testAdminEditOwnQuota() {
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);
		$result = $this->api->editUser(
			array(
				'userid' => $user->getUID(),
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
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);
		$user2 = $this->generateUsers();
		$result = $this->api->editUser(
			array(
				'userid' => $user2->getUID(),
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
		$this->userSession->setUser($user);
		$user2 = $this->generateUsers();
		$result = $this->api->editUser(
			array(
				'userid' => $user2->getUID(),
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
		$this->userSession->setUser($user);
		$result = $this->api->editUser(
			array(
				'userid' => $user->getUID(),
				'_put' => array(
					'key' => 'email',
					'value' => $email,
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals($email, \OC::$server->getConfig()->getUserValue($user->getUID(), 'settings', 'email', null));
	}

	public function testUserEditOwnEmailInvalid() {
		$user = $this->generateUsers();
		$email = 'test@example';
		$this->userSession->setUser($user);
		$result = $this->api->editUser([
			'userid' => $user->getUID(),
			'_put' => [
				'key' => 'email',
				'value' => $email,
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
	}

	public function testUserEditOtherUserEmailAsUser() {
		$users = $this->generateUsers(2);
		$email = 'test@example.com';
		$this->userSession->setUser($users[0]);
		$result = $this->api->editUser(
			array(
				'userid' => $users[1]->getUID(),
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
		$this->userSession->setUser($users[0]);
		$this->groupManager->get('admin')->addUser($users[0]);
		$result = $this->api->editUser(
			array(
				'userid' => $users[1]->getUID(),
				'_put' => array(
					'key' => 'email',
					'value' => $email,
					),
				)
			);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals($email, \OC::$server->getConfig()->getUserValue($users[1]->getUID(), 'settings', 'email', null));
	}

	public function testUserEditOwnPassword() {
		$user = $this->generateUsers();
		$password = 'foo';
		$this->userSession->setUser($user);
		$result = $this->api->editUser([
			'userid' => $user->getUID(),
			'_put' => [
				'key' => 'password',
				'value' => $password,
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
	}

	public function testUserEditOtherUserPasswordAsUser() {
		$users = $this->generateUsers(2);
		$password = 'foo';
		$this->userSession->setUser($users[0]);
		$result = $this->api->editUser([
			'userid' => $users[1]->getUID(),
			'_put' => [
				'key' => 'password',
				'value' => $password,
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	public function testUserEditOtherUserPasswordAsAdmin() {
		$users = $this->generateUsers(2);
		$password = 'foo';
		$this->userSession->setUser($users[0]);
		$this->groupManager->get('admin')->addUser($users[0]);
		$result = $this->api->editUser([
			'userid' => $users[1]->getUID(),
			'_put' => [
				'key' => 'password',
				'value' => $password,
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
	}

	public function testDeleteSelf() {
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$result = $this->api->deleteUser(array(
			'userid' => $user->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	public function testDeleteOtherAsUser() {
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$user2 = $this->generateUsers();
		$result = $this->api->deleteUser(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	public function testDeleteOtherAsSubAdmin() {
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$user2 = $this->generateUsers();
		$group = $this->groupManager->createGroup($this->getUniqueID());
		$group->addUser($user);
		$group->addUser($user2);
		\OC_SubAdmin::createSubAdmin($user->getUID(), $group->getGID());
		$result = $this->api->deleteUser(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$group->delete();
	}

	public function testDeleteOtherAsIrelevantSubAdmin() {
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$user2 = $this->generateUsers();
		$group = $this->groupManager->createGroup($this->getUniqueID());
		$group2 = $this->groupManager->createGroup($this->getUniqueID());
		$group->addUser($user);
		$group2->addUser($user2);
		\OC_SubAdmin::createSubAdmin($user->getUID(), $group->getGID());
		$result = $this->api->deleteUser(array(
			'userid' => $user2->getUID(),
		));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$group->delete();
		$group2->delete();
	}

	public function testDeleteOtherAsAdmin() {
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);
		$user2 = $this->generateUsers();
		$result = $this->api->deleteUser(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
	}

	public function testDeleteSelfAsAdmin() {
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);
		$result = $this->api->deleteUser(array(
			'userid' => $user->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
	}

	public function testDeleteFails() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('delete')
			->willReturn(false);

		$user2 = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user2->expects($this->any())
			->method('getUID')
			->willReturn('user2');

		$userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$userManager->expects($this->once())
			->method('userExists')
			->with('user')
			->willReturn(true);
		$userManager->expects($this->once())
			->method('get')
			->with('user')
			->willReturn($user);

		$userSession = $this->getMockBuilder('\OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$userSession->expects($this->once())
			->method('getUser')
			->willReturn($user2);
	
		$groupManager = $this->getMockBuilder('\OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();
		$groupManager->expects($this->once())
			->method('isAdmin')
			->with('user2')
			->willReturn(true);

		$api = new \OCA\Provisioning_Api\Users(
			$userManager, 
			$this->config, 
			$groupManager,
			$userSession
		);

		$result = $api->deleteUser([
			'userid' => 'user',
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}

	public function testGetUsersGroupsOnSelf() {
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$group = $this->getUniqueID();
		$group = $this->groupManager->createGroup($group);
		$group->addUser($user);
		$result = $this->api->getUsersGroups(array(
			'userid' => $user->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group->getGID(), reset($data['groups']));
		$this->assertEquals(1, count($data['groups']));
		$group->delete();
	}

	public function testGetUsersGroupOnOther() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group = $this->getUniqueID();
		$group = $this->groupManager->createGroup($group);
		$group->addUser($user2);
		$result = $this->api->getUsersGroups(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$group->delete();
	}

	public function testGetUsersGroupOnOtherAsAdmin() {
		$user1 = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user1);
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group = $this->getUniqueID();
		$group = $this->groupManager->createGroup($group);
		$group->addUser($user2);
		$result = $this->api->getUsersGroups(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group->getGID(), reset($data['groups']));
		$this->assertEquals(1, count($data['groups']));
		$group->delete();
	}

	public function testGetUsersGroupsOnOtherAsSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group1 = $this->getUniqueID();
		$group2 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$group2 = $this->groupManager->createGroup($group2);
		$group1->addUser($user2);
		$group2->addUser($user2);
		$group1->addUser($user1);
		\OC_SubAdmin::createSubAdmin($user1->getUID(), $group1->getGID());
		$result = $this->api->getUsersGroups(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group1->getGID(), reset($data['groups']));
		$this->assertEquals(1, count($data['groups']));
		$group1->delete();
		$group2->delete();
	}

	public function testGetUsersGroupsOnOtherAsIrelevantSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group1 = $this->getUniqueID();
		$group2 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$group2 = $this->groupManager->createGroup($group2);
		$group2->addUser($user2);
		$group1->addUser($user1);
		\OC_SubAdmin::createSubAdmin($user1->getUID(), $group1->getGID());
		$result = $this->api->getUsersGroups(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$group1->delete();
		$group2->delete();
	}

	public function testAddToGroup() {
		$user = $this->generateUsers();
		$group = $this->getUniqueID();
		$group = $this->groupManager->createGroup($group);
		$this->userSession->setUser($user);
		$_POST['groupid'] = $group->getGID();
		$result = $this->api->addToGroup(array(
			'userid' => $user->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertFalse($group->inGroup($user));
		$group->delete();
	}

	public function testAddToGroupAsAdmin() {
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$group = $this->getUniqueID();
		$group = $this->groupManager->createGroup($group);
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user);
		$_POST['groupid'] = $group->getGID();
		$result = $this->api->addToGroup(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue($group->inGroup($user2));
		$group->delete();
	}

	public function testAddToGroupAsSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group1 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		\OC_SubAdmin::createSubAdmin($user1->getUID(), $group1->getGID());
		$_POST['groupid'] = $group1->getGID();
		$result = $this->api->addToGroup(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertFalse($group1->inGroup($user2));
		$group1->delete();
	}

	public function testAddToGroupAsIrelevantSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group1 = $this->getUniqueID();
		$group2 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$group2 = $this->groupManager->createGroup($group2);
		\OC_SubAdmin::createSubAdmin($user1->getUID(), $group1->getGID());
		$_POST['groupid'] = $group2->getGID();
		$result = $this->api->addToGroup(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertFalse($group2->inGroup($user2));
		$group1->delete();
		$group2->delete();
	}

	public function testAddToGroupNoGroupId() {
		$_POST['groupid'] = '';
		$result = $this->api->addToGroup([
			'userid' => $this->getUniqueID(),
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}

	public function testAddToNonExistingGroup() {
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);

		$group = $this->groupManager->createGroup($this->getUniqueID());
		$_POST['groupid'] = $group->getGID();
		$result = $this->api->addToGroup([
			'userid' => $this->getUniqueID(),
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(103, $result->getStatusCode());
	}

	public function testAddNonExistingUserToGroup() {
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);

		$_POST['groupid'] = $this->getUniqueID();
		$result = $this->api->addToGroup([
			'userid' => $this->getUniqueID(),
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
	}

	// test delete /cloud/users/{userid}/groups
	public function testRemoveFromGroupAsSelf() {
		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group1 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$group1->addUser($user1);
		$result = $this->api->removeFromGroup(array(
			'userid' => $user1->getUID(),
			'_delete' => array(
				'groupid' => $group1->getGID(),
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertTrue($group1->inGroup($user1));
		$group1->delete();
	}

	public function testRemoveFromGroupAsAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group1 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$group1->addUser($user2);
		$this->groupManager->get('admin')->addUser($user1);
		$result = $this->api->removeFromGroup(array(
			'userid' => $user2->getUID(),
			'_delete' => array(
				'groupid' => $group1->getGID(),
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertFalse($group1->inGroup($user2));
		$group1->delete();
	}

	public function testRemoveSelfFromGroupAsAdmin() {
		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group1 = $this->groupManager->createGroup($this->getUniqueID());
		$group1->addUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$result = $this->api->removeFromGroup([
			'userid' => $user1->getUID(),
			'_delete' => [
				'groupid' => $group1->getGID(),
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertFalse($group1->inGroup($user1));
		$group1->delete();
	}

	public function testRemoveFromGroupAsSubAdmin() {
		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$user2 = $this->generateUsers();
		$group1 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$group1->addUser($user1);
		$group1->addUser($user2);
		\OC_SubAdmin::createSubAdmin($user1->getUID(), $group1->getGID());
		$result = $this->api->removeFromGroup(array(
			'userid' => $user2->getUID(),
			'_delete' => array(
				'groupid' => $group1->getGID(),
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertFalse($group1->inGroup($user2));
		$group1->delete();
	}

	public function testRemoveFromGroupAsIrelevantSubAdmin() {
		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$user2 = $this->generateUsers();
		$group1 = $this->getUniqueID();
		$group2 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$group2 = $this->groupManager->createGroup($group2);
		$group1->addUser($user1);
		$group2->addUser($user2);
		\OC_SubAdmin::createSubAdmin($user1->getUID(), $group1->getGID());
		$result = $this->api->removeFromGroup(array(
			'userid' => $user2->getUID(),
			'_delete' => array(
				'groupid' => $group2->getGID(),
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertTrue($group2->inGroup($user2));
		$group1->delete();
		$group2->delete();
	}

	public function testRemoveFromGroupNoGroupId() {
		$result = $this->api->removeFromGroup([
			'_delete' => [
				'groupid' => ''
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}

	public function testRemoveSelfFromAdminAsAdmin() {
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$this->groupManager->get('admin')->addUser($user);

		$result = $this->api->removeFromGroup([
			'userid' => $user->getUID(),
			'_delete' => [
				'groupid' => 'admin'
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(105, $result->getStatusCode());
		$this->assertEquals('Cannot remove yourself from the admin group', $result->getMeta()['message']);
	}

	public function testRemoveSelfFromSubAdminGroupAsSubAdmin() {
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$group = $this->groupManager->createGroup($this->getUniqueID());
		\OC_SubAdmin::createSubAdmin($user->getUID(), $group->getGID());

		$result = $this->api->removeFromGroup([
			'userid' => $user->getUID(),
			'_delete' => [
				'groupid' => $group->getGID()
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(105, $result->getStatusCode());
		$this->assertEquals('Cannot remove yourself from this group as you are a SubAdmin', $result->getMeta()['message']);
		$group->delete();
	}

	public function testRemoveFromNonExistingGroup() {
		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);

		$user2 = $this->generateUsers();
		$result = $this->api->removeFromGroup([
			'userid' => $user2->getUID(),
			'_delete' => [
				'groupid' => $this->getUniqueID()
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
	}

	public function testRemoveFromNonGroupNonExistingUser() {
		$user = $this->generateUsers();
		$this->userSession->setUser($user);
		$this->groupManager->get('admin')->addUser($user);

		$group = $this->groupManager->createGroup($this->getUniqueID());

		$result = $this->api->removeFromGroup([
			'userid' => $this->getUniqueID(),
			'_delete' => [
				'groupid' => $group->getGID()
			],
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(103, $result->getStatusCode());
	}


	public function testCreateSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$group1 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$_POST['groupid'] = $group1->getGID();
		$result = $this->api->addSubAdmin(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(\OC_SubAdmin::isSubAdminofGroup($user2->getUID(), $group1->getGID()));
		$group1->delete();

		$this->resetParams();

		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$_POST['groupid'] = 'admin';
		$result = $this->api->addSubAdmin(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertEquals(103, $result->getStatusCode());
		$this->assertFalse($result->succeeded());

		$this->resetParams();

		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$group1 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$_POST['groupid'] = $group1->getGID();
		$result = $this->api->addSubAdmin(array(
			'userid' => $this->getUniqueID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
		$group1->delete();

		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$group = $this->getUniqueID();
		$_POST['groupid'] = $group;
		$result = $this->api->addSubAdmin([
			'userid' => $user1->getUID()
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
		$this->assertEquals('Group:'.$group.' does not exist', $result->getMeta()['message']);
	}

	public function testRemoveSubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$group1 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		\OC_SubAdmin::createSubAdmin($user2->getUID(), $group1->getGID());
		$result = $this->api->removeSubAdmin(array(
			'userid' => $user2->getUID(),
			'_delete' => array(
				'groupid' => $group1->getGID(),
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertTrue(!\OC_SubAdmin::isSubAdminofGroup($user2->getUID(), $group1->getGID()));
		$group1->delete();

		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$result = $this->api->removeSubAdmin(array(
			'userid' => $this->getUniqueID(),
			'_delete' => array(
				'groupid' => $group1->getGID(),
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertEquals(101, $result->getStatusCode());
		$this->assertFalse($result->succeeded());

		$this->resetParams();

		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$group1 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		$_POST['groupid'] = $group1->getGID();
		$result = $this->api->removeSubAdmin(array(
			'userid' => $user2->getUID(),
			'_delete' => array(
				'groupid' => $group1->getGID(),
				),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
		$group1->delete();
	}

	public function testGetSubAdminGroups() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$group1 = $this->getUniqueID();
		$group1 = $this->groupManager->createGroup($group1);
		\OC_SubAdmin::createSubAdmin($user2->getUID(), $group1->getGID());
		$result = $this->api->getUserSubAdminGroups(array(
			'userid' => $user2->getUID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals($group1->getGID(), reset($data));
		$group1->delete();

		$user1 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$result = $this->api->getUserSubAdminGroups(array(
			'userid' => $this->getUniqueID(),
			));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}

	public function testSubAdminOfGroupAlreadySubAdmin() {
		$user1 = $this->generateUsers();
		$user2 = $this->generateUsers();
		$this->userSession->setUser($user1);
		$this->groupManager->get('admin')->addUser($user1);
		$group1 = $this->groupManager->createGroup($this->getUniqueID());

		//Make user2 subadmin of group1
		$_POST['groupid'] = $group1->getGID();
		$result = $this->api->addSubAdmin([
			'userid' => $user2->getUID(),
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());

		//Make user2 subadmin of group1 again
		$_POST['groupid'] = $group1->getGID();
		$result = $this->api->addSubAdmin([
			'userid' => $user2->getUID(),
		]);
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());
		$group1->delete();
	}
}
