<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author michag86 <micha_g@arcor.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
 *
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

use OCA\Provisioning_API\Users;
use OCP\API;
use OCP\IUserManager;
use OCP\IConfig;
use OCP\IUserSession;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase as OriginalTest;
use OCP\ILogger;

class UsersTest extends OriginalTest {
	
	/** @var IUserManager | PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var IConfig | PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var \OC\Group\Manager | PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;
	/** @var IUserSession | PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;
	/** @var ILogger | PHPUnit_Framework_MockObject_MockObject */
	protected $logger;
	/** @var Users | PHPUnit_Framework_MockObject_MockObject */
	protected $api;

	protected function tearDown() {
		$_GET = null;
		$_POST = null;
		parent::tearDown();
	}

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMockBuilder('OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder('OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();
		$this->api = $this->getMockBuilder('OCA\Provisioning_API\Users')
			->setConstructorArgs([
				$this->userManager,
				$this->config,
				$this->groupManager,
				$this->userSession,
				$this->logger,
			])
			->setMethods(['fillStorageInfo'])
			->getMock();
	}

	public function testGetUsersNotLoggedIn() {
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, API::RESPOND_UNAUTHORISED);
		$this->assertEquals($expected, $this->api->getUsers());
	}

	public function testGetUsersAsAdmin() {
		$_GET['search'] = 'MyCustomSearch';

		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('admin'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue(true));
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('MyCustomSearch', null, null)
			->will($this->returnValue(['Admin' => [], 'Foo' => [], 'Bar' => []]));

		$expected = new \OC\OCS\Result([
			'users' => [
				'Admin',
				'Foo',
				'Bar',
			],
		]);
		$this->assertEquals($expected, $this->api->getUsers());
	}

	public function testGetUsersAsSubAdmin() {
		$_GET['search'] = 'MyCustomSearch';

		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue(false));
		$firstGroup = $this->getMockBuilder('OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$firstGroup
			->expects($this->once())
			->method('getGID')
			->will($this->returnValue('FirstGroup'));
		$secondGroup = $this->getMockBuilder('OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$secondGroup
			->expects($this->once())
			->method('getGID')
			->will($this->returnValue('SecondGroup'));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdmin')
			->with($loggedInUser)
			->will($this->returnValue(true));
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($loggedInUser)
			->will($this->returnValue([$firstGroup, $secondGroup]));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->groupManager
			->expects($this->any())
			->method('displayNamesInGroup')
			->will($this->onConsecutiveCalls(['AnotherUserInTheFirstGroup' => []], ['UserInTheSecondGroup' => []]));

		$expected = new \OC\OCS\Result([
			'users' => [
				'AnotherUserInTheFirstGroup',
				'UserInTheSecondGroup',
			],
		]);
		$this->assertEquals($expected, $this->api->getUsers());
	}

	public function testGetUsersAsRegularUser() {
		$_GET['search'] = 'MyCustomSearch';

		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('regularUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdmin')
			->with($loggedInUser)
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, API::RESPOND_UNAUTHORISED);
		$this->assertEquals($expected, $this->api->getUsers());
	}

	public function testAddUserAlreadyExisting() {
		$_POST['userid'] = 'AlreadyExistingUser';
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('AlreadyExistingUser')
			->will($this->returnValue(true));
		$this->logger
			->expects($this->once())
			->method('error')
			->with('Failed addUser attempt: User already exists.', ['app' => 'ocs_api']);
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('adminUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);

		$expected = new \OC\OCS\Result(null, 102, 'User already exists');
		$this->assertEquals($expected, $this->api->addUser());
	}

	public function testAddUserNonExistingGroup() {
		$_POST['userid'] = 'NewUser';
		$_POST['groups'] = ['NonExistingGroup'];
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('adminUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('groupExists')
			->with('NonExistingGroup')
			->willReturn(false);

		$expected = new \OC\OCS\Result(null, 104, 'group NonExistingGroup does not exist');
		$this->assertEquals($expected, $this->api->addUser());
	}

	public function testAddUserExistingGroupNonExistingGroup() {
		$_POST['userid'] = 'NewUser';
		$_POST['groups'] = ['ExistingGroup', 'NonExistingGroup'];
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('adminUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);
		$this->groupManager
			->expects($this->exactly(2))
			->method('groupExists')
			->withConsecutive(
				['ExistingGroup'],
				['NonExistingGroup']
			)
			->will($this->returnValueMap([
				['ExistingGroup', true],
				['NonExistingGroup', false]
			]));

		$expected = new \OC\OCS\Result(null, 104, 'group NonExistingGroup does not exist');
		$this->assertEquals($expected, $this->api->addUser());
	}

	public function testAddUserSuccessful() {
		$_POST['userid'] = 'NewUser';
		$_POST['password'] = 'PasswordOfTheNewUser';
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->will($this->returnValue(false));
		$this->userManager
			->expects($this->once())
			->method('createUser')
			->with('NewUser', 'PasswordOfTheNewUser');
		$this->logger
			->expects($this->once())
			->method('info')
			->with('Successful addUser call with userid: NewUser', ['app' => 'ocs_api']);
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('adminUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->addUser());
	}

	public function testAddUserExistingGroup() {
		$_POST['userid'] = 'NewUser';
		$_POST['password'] = 'PasswordOfTheNewUser';
		$_POST['groups'] = ['ExistingGroup'];
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('adminUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('groupExists')
			->with('ExistingGroup')
			->willReturn(true);
		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userManager
			->expects($this->once())
			->method('createUser')
			->with('NewUser', 'PasswordOfTheNewUser')
			->willReturn($user);
		$group = $this->getMockBuilder('OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$group
			->expects($this->once())
			->method('addUser')
			->with($user);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('ExistingGroup')
			->willReturn($group);
		$this->logger
			->expects($this->exactly(2))
			->method('info')
			->withConsecutive(
				['Successful addUser call with userid: NewUser', ['app' => 'ocs_api']],
				['Added userid NewUser to group ExistingGroup', ['app' => 'ocs_api']]
			);

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->addUser());
	}

	public function testAddUserUnsuccessful() {
		$_POST['userid'] = 'NewUser';
		$_POST['password'] = 'PasswordOfTheNewUser';
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->will($this->returnValue(false));
		$this->userManager
			->expects($this->once())
			->method('createUser')
			->with('NewUser', 'PasswordOfTheNewUser')
			->will($this->throwException(new \Exception('User backend not found.')));
		$this->logger
			->expects($this->once())
			->method('error')
			->with('Failed addUser attempt with exception: User backend not found.', ['app' => 'ocs_api']);
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('adminUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);

		$expected = new \OC\OCS\Result(null, 101, 'Bad request');
		$this->assertEquals($expected, $this->api->addUser());
	}

	public function testAddUserAsRegularUser() {
		$_POST['userid'] = 'NewUser';
		$_POST['password'] = 'PasswordOfTheNewUser';
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('regularUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('regularUser')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdmin')
			->with($loggedInUser)
			->willReturn(false);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->with()
			->willReturn($subAdminManager);

		$expected = new \OC\OCS\Result(null, API::RESPOND_UNAUTHORISED);
		$this->assertEquals($expected, $this->api->addUser());	
	}

	public function testAddUserAsSubAdminNoGroup() {
		$_POST['userid'] = 'NewUser';
		$_POST['password'] = 'PasswordOfTheNewUser';
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('regularUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('regularUser')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdmin')
			->with($loggedInUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->with()
			->willReturn($subAdminManager);

		$expected = new \OC\OCS\Result(null, 106, 'no group specified (required for subadmins)');
		$this->assertEquals($expected, $this->api->addUser());	
	}

	public function testAddUserAsSubAdminValidGroupNotSubAdmin() {
		$_POST['userid'] = 'NewUser';
		$_POST['password'] = 'PasswordOfTheNewUser';
		$_POST['groups'] = ['ExistingGroup'];
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('regularUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('regularUser')
			->willReturn(false);
		$existingGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('ExistingGroup')
			->willReturn($existingGroup);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdmin')
			->with($loggedInUser)
			->willReturn(true);
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($loggedInUser, $existingGroup)
			->willReturn(false);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->with()
			->willReturn($subAdminManager);
		$this->groupManager
			->expects($this->once())
			->method('groupExists')
			->with('ExistingGroup')
			->willReturn(true);

		$expected = new \OC\OCS\Result(null, 105, 'insufficient privileges for group ExistingGroup');
		$this->assertEquals($expected, $this->api->addUser());	
	}

	public function testAddUserAsSubAdminExistingGroups() {
		$_POST['userid'] = 'NewUser';
		$_POST['password'] = 'PasswordOfTheNewUser';
		$_POST['groups'] = ['ExistingGroup1', 'ExistingGroup2'];
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('subAdminUser'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subAdminUser')
			->willReturn(false);
		$this->groupManager
			->expects($this->exactly(2))
			->method('groupExists')
			->withConsecutive(
				['ExistingGroup1'],
				['ExistingGroup2']
			)
			->willReturn(true);
		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userManager
			->expects($this->once())
			->method('createUser')
			->with('NewUser', 'PasswordOfTheNewUser')
			->willReturn($user);
		$existingGroup1 = $this->getMockBuilder('OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$existingGroup2 = $this->getMockBuilder('OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$existingGroup1
			->expects($this->once())
			->method('addUser')
			->with($user);
		$existingGroup2
			->expects($this->once())
			->method('addUser')
			->with($user);
		$this->groupManager
			->expects($this->exactly(4))
			->method('get')
			->withConsecutive(
				['ExistingGroup1'],
				['ExistingGroup2'],
				['ExistingGroup1'],
				['ExistingGroup2']
			)
			->will($this->returnValueMap([
				['ExistingGroup1', $existingGroup1],
				['ExistingGroup2', $existingGroup2]
			]));
		$this->logger
			->expects($this->exactly(3))
			->method('info')
			->withConsecutive(
				['Successful addUser call with userid: NewUser', ['app' => 'ocs_api']],
				['Added userid NewUser to group ExistingGroup1', ['app' => 'ocs_api']],
				['Added userid NewUser to group ExistingGroup2', ['app' => 'ocs_api']]
			);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$subAdminManager
			->expects($this->once())
			->method('isSubAdmin')
			->with($loggedInUser)
			->willReturn(true);
		$subAdminManager
			->expects($this->exactly(2))
			->method('isSubAdminOfGroup')
			->withConsecutive(
				[$loggedInUser, $existingGroup1],
				[$loggedInUser, $existingGroup2]
			)
			->willReturn(true);


		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->addUser());
	}


	public function testGetUserNotLoggedIn() {
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, API::RESPOND_UNAUTHORISED);
		$this->assertEquals($expected, $this->api->getUser(['userid' => 'UserToGet']));
	}

	public function testGetUserTargetDoesNotExist() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, API::RESPOND_NOT_FOUND, 'The requested user could not be found');
		$this->assertEquals($expected, $this->api->getUser(['userid' => 'UserToGet']));
	}

	public function testGetUserAsAdmin() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$targetUser->expects($this->once())
			->method('getEMailAddress')
			->willReturn('demo@owncloud.org');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(0))
			->method('getUserValue')
			->with('UserToGet', 'core', 'enabled', 'true')
			->will($this->returnValue('true'));
		$this->api
			->expects($this->once())
			->method('fillStorageInfo')
			->with('UserToGet')
			->will($this->returnValue(['DummyValue']));
		$targetUser
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('Demo User'));

		$expected = new \OC\OCS\Result(
			[
				'enabled' => 'true',
				'quota' => ['DummyValue'],
				'email' => 'demo@owncloud.org',
				'displayname' => 'Demo User',
			]
		);
		$this->assertEquals($expected, $this->api->getUser(['userid' => 'UserToGet']));
	}

	public function testGetUserAsSubAdminAndUserIsAccessible() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$targetUser
				->expects($this->once())
				->method('getEMailAddress')
				->willReturn('demo@owncloud.org');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->config
			->expects($this->at(0))
			->method('getUserValue')
			->with('UserToGet', 'core', 'enabled', 'true')
			->will($this->returnValue('true'));
		$this->api
			->expects($this->once())
			->method('fillStorageInfo')
			->with('UserToGet')
			->will($this->returnValue(['DummyValue']));
		$targetUser
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('Demo User'));

		$expected = new \OC\OCS\Result(
			[
				'enabled' => 'true',
				'quota' => ['DummyValue'],
				'email' => 'demo@owncloud.org',
				'displayname' => 'Demo User',
			]
		);
		$this->assertEquals($expected, $this->api->getUser(['userid' => 'UserToGet']));
	}

	public function testGetUserAsSubAdminAndUserIsNotAccessible() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, API::RESPOND_UNAUTHORISED);
		$this->assertEquals($expected, $this->api->getUser(['userid' => 'UserToGet']));
	}

	public function testGetUserAsSubAdminSelfLookup() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('subadmin')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->api
			->expects($this->once())
			->method('fillStorageInfo')
			->with('subadmin')
			->will($this->returnValue(['DummyValue']));
		$targetUser
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('Subadmin User'));
		$targetUser
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('subadmin@owncloud.org'));

		$expected = new \OC\OCS\Result([
			'quota' => ['DummyValue'],
			'email' => 'subadmin@owncloud.org',
			'displayname' => 'Subadmin User',
		]);
		$this->assertEquals($expected, $this->api->getUser(['userid' => 'subadmin']));
	}

	public function testEditUserNotLoggedIn() {
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, API::RESPOND_UNAUTHORISED);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit']));
	}

	public function testEditUserRegularUserSelfEditChangeDisplayName() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$targetUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));
		$targetUser
			->expects($this->once())
			->method('setDisplayName')
			->with('NewDisplayName');

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'display', 'value' => 'NewDisplayName']]));
	}

	public function testEditUserRegularUserSelfEditChangeEmailValid() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$targetUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));
		$targetUser
			->expects($this->once())
			->method('setEMailAddress')
			->with('demo@owncloud.org');

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'email', 'value' => 'demo@owncloud.org']]));
	}

	public function testEditUserRegularUserSelfEditChangeEmailInvalid() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$targetUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));

		$expected = new \OC\OCS\Result(null, 102);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'email', 'value' => 'demo.org']]));
	}

	public function testEditUserRegularUserSelfEditChangePassword() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$targetUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));
		$targetUser
			->expects($this->once())
			->method('setPassword')
			->with('NewPassword');

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'password', 'value' => 'NewPassword']]));
	}

	public function testEditUserRegularUserSelfEditChangeQuota() {
		$loggedInUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$targetUser = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));

		$expected = new \OC\OCS\Result(null, 997);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'quota', 'value' => 'NewQuota']]));
	}

	public function testEditUserAdminUserSelfEditChangeValidQuota() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();;
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setQuota')
			->with('2.9 MB');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('UserToEdit')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'quota', 'value' => '3042824']]));
	}

	public function testEditUserAdminUserSelfEditChangeInvalidQuota() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('UserToEdit')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 103, 'Invalid quota value ABC');
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'quota', 'value' => 'ABC']]));
	}

	public function testEditUserAdminUserEditChangeValidQuota() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setQuota')
			->with('2.9 MB');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'quota', 'value' => '3042824']]));
	}

	public function testEditUserSubadminUserAccessible() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setQuota')
			->with('2.9 MB');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'quota', 'value' => '3042824']]));
	}

	public function testEditUserSubadminUserInaccessible() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 997);
		$this->assertEquals($expected, $this->api->editUser(['userid' => 'UserToEdit', '_put' => ['key' => 'quota', 'value' => '3042824']]));
	}

	public function testDeleteUserNotLoggedIn() {
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 997);
		$this->assertEquals($expected, $this->api->deleteUser(['userid' => 'UserToDelete']));
	}

	public function testDeleteUserNotExistingUser() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 101);
		$this->assertEquals($expected, $this->api->deleteUser(['userid' => 'UserToDelete']));
	}

	public function testDeleteUserSelf() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToDelete'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToDelete'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($targetUser));

		$expected = new \OC\OCS\Result(null, 101);
		$this->assertEquals($expected, $this->api->deleteUser(['userid' => 'UserToDelete']));
	}

	public function testDeleteSuccessfulUserAsAdmin() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToDelete'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));
		$targetUser
			->expects($this->once())
			->method('delete')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->deleteUser(['userid' => 'UserToDelete']));
	}

	public function testDeleteUnsuccessfulUserAsAdmin() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToDelete'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));
		$targetUser
			->expects($this->once())
			->method('delete')
			->will($this->returnValue(false));

		$expected = new \OC\OCS\Result(null, 101);
		$this->assertEquals($expected, $this->api->deleteUser(['userid' => 'UserToDelete']));
	}

	public function testDeleteSuccessfulUserAsSubadmin() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToDelete'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$targetUser
			->expects($this->once())
			->method('delete')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->deleteUser(['userid' => 'UserToDelete']));
	}

	public function testDeleteUnsuccessfulUserAsSubadmin() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToDelete'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$targetUser
			->expects($this->once())
			->method('delete')
			->will($this->returnValue(false));

		$expected = new \OC\OCS\Result(null, 101);
		$this->assertEquals($expected, $this->api->deleteUser(['userid' => 'UserToDelete']));
	}

	public function testDeleteUserAsSubAdminAndUserIsNotAccessible() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToDelete'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 997);
		$this->assertEquals($expected, $this->api->deleteUser(['userid' => 'UserToDelete']));
	}

	public function testGetUsersGroupsNotLoggedIn() {
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 997);
		$this->assertEquals($expected, $this->api->getUsersGroups(['userid' => 'UserToLookup']));
	}

	public function testGetUsersGroupsTargetUserNotExisting() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));

		$expected = new \OC\OCS\Result(null, 998);
		$this->assertEquals($expected, $this->api->getUsersGroups(['userid' => 'UserToLookup']));
	}

	public function testGetUsersGroupsSelfTargetted() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToLookup'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToLookup'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToLookup')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('getUserGroupIds')
			->with($targetUser)
			->will($this->returnValue(['DummyValue']));

		$expected = new \OC\OCS\Result(['groups' => ['DummyValue']]);
		$this->assertEquals($expected, $this->api->getUsersGroups(['userid' => 'UserToLookup']));
	}

	public function testGetUsersGroupsForAdminUser() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToLookup'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToLookup')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('getUserGroupIds')
			->with($targetUser)
			->will($this->returnValue(['DummyValue']));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(['groups' => ['DummyValue']]);
		$this->assertEquals($expected, $this->api->getUsersGroups(['userid' => 'UserToLookup']));
	}

	public function testGetUsersGroupsForSubAdminUserAndUserIsAccessible() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToLookup'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToLookup')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$group1 = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$group1
			->expects($this->any())
			->method('getGID')
			->will($this->returnValue('Group1'));
		$group2 = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$group2
			->expects($this->any())
			->method('getGID')
			->will($this->returnValue('Group2'));
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($loggedInUser)
			->will($this->returnValue([$group1, $group2]));
		$this->groupManager
			->expects($this->any())
			->method('getUserGroupIds')
			->with($targetUser)
			->will($this->returnValue(['Group1']));

		$expected = new \OC\OCS\Result(['groups' => ['Group1']]);
		$this->assertEquals($expected, $this->api->getUsersGroups(['userid' => 'UserToLookup']));
	}


	public function testGetUsersGroupsForSubAdminUserAndUserIsInaccessible() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToLookup'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToLookup')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->groupManager
			->expects($this->any())
			->method('getUserGroupIds')
			->with($targetUser)
			->will($this->returnValue(['Group1']));

		$expected = new \OC\OCS\Result(null, 997);
		$this->assertEquals($expected, $this->api->getUsersGroups(['userid' => 'UserToLookup']));
	}

	public function testAddToGroupNotLoggedIn() {
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 997);
		$this->assertEquals($expected, $this->api->addToGroup([]));
	}

	public function testAddToGroupWithTargetGroupNotExisting() {
		$_POST['groupid'] = 'GroupToAddTo';

		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('admin'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('GroupToAddTo')
			->will($this->returnValue(null));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 102);
		$this->assertEquals($expected, $this->api->addToGroup(['userid' => 'TargetUser']));
	}

	public function testAddToGroupWithNoGroupSpecified() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('admin'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 101);
		$this->assertEquals($expected, $this->api->addToGroup(['userid' => 'TargetUser']));
	}

	public function testAddToGroupWithTargetUserNotExisting() {
		$_POST['groupid'] = 'GroupToAddTo';

		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('GroupToAddTo')
			->will($this->returnValue($targetGroup));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 103);
		$this->assertEquals($expected, $this->api->addToGroup(['userid' => 'TargetUser']));
	}

	public function testAddToGroupWithoutPermission() {
		$_POST['groupid'] = 'GroupToAddTo';

		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('admin'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(false));

		$expected = new \OC\OCS\Result(null, 997);
		$this->assertEquals($expected, $this->api->addToGroup(['userid' => 'TargetUser']));
	}

	public function testRemoveFromGroupWithoutLogIn() {
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 997);
		$this->assertEquals($expected, $this->api->removeFromGroup(['userid' => 'TargetUser', '_delete' => ['groupid' => 'TargetGroup']]));
	}

	public function testRemoveFromGroupWithNoTargetGroup() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$expected = new \OC\OCS\Result(null, 101);
		$this->assertEquals($expected, $this->api->removeFromGroup(['userid' => 'TargetUser', '_delete' => []]));
	}

	public function testRemoveFromGroupWithNotExistingTargetGroup() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 102);
		$this->assertEquals($expected, $this->api->removeFromGroup(['userid' => 'TargetUser', '_delete' => ['groupid' => 'TargetGroup']]));
	}

	public function testRemoveFromGroupWithNotExistingTargetUser() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->will($this->returnValue($targetGroup));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('TargetUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 103);
		$this->assertEquals($expected, $this->api->removeFromGroup(['userid' => 'TargetUser', '_delete' => ['groupid' => 'TargetGroup']]));
	}

	public function testRemoveFromGroupWithoutPermission() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('unauthorizedUser'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->will($this->returnValue($targetGroup));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('TargetUser')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('unauthorizedUser')
			->will($this->returnValue(false));

		$expected = new \OC\OCS\Result(null, 104);
		$this->assertEquals($expected, $this->api->removeFromGroup(['userid' => 'TargetUser', '_delete' => ['groupid' => 'TargetGroup']]));
	}

	public function testRemoveFromGroupAsAdminFromAdmin() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$targetGroup
			->expects($this->once())
			->method('getGID')
			->will($this->returnValue('admin'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('admin')
			->will($this->returnValue($targetGroup));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('admin')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 105, 'Cannot remove yourself from the admin group');
		$this->assertEquals($expected, $this->api->removeFromGroup(['userid' => 'admin', '_delete' => ['groupid' => 'admin']]));
	}

	public function testRemoveFromGroupAsSubAdminFromSubAdmin() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$targetGroup
			->expects($this->any())
			->method('getGID')
			->will($this->returnValue('subadmin'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('subadmin')
			->will($this->returnValue($targetGroup));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('subadmin')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminofGroup')
			->with($loggedInUser, $targetGroup)
			->will($this->returnValue(true));
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($loggedInUser)
			->will($this->returnValue([$targetGroup]));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));

		$expected = new \OC\OCS\Result(null, 105, 'Cannot remove yourself from this group as you are a SubAdmin');
		$this->assertEquals($expected, $this->api->removeFromGroup(['userid' => 'subadmin', '_delete' => ['groupid' => 'subadmin']]));
	}

	public function testRemoveFromGroupSuccessful() {
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('admin')
			->will($this->returnValue($targetGroup));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('AnotherUser')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));
		$targetGroup
			->expects($this->once())
			->method('removeUser')
			->with($targetUser);

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->removeFromGroup(['userid' => 'AnotherUser', '_delete' => ['groupid' => 'admin']]));
	}

	public function testAddSubAdminWithNotExistingTargetUser() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 101, 'User does not exist');
		$this->assertEquals($expected, $this->api->addSubAdmin(['userid' => 'NotExistingUser']));
	}

	public function testAddSubAdminWithNotExistingTargetGroup() {
		$_POST['groupid'] = 'NotExistingGroup';

		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('NotExistingGroup')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 102, 'Group:NotExistingGroup does not exist');
		$this->assertEquals($expected, $this->api->addSubAdmin(['userid' => 'ExistingUser']));
	}

	public function testAddSubAdminToAdminGroup() {
		$_POST['groupid'] = 'ADmiN';

		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('ADmiN')
			->will($this->returnValue($targetGroup));

		$expected = new \OC\OCS\Result(null, 103, 'Cannot create subadmins for admin group');
		$this->assertEquals($expected, $this->api->addSubAdmin(['userid' => 'ExistingUser']));
	}

	public function testAddSubAdminTwice() {
		$_POST['groupid'] = 'TargetGroup';

		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->will($this->returnValue($targetGroup));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->addSubAdmin(['userid' => 'ExistingUser']));
	}

	public function testAddSubAdminSuccessful() {
		$_POST['groupid'] = 'TargetGroup';

		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->will($this->returnValue($targetGroup));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(false));
		$subAdminManager
			->expects($this->once())
			->method('createSubAdmin')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->addSubAdmin(['userid' => 'ExistingUser']));
	}

	public function testAddSubAdminUnsuccessful() {
		$_POST['groupid'] = 'TargetGroup';

		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->will($this->returnValue($targetGroup));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(false));
		$subAdminManager
			->expects($this->once())
			->method('createSubAdmin')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 103, 'Unknown error occurred');
		$this->assertEquals($expected, $this->api->addSubAdmin(['userid' => 'ExistingUser']));
	}

	public function testRemoveSubAdminNotExistingTargetUser() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 101, 'User does not exist');
		$this->assertEquals($expected, $this->api->removeSubAdmin(['userid' => 'NotExistingUser', '_delete' => ['groupid' => 'GroupToDeleteFrom']]));
	}

	public function testRemoveSubAdminNotExistingTargetGroup() {
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('GroupToDeleteFrom')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 101, 'Group does not exist');
		$this->assertEquals($expected, $this->api->removeSubAdmin(['userid' => 'ExistingUser', '_delete' => ['groupid' => 'GroupToDeleteFrom']]));
	}

	public function testRemoveSubAdminFromNotASubadmin() {
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('GroupToDeleteFrom')
			->will($this->returnValue($targetGroup));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 102, 'User is not a subadmin of this group');
		$this->assertEquals($expected, $this->api->removeSubAdmin(['userid' => 'ExistingUser', '_delete' => ['groupid' => 'GroupToDeleteFrom']]));
	}

	public function testRemoveSubAdminSuccessful() {
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('GroupToDeleteFrom')
			->will($this->returnValue($targetGroup));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(true));
		$subAdminManager
			->expects($this->once())
			->method('deleteSubAdmin')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->removeSubAdmin(['userid' => 'ExistingUser', '_delete' => ['groupid' => 'GroupToDeleteFrom']]));
	}

	public function testRemoveSubAdminUnsuccessful() {
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('GroupToDeleteFrom')
			->will($this->returnValue($targetGroup));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(true));
		$subAdminManager
			->expects($this->once())
			->method('deleteSubAdmin')
			->with($targetUser, $targetGroup)
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 103, 'Unknown error occurred');
		$this->assertEquals($expected, $this->api->removeSubAdmin(['userid' => 'ExistingUser', '_delete' => ['groupid' => 'GroupToDeleteFrom']]));
	}

	public function testGetUserSubAdminGroupsNotExistingTargetUser() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->will($this->returnValue(null));

		$expected = new \OC\OCS\Result(null, 101, 'User does not exist');
		$this->assertEquals($expected, $this->api->getUserSubAdminGroups(['userid' => 'RequestedUser']));
	}

	public function testGetUserSubAdminGroupsWithGroups() {
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$targetGroup
			->expects($this->once())
			->method('getGID')
			->will($this->returnValue('TargetGroup'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($targetUser)
			->will($this->returnValue([$targetGroup]));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(['TargetGroup'], 100);
		$this->assertEquals($expected, $this->api->getUserSubAdminGroups(['userid' => 'RequestedUser']));
	}

	public function testGetUserSubAdminGroupsWithoutGroups() {
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($targetUser)
			->will($this->returnValue([]));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));

		$expected = new \OC\OCS\Result(null, 102, 'Unknown error occurred');
		$this->assertEquals($expected, $this->api->getUserSubAdminGroups(['userid' => 'RequestedUser']));
	}

	public function testEnableUser() {
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setEnabled')
			->with(true);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->will($this->returnValue($targetUser));
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('admin'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->enableUser(['userid' => 'RequestedUser']));
	}

	public function testDisableUser() {
		$targetUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setEnabled')
			->with(false);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->will($this->returnValue($targetUser));
		$loggedInUser = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('admin'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue(true));

		$expected = new \OC\OCS\Result(null, 100);
		$this->assertEquals($expected, $this->api->disableUser(['userid' => 'RequestedUser']));
	}
}
