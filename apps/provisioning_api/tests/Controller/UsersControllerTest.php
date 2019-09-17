<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@owncloud.com>
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

namespace OCA\Provisioning_API\Tests\Controller;

use Exception;
use OC\Accounts\AccountManager;
use OC\Authentication\Token\RemoteWipe;
use OC\Group\Manager;
use OCA\FederatedFileSharing\AppInfo\Application;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Provisioning_API\FederatedFileSharingFactory;
use OCP\App\IAppManager;
use OCP\Mail\IEMailTemplate;
use OCA\Settings\Mailer\NewUserMailHelper;
use OC\SubAdmin;
use OCA\Provisioning_API\Controller\UsersController;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroup;
use OCP\ILogger;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class UsersControllerTest extends TestCase {

	/** @var IUserManager|PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var IConfig|PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var IAppManager|PHPUnit_Framework_MockObject_MockObject */
	protected $appManager;
	/** @var Manager|PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;
	/** @var IUserSession|PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;
	/** @var ILogger|PHPUnit_Framework_MockObject_MockObject */
	protected $logger;
	/** @var UsersController|PHPUnit_Framework_MockObject_MockObject */
	protected $api;
	/** @var AccountManager|PHPUnit_Framework_MockObject_MockObject */
	protected $accountManager;
	/** @var IRequest|PHPUnit_Framework_MockObject_MockObject */
	protected $request;
	/** @var IFactory|PHPUnit_Framework_MockObject_MockObject */
	private $l10nFactory;
	/** @var NewUserMailHelper|PHPUnit_Framework_MockObject_MockObject */
	private $newUserMailHelper;
	/** @var FederatedFileSharingFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $federatedFileSharingFactory;
	/** @var ISecureRandom|\PHPUnit_Framework_MockObject_MockObject */
	private $secureRandom;
	/** @var RemoteWipe|MockObject */
	private $remoteWipe;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->groupManager = $this->createMock(Manager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->request = $this->createMock(IRequest::class);
		$this->accountManager = $this->createMock(AccountManager::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->newUserMailHelper = $this->createMock(NewUserMailHelper::class);
		$this->federatedFileSharingFactory = $this->createMock(FederatedFileSharingFactory::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->remoteWipe = $this->createMock(RemoteWipe::class);

		$this->api = $this->getMockBuilder(UsersController::class)
			->setConstructorArgs([
				'provisioning_api',
				$this->request,
				$this->userManager,
				$this->config,
				$this->appManager,
				$this->groupManager,
				$this->userSession,
				$this->accountManager,
				$this->logger,
				$this->l10nFactory,
				$this->newUserMailHelper,
				$this->federatedFileSharingFactory,
				$this->secureRandom,
				$this->remoteWipe,
			])
			->setMethods(['fillStorageInfo'])
			->getMock();
	}

	public function testGetUsersAsAdmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
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
			->with('MyCustomSearch')
			->will($this->returnValue(['Admin' => [], 'Foo' => [], 'Bar' => []]));

		$expected = ['users' => [
				'Admin',
				'Foo',
				'Bar',
			],
		];
		$this->assertEquals($expected, $this->api->getUsers('MyCustomSearch')->getData());
	}

	public function testGetUsersAsSubAdmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$expected = [
			'users' => [
				'AnotherUserInTheFirstGroup',
				'UserInTheSecondGroup',
			],
		];
		$this->assertEquals($expected, $this->api->getUsers('MyCustomSearch')->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 102
	 */
	public function testAddUserAlreadyExisting() {
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('AlreadyExistingUser')
			->will($this->returnValue(true));
		$this->logger
			->expects($this->once())
			->method('error')
			->with('Failed addUser attempt: User already exists.', ['app' => 'ocs_api']);
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$this->api->addUser('AlreadyExistingUser', 'password', '', '', []);
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 104
	 * @expectedExceptionMessage group NonExistingGroup does not exist
	 */
	public function testAddUserNonExistingGroup() {
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$this->api->addUser('NewUser', 'pass', '', '', ['NonExistingGroup']);
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 104
	 * @expectedExceptionMessage group NonExistingGroup does not exist
	 */
	public function testAddUserExistingGroupNonExistingGroup() {
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$this->api->addUser('NewUser', 'pass', '', '', ['ExistingGroup', 'NonExistingGroup']);
	}

	public function testAddUserSuccessful() {
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
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$this->assertTrue(key_exists(
			'id',
			$this->api->addUser('NewUser', 'PasswordOfTheNewUser')->getData()
		));
	}

	public function testAddUserSuccessfulWithDisplayName() {
		$api = $this->getMockBuilder('OCA\Provisioning_API\Controller\UsersController')
			->setConstructorArgs([
				'provisioning_api',
				$this->request,
				$this->userManager,
				$this->config,
				$this->appManager,
				$this->groupManager,
				$this->userSession,
				$this->accountManager,
				$this->logger,
				$this->l10nFactory,
				$this->newUserMailHelper,
				$this->federatedFileSharingFactory,
				$this->secureRandom,
				$this->remoteWipe
			])
			->setMethods(['editUser'])
			->getMock();

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
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('adminUser'));
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);
		$api
			->expects($this->once())
			->method('editUser')
			->with('NewUser', 'display', 'DisplayNameOfTheNewUser');

		$this->assertTrue(key_exists(
			'id',
			$api->addUser('NewUser', 'PasswordOfTheNewUser', 'DisplayNameOfTheNewUser')->getData()
		));
	}

	public function testAddUserSuccessfulGenerateUserID() {
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(function($appid, $key, $default) {
				if($key === 'newUser.generateUserID') {
					return 'yes';
				}
				return null;
			});
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->with($this->anything())
			->will($this->returnValue(false));
		$this->userManager
			->expects($this->once())
			->method('createUser')
			->with($this->anything(), 'PasswordOfTheNewUser');
		$this->logger
			->expects($this->once())
			->method('info')
			->with($this->stringStartsWith('Successful addUser call with userid: '), ['app' => 'ocs_api']);
		$loggedInUser = $this->getMockBuilder(IUser::class)
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
		$this->secureRandom->expects($this->any())
			->method('generate')
			->with(10)
			->willReturnCallback(function() { return (string)rand(1000000000, 9999999999); });

		$this->assertTrue(key_exists(
			'id',
			$this->api->addUser('', 'PasswordOfTheNewUser')->getData()
		));
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 111
	 * @expectedExceptionMessage Could not create non-existing user id
	 */
	public function testAddUserFailedToGenerateUserID() {
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(function($appid, $key, $default) {
				if($key === 'newUser.generateUserID') {
					return 'yes';
				}
				return null;
			});
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->with($this->anything())
			->will($this->returnValue(true));
		$this->userManager
			->expects($this->never())
			->method('createUser');
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$this->api->addUser('', 'PasswordOfTheNewUser')->getData();
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 110
	 * @expectedExceptionMessage Required email address was not provided
	 */
	public function testAddUserEmailRequired() {
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(function($appid, $key, $default) {
				if($key === 'newUser.requireEmail') {
					return 'yes';
				}
				return null;
			});
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->will($this->returnValue(false));
		$this->userManager
			->expects($this->never())
			->method('createUser');
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$this->assertTrue(key_exists(
			'id',
			$this->api->addUser('NewUser', 'PasswordOfTheNewUser')->getData()
		));
	}

	public function testAddUserExistingGroup() {
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$loggedInUser = $this->getMockBuilder(IUser::class)
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
		$user = $this->getMockBuilder(IUser::class)
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

		$this->assertTrue(key_exists(
			'id',
				$this->api->addUser('NewUser', 'PasswordOfTheNewUser', '', '', ['ExistingGroup'])->getData()
		));
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 * @expectedExceptionMessage Bad request
	 */
	public function testAddUserUnsuccessful() {
		$exception = new Exception('User backend not found.');
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->will($this->returnValue(false));
		$this->userManager
			->expects($this->once())
			->method('createUser')
			->with('NewUser', 'PasswordOfTheNewUser')
			->will($this->throwException($exception));
		$this->logger
			->expects($this->once())
			->method('logException')
			->with($exception, [
				'message' => 'Failed addUser attempt with exception.',
				'level' => ILogger::ERROR,
				'app' => 'ocs_api',
			]);
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$this->api->addUser('NewUser', 'PasswordOfTheNewUser');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 106
	 * @expectedExceptionMessage no group specified (required for subadmins)
	 */
	public function testAddUserAsSubAdminNoGroup() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
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
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->with()
			->willReturn($subAdminManager);

		$this->api->addUser('NewUser', 'PasswordOfTheNewUser', '', '', []);
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 105
	 * @expectedExceptionMessage insufficient privileges for group ExistingGroup
	 */
	public function testAddUserAsSubAdminValidGroupNotSubAdmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$this->api->addUser('NewUser', 'PasswordOfTheNewUser', '', '', ['ExistingGroup'])->getData();
	}

	public function testAddUserAsSubAdminExistingGroups() {
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$loggedInUser = $this->getMockBuilder(IUser::class)
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
		$user = $this->getMockBuilder(IUser::class)
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
			->expects($this->exactly(2))
			->method('isSubAdminOfGroup')
			->withConsecutive(
				[$loggedInUser, $existingGroup1],
				[$loggedInUser, $existingGroup2]
			)
			->willReturn(true);

		$this->assertTrue(key_exists(
			'id',
			$this->api->addUser('NewUser', 'PasswordOfTheNewUser', '', '', ['ExistingGroup1', 'ExistingGroup2'])->getData()
		));
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 404
	 * @expectedExceptionMessage User does not exist
	 */
	public function testGetUserTargetDoesNotExist() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
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

		$this->api->getUser('UserToGet');
	}

	public function testGetUserDataAsAdmin() {
		$group = $this->getMockBuilder(IGroup::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser->expects($this->once())
			->method('getEMailAddress')
			->willReturn('demo@nextcloud.com');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->exactly(2))
			->method('get')
			->with('UID')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->any())
			->method('getUserGroups')
			->willReturn([$group, $group, $group]);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->willReturn([$group]);
		$group->expects($this->at(0))
			->method('getGID')
			->willReturn('group0');
		$group->expects($this->at(1))
			->method('getGID')
			->willReturn('group1');
		$group->expects($this->at(2))
			->method('getGID')
			->willReturn('group2');
		$group->expects($this->at(3))
			->method('getGID')
			->willReturn('group3');
		$this->accountManager->expects($this->any())->method('getUser')
			->with($targetUser)
			->willReturn(
				[
					AccountManager::PROPERTY_ADDRESS => ['value' => 'address'],
					AccountManager::PROPERTY_PHONE => ['value' => 'phone'],
					AccountManager::PROPERTY_TWITTER => ['value' => 'twitter'],
					AccountManager::PROPERTY_WEBSITE => ['value' => 'website'],
				]
			);
		$this->config
			->expects($this->at(0))
			->method('getUserValue')
			->with('UID', 'core', 'enabled', 'true')
			->will($this->returnValue('true'));
		$this->config
			->expects($this->at(1))
			->method('getUserValue')
			->with('UID', 'core', 'lang')
			->will($this->returnValue('de'));
		$this->api
			->expects($this->once())
			->method('fillStorageInfo')
			->with('UID')
			->will($this->returnValue(['DummyValue']));

		$backend = $this->createMock(UserInterface::class);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$targetUser
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('Demo User'));
		$targetUser
			->expects($this->once())
			->method('getHome')
			->will($this->returnValue('/var/www/newtcloud/data/UID'));
		$targetUser
			->expects($this->once())
			->method('getLastLogin')
			->will($this->returnValue(1521191471));
		$targetUser
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('Database'));
		$targetUser
			->expects($this->once())
			->method('getBackend')
			->willReturn($backend);
		$targetUser
			->expects($this->exactly(6))
			->method('getUID')
			->will($this->returnValue('UID'));

		$expected = [
			'id' => 'UID',
			'enabled' => true,
			'storageLocation' => '/var/www/newtcloud/data/UID',
			'lastLogin' => 1521191471000,
			'backend' => 'Database',
			'subadmin' => ['group3'],
			'quota' => ['DummyValue'],
			'email' => 'demo@nextcloud.com',
			'displayname' => 'Demo User',
			'phone' => 'phone',
			'address' => 'address',
			'website' => 'website',
			'twitter' => 'twitter',
			'groups' => ['group0', 'group1', 'group2'],
			'language' => 'de',
			'locale' => null,
			'backendCapabilities' => [
				'setDisplayName' => true,
				'setPassword' => true,
			]
		];
		$this->assertEquals($expected, $this->invokePrivate($this->api, 'getUserData', ['UID']));
	}

	public function testGetUserDataAsSubAdminAndUserIsAccessible() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser
				->expects($this->once())
				->method('getEMailAddress')
				->willReturn('demo@nextcloud.com');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->exactly(2))
			->method('get')
			->with('UID')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->any())
			->method('getUserGroups')
			->willReturn([]);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(true));
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->willReturn([]);
		$this->groupManager
			->expects($this->exactly(2))
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->config
			->expects($this->at(0))
			->method('getUserValue')
			->with('UID', 'core', 'enabled', 'true')
			->will($this->returnValue('true'));
		$this->config
			->expects($this->at(1))
			->method('getUserValue')
			->with('UID', 'core', 'lang')
			->will($this->returnValue('da'));
		$this->api
			->expects($this->once())
			->method('fillStorageInfo')
			->with('UID')
			->will($this->returnValue(['DummyValue']));

		$backend = $this->createMock(UserInterface::class);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$targetUser
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('Demo User'));
		$targetUser
			->expects($this->once())
			->method('getHome')
			->will($this->returnValue('/var/www/newtcloud/data/UID'));
		$targetUser
			->expects($this->once())
			->method('getLastLogin')
			->will($this->returnValue(1521191471));
		$targetUser
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('Database'));
		$targetUser
			->expects($this->once())
			->method('getBackend')
			->willReturn($backend);
		$targetUser
			->expects($this->exactly(6))
			->method('getUID')
			->will($this->returnValue('UID'));
		$this->accountManager->expects($this->any())->method('getUser')
			->with($targetUser)
			->willReturn(
				[
					AccountManager::PROPERTY_ADDRESS => ['value' => 'address'],
					AccountManager::PROPERTY_PHONE => ['value' => 'phone'],
					AccountManager::PROPERTY_TWITTER => ['value' => 'twitter'],
					AccountManager::PROPERTY_WEBSITE => ['value' => 'website'],
				]
			);

		$expected = [
			'id' => 'UID',
			'enabled' => true,
			'storageLocation' => '/var/www/newtcloud/data/UID',
			'lastLogin' => 1521191471000,
			'backend' => 'Database',
			'subadmin' => [],
			'quota' => ['DummyValue'],
			'email' => 'demo@nextcloud.com',
			'displayname' => 'Demo User',
			'phone' => 'phone',
			'address' => 'address',
			'website' => 'website',
			'twitter' => 'twitter',
			'groups' => [],
			'language' => 'da',
			'locale' => null,
			'backendCapabilities' => [
				'setDisplayName' => true,
				'setPassword' => true,
			]
		];
		$this->assertEquals($expected, $this->invokePrivate($this->api, 'getUserData', ['UID']));
	}


	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 997
	 */
	public function testGetUserDataAsSubAdminAndUserIsNotAccessible() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)
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

		$this->invokePrivate($this->api, 'getUser', ['UserToGet']);
	}

	public function testGetUserDataAsSubAdminSelfLookup() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->exactly(2))
			->method('get')
			->with('UID')
			->will($this->returnValue($targetUser));
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('UID')
			->will($this->returnValue(false));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->will($this->returnValue(false));
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->willReturn([]);
		$this->groupManager
			->expects($this->exactly(2))
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->groupManager
			->expects($this->any())
			->method('getUserGroups')
			->willReturn([]);
		$this->api
			->expects($this->once())
			->method('fillStorageInfo')
			->with('UID')
			->will($this->returnValue(['DummyValue']));

		$backend = $this->createMock(UserInterface::class);
		$backend->expects($this->atLeastOnce())
			->method('implementsActions')
			->willReturn(false);

		$targetUser
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('Subadmin User'));
		$targetUser
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('subadmin@nextcloud.com'));
		$targetUser
			->expects($this->exactly(6))
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser
			->expects($this->once())
			->method('getHome')
			->will($this->returnValue('/var/www/newtcloud/data/UID'));
		$targetUser
			->expects($this->once())
			->method('getLastLogin')
			->will($this->returnValue(1521191471));
		$targetUser
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('Database'));
		$targetUser
			->expects($this->once())
			->method('getBackend')
			->willReturn($backend);
		$this->config
			->expects($this->at(0))
			->method('getUserValue')
			->with('UID', 'core', 'lang')
			->will($this->returnValue('ru'));
		$this->accountManager->expects($this->any())->method('getUser')
			->with($targetUser)
			->willReturn(
				[
					AccountManager::PROPERTY_ADDRESS => ['value' => 'address'],
					AccountManager::PROPERTY_PHONE => ['value' => 'phone'],
					AccountManager::PROPERTY_TWITTER => ['value' => 'twitter'],
					AccountManager::PROPERTY_WEBSITE => ['value' => 'website'],
				]
			);

		$expected = [
			'id' => 'UID',
			'storageLocation' => '/var/www/newtcloud/data/UID',
			'lastLogin' => 1521191471000,
			'backend' => 'Database',
			'subadmin' => [],
			'quota' => ['DummyValue'],
			'email' => 'subadmin@nextcloud.com',
			'displayname' => 'Subadmin User',
			'phone' => 'phone',
			'address' => 'address',
			'website' => 'website',
			'twitter' => 'twitter',
			'groups' => [],
			'language' => 'ru',
			'locale' => null,
			'backendCapabilities' => [
				'setDisplayName' => false,
				'setPassword' => false,
			]
		];
		$this->assertEquals($expected, $this->invokePrivate($this->api, 'getUserData', ['UID']));
	}

	public function testEditUserRegularUserSelfEditChangeDisplayName() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser = $this->getMockBuilder(IUser::class)
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
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'display', 'NewDisplayName')->getData());
	}

	public function testEditUserRegularUserSelfEditChangeEmailValid() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser = $this->getMockBuilder(IUser::class)
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
			->with('demo@nextcloud.com');
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'email', 'demo@nextcloud.com')->getData());
	}


	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 102
	 */
	public function testEditUserRegularUserSelfEditChangeEmailInvalid() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser = $this->getMockBuilder(IUser::class)
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
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->api->editUser('UserToEdit', 'email', 'demo.org');
	}

	public function testEditUserRegularUserSelfEditChangePassword() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser = $this->getMockBuilder(IUser::class)
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
			->method('canChangePassword')
			->will($this->returnValue(true));
		$targetUser
			->expects($this->once())
			->method('setPassword')
			->with('NewPassword');
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'password', 'NewPassword')->getData());
	}


	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 997
	 */
	public function testEditUserRegularUserSelfEditChangeQuota() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser = $this->getMockBuilder(IUser::class)
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
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->api->editUser('UserToEdit', 'quota', 'NewQuota');
	}

	public function testEditUserAdminUserSelfEditChangeValidQuota() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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
			->expects($this->exactly(3))
			->method('isAdmin')
			->with('UID')
			->will($this->returnValue(true));
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'quota', '3042824')->getData());
	}


	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 103
	 * @expectedExceptionMessage Invalid quota value ABC
	 */
	public function testEditUserAdminUserSelfEditChangeInvalidQuota() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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
			->expects($this->exactly(3))
			->method('isAdmin')
			->with('UID')
			->will($this->returnValue(true));
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->api->editUser('UserToEdit', 'quota', 'ABC');
	}

	public function testEditUserAdminUserEditChangeValidQuota() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'quota', '3042824')->getData());
	}

	public function testEditUserSelfEditChangeLanguage() {

		$this->l10nFactory->expects($this->once())
			->method('findAvailableLanguages')
			->willReturn(['en', 'de', 'sv']);
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnMap([
				['allow_user_to_change_display_name', true, true],
				['force_language', false, false],
			]);

		$loggedInUser = $this->createMock(IUser::class);
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$targetUser = $this->createMock(IUser::class);
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('UserToEdit', 'core', 'lang', 'de');
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
			->expects($this->atLeastOnce())
			->method('isAdmin')
			->with('UserToEdit')
			->will($this->returnValue(false));
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'language', 'de')->getData());
	}

	public function dataEditUserSelfEditChangeLanguageButForced() {
		return [
			['de'],
			[true],
		];
	}

	/**
	 * @dataProvider dataEditUserSelfEditChangeLanguageButForced
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 */
	public function testEditUserSelfEditChangeLanguageButForced($forced) {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnMap([
				['allow_user_to_change_display_name', true, true],
				['force_language', false, $forced],
			]);

		$loggedInUser = $this->createMock(IUser::class);
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));
		$targetUser = $this->createMock(IUser::class);
		$this->config->expects($this->never())
			->method('setUserValue');
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
			->expects($this->atLeastOnce())
			->method('isAdmin')
			->with('UserToEdit')
			->will($this->returnValue(false));
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'language', 'de')->getData());
	}

	public function testEditUserAdminEditChangeLanguage() {

		$this->l10nFactory->expects($this->once())
			->method('findAvailableLanguages')
			->willReturn(['en', 'de', 'sv']);

		$loggedInUser = $this->createMock(IUser::class);
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->createMock(IUser::class);
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('UserToEdit', 'core', 'lang', 'de');
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
		$subAdminManager = $this->createMock(SubAdmin::class);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'language', 'de')->getData());
	}

	/**
	 * @dataProvider dataEditUserSelfEditChangeLanguageButForced
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 */
	public function testEditUserAdminEditChangeLanguageInvalidLanguage() {

		$this->l10nFactory->expects($this->once())
			->method('findAvailableLanguages')
			->willReturn(['en', 'de', 'sv']);

		$loggedInUser = $this->createMock(IUser::class);
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->createMock(IUser::class);
		$this->config->expects($this->never())
			->method('setUserValue');
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
		$subAdminManager = $this->createMock(SubAdmin::class);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UserToEdit'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'language', 'ru')->getData());
	}

	public function testEditUserSubadminUserAccessible() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'quota', '3042824')->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 997
	 */
	public function testEditUserSubadminUserInaccessible() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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
		$targetUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));

		$this->api->editUser('UserToEdit', 'quota', 'value');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 */
	public function testDeleteUserNotExistingUser() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->api->deleteUser('UserToDelete');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 */
	public function testDeleteUserSelf() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('UID'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UID'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($targetUser));

		$this->api->deleteUser('UserToDelete');
	}

	public function testDeleteSuccessfulUserAsAdmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UID'));
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

		$this->assertEquals([], $this->api->deleteUser('UserToDelete')->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 */
	public function testDeleteUnsuccessfulUserAsAdmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UID'));
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

		$this->api->deleteUser('UserToDelete');
	}

	public function testDeleteSuccessfulUserAsSubadmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UID'));
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

		$this->assertEquals([], $this->api->deleteUser('UserToDelete')->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 */
	public function testDeleteUnsuccessfulUserAsSubadmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UID'));
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

		$this->api->deleteUser('UserToDelete');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 997
	 */
	public function testDeleteUserAsSubAdminAndUserIsNotAccessible() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UID'));
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

		$this->api->deleteUser('UserToDelete');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 998
	 */
	public function testGetUsersGroupsTargetUserNotExisting() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));

		$this->api->getUsersGroups('UserToLookup');
	}

	public function testGetUsersGroupsSelfTargetted() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('UserToLookup'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals(['groups' => ['DummyValue']], $this->api->getUsersGroups('UserToLookup')->getData());
	}

	public function testGetUsersGroupsForAdminUser() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals(['groups' => ['DummyValue']], $this->api->getUsersGroups('UserToLookup')->getData());
	}

	public function testGetUsersGroupsForSubAdminUserAndUserIsAccessible() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals(['groups' => ['Group1']], $this->api->getUsersGroups('UserToLookup')->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 997
	 */
	public function testGetUsersGroupsForSubAdminUserAndUserIsInaccessible() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->api->getUsersGroups('UserToLookup');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 102
	 */
	public function testAddToGroupWithTargetGroupNotExisting() {
		$this->groupManager->expects($this->once())
			->method('get')
			->with('GroupToAddTo')
			->willReturn(null);

		$this->api->addToGroup('TargetUser', 'GroupToAddTo');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 */
	public function testAddToGroupWithNoGroupSpecified() {
		$this->api->addToGroup('TargetUser');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 103
	 */
	public function testAddToGroupWithTargetUserNotExisting() {
		$targetGroup = $this->createMock(IGroup::class);
		$this->groupManager->expects($this->once())
			->method('get')
			->with('GroupToAddTo')
			->willReturn($targetGroup);

		$this->api->addToGroup('TargetUser', 'GroupToAddTo');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 104
	 */
	public function testAddToGroupNoSubadmin() {
		$targetUser = $this->createMock(IUser::class);
		$loggedInUser = $this->createMock(IUser::class);
		$loggedInUser->expects($this->once())
			->method('getUID')
			->willReturn('subadmin');

		$targetGroup = $this->createMock(IGroup::class);
		$targetGroup->expects($this->never())
			->method('addUser')
			->with($targetUser);

		$this->groupManager->expects($this->once())
			->method('get')
			->with('GroupToAddTo')
			->willReturn($targetGroup);


		$subAdminManager = $this->createMock(SubAdmin::class);
		$subAdminManager->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($loggedInUser, $targetGroup)
			->willReturn(false);

		$this->groupManager->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->groupManager->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);

		$this->userManager->expects($this->once())
			->method('get')
			->with('TargetUser')
			->willReturn($targetUser);

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);

		$this->api->addToGroup('TargetUser', 'GroupToAddTo');
	}

	public function testAddToGroupSuccessAsSubadmin() {
		$targetUser = $this->createMock(IUser::class);
		$loggedInUser = $this->createMock(IUser::class);
		$loggedInUser->expects($this->once())
			->method('getUID')
			->willReturn('subadmin');

		$targetGroup = $this->createMock(IGroup::class);
		$targetGroup->expects($this->once())
			->method('addUser')
			->with($targetUser);

		$this->groupManager->expects($this->once())
			->method('get')
			->with('GroupToAddTo')
			->willReturn($targetGroup);


		$subAdminManager = $this->createMock(SubAdmin::class);
		$subAdminManager->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($loggedInUser, $targetGroup)
			->willReturn(true);

		$this->groupManager->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->groupManager->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);

		$this->userManager->expects($this->once())
			->method('get')
			->with('TargetUser')
			->willReturn($targetUser);

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);

		$this->assertEquals(new DataResponse(), $this->api->addToGroup('TargetUser', 'GroupToAddTo'));
	}

	public function testAddToGroupSuccessAsAdmin() {
		$targetUser = $this->createMock(IUser::class);
		$loggedInUser = $this->createMock(IUser::class);
		$loggedInUser->expects($this->once())
			->method('getUID')
			->willReturn('admin');

		$targetGroup = $this->createMock(IGroup::class);
		$targetGroup->expects($this->once())
			->method('addUser')
			->with($targetUser);

		$this->groupManager->expects($this->once())
			->method('get')
			->with('GroupToAddTo')
			->willReturn($targetGroup);


		$subAdminManager = $this->createMock(SubAdmin::class);
		$subAdminManager->expects($this->never())
			->method('isSubAdminOfGroup');

		$this->groupManager->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->groupManager->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$this->userManager->expects($this->once())
			->method('get')
			->with('TargetUser')
			->willReturn($targetUser);

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);

		$this->assertEquals(new DataResponse(), $this->api->addToGroup('TargetUser', 'GroupToAddTo'));
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 */
	public function testRemoveFromGroupWithNoTargetGroup() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));

		$this->api->removeFromGroup('TargetUser', '');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 */
	public function testRemoveFromGroupWithEmptyTargetGroup() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));

		$this->api->removeFromGroup('TargetUser', '');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 102
	 */
	public function testRemoveFromGroupWithNotExistingTargetGroup() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->will($this->returnValue(null));

		$this->api->removeFromGroup('TargetUser', 'TargetGroup');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 103
	 */
	public function testRemoveFromGroupWithNotExistingTargetUser() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->api->removeFromGroup('TargetUser', 'TargetGroup');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 104
	 */
	public function testRemoveFromGroupWithoutPermission() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('unauthorizedUser'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->api->removeFromGroup('TargetUser', 'TargetGroup');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 105
	 * @expectedExceptionMessage Cannot remove yourself from the admin group
	 */
	public function testRemoveFromGroupAsAdminFromAdmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('admin'));
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
			->with('Admin')
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

		$this->api->removeFromGroup('Admin', 'admin');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 105
	 * @expectedExceptionMessage Cannot remove yourself from this group as you are a SubAdmin
	 */
	public function testRemoveFromGroupAsSubAdminFromSubAdmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('subadmin'));
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
			->with('SubAdmin')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($loggedInUser, $targetGroup)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));

		$this->api->removeFromGroup('SubAdmin', 'subadmin');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 105
	 * @expectedExceptionMessage Not viable to remove user from the last group you are SubAdmin of
	 */
	public function testRemoveFromGroupAsSubAdminFromLastSubAdminGroup() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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
			->with('AnotherUser')
			->will($this->returnValue($targetUser));
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($loggedInUser, $targetGroup)
			->will($this->returnValue(true));
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->will($this->returnValue($subAdminManager));
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($loggedInUser)
			->will($this->returnValue([$targetGroup]));

		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('subadmin')
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('getUserGroupIds')
			->with($targetUser)
			->willReturn(['subadmin', 'other group']);

		$this->api->removeFromGroup('AnotherUser', 'subadmin');
	}

	public function testRemoveFromGroupSuccessful() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('admin'));
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals([], $this->api->removeFromGroup('AnotherUser', 'admin')->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 * @expectedExceptionMessage User does not exist
	 */
	public function testAddSubAdminWithNotExistingTargetUser() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->will($this->returnValue(null));

		$this->api->addSubAdmin('NotExistingUser', '');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 102
	 * @expectedExceptionMessage Group does not exist
	 */
	public function testAddSubAdminWithNotExistingTargetGroup() {

		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->api->addSubAdmin('ExistingUser', 'NotExistingGroup');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 103
	 * @expectedExceptionMessage Cannot create subadmins for admin group
	 */
	public function testAddSubAdminToAdminGroup() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$targetGroup
			->expects($this->once())
			->method('getGID')
			->will($this->returnValue('admin'));
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

		$this->api->addSubAdmin('ExistingUser', 'ADmiN');
	}

	public function testAddSubAdminTwice() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals([], $this->api->addSubAdmin('ExistingUser', 'TargetGroup')->getData());
	}

	public function testAddSubAdminSuccessful() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals([], $this->api->addSubAdmin('ExistingUser', 'TargetGroup')->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 * @expectedExceptionMessage User does not exist
	 */
	public function testRemoveSubAdminNotExistingTargetUser() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->will($this->returnValue(null));

		$this->api->removeSubAdmin('NotExistingUser', 'GroupToDeleteFrom');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 * @expectedExceptionMessage Group does not exist
	 */
	public function testRemoveSubAdminNotExistingTargetGroup() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->api->removeSubAdmin('ExistingUser', 'GroupToDeleteFrom');
	}


	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 102
	 * @expectedExceptionMessage User is not a subadmin of this group
	 */
	public function testRemoveSubAdminFromNotASubadmin() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->api->removeSubAdmin('ExistingUser', 'GroupToDeleteFrom');
	}

	public function testRemoveSubAdminSuccessful() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals([], $this->api->removeSubAdmin('ExistingUser', 'GroupToDeleteFrom')->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 404
	 * @expectedExceptionMessage User does not exist
	 */
	public function testGetUserSubAdminGroupsNotExistingTargetUser() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->will($this->returnValue(null));

		$this->api->getUserSubAdminGroups('RequestedUser');
	}

	public function testGetUserSubAdminGroupsWithGroups() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals(['TargetGroup'], $this->api->getUserSubAdminGroups('RequestedUser')->getData());
	}

	public function testEnableUser() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setEnabled')
			->with(true);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->will($this->returnValue($targetUser));
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals([], $this->api->enableUser('RequestedUser')->getData());
	}

	public function testDisableUser() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setEnabled')
			->with(false);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->will($this->returnValue($targetUser));
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
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

		$this->assertEquals([], $this->api->disableUser('RequestedUser')->getData());
	}

	public function testGetCurrentUserLoggedIn() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('UID');

		$this->userSession->expects($this->once())->method('getUser')
			->willReturn($user);

		/** @var UsersController | PHPUnit_Framework_MockObject_MockObject $api */
		$api = $this->getMockBuilder('OCA\Provisioning_API\Controller\UsersController')
			->setConstructorArgs([
				'provisioning_api',
				$this->request,
				$this->userManager,
				$this->config,
				$this->appManager,
				$this->groupManager,
				$this->userSession,
				$this->accountManager,
				$this->logger,
				$this->l10nFactory,
				$this->newUserMailHelper,
				$this->federatedFileSharingFactory,
				$this->secureRandom,
				$this->remoteWipe,
			])
			->setMethods(['getUserData'])
			->getMock();

		$api->expects($this->once())->method('getUserData')->with('UID')
			->willReturn(
				[
					'id' => 'UID',
					'enabled' => 'true',
					'quota' => ['DummyValue'],
					'email' => 'demo@nextcloud.com',
					'displayname' => 'Demo User',
					'phone' => 'phone',
					'address' => 'address',
					'website' => 'website',
					'twitter' => 'twitter'
				]
			);

		$expected = [
			'id' => 'UID',
			'enabled' => 'true',
			'quota' => ['DummyValue'],
			'email' => 'demo@nextcloud.com',
			'phone' => 'phone',
			'address' => 'address',
			'website' => 'website',
			'twitter' => 'twitter',
			'display-name' => 'Demo User'
		];

		$this->assertSame($expected, $api->getCurrentUser()->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 */
	public function testGetCurrentUserNotLoggedIn() {

		$this->userSession->expects($this->once())->method('getUser')
			->willReturn(null);

		$this->api->getCurrentUser();
	}


	public function testGetUser() {
		/** @var UsersController | PHPUnit_Framework_MockObject_MockObject $api */
		$api = $this->getMockBuilder('OCA\Provisioning_API\Controller\UsersController')
			->setConstructorArgs([
				'provisioning_api',
				$this->request,
				$this->userManager,
				$this->config,
				$this->appManager,
				$this->groupManager,
				$this->userSession,
				$this->accountManager,
				$this->logger,
				$this->l10nFactory,
				$this->newUserMailHelper,
				$this->federatedFileSharingFactory,
				$this->secureRandom,
				$this->remoteWipe,
			])
			->setMethods(['getUserData'])
			->getMock();

		$expected = [
			'id' => 'UID',
			'enabled' => 'true',
			'quota' => ['DummyValue'],
			'email' => 'demo@nextcloud.com',
			'phone' => 'phone',
			'address' => 'address',
			'website' => 'website',
			'twitter' => 'twitter',
			'displayname' => 'Demo User'
		];

		$api->expects($this->once())->method('getUserData')
			->with('uid')
			->willReturn($expected);

		$this->assertSame($expected, $api->getUser('uid')->getData());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 998
	 */
	public function testResendWelcomeMessageWithNotExistingTargetUser() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->will($this->returnValue(null));

		$this->api->resendWelcomeMessage('NotExistingUser');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 997
	 */
	public function testResendWelcomeMessageAsSubAdminAndUserIsNotAccessible() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('subadmin'));
		$targetUser = $this->getMockBuilder(IUser::class)
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

		$this->api->resendWelcomeMessage('UserToGet');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 * @expectedExceptionMessage Email address not available
	 */
	public function testResendWelcomeMessageNoEmail() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser = $this->getMockBuilder(IUser::class)
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
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->will($this->returnValue(''));

		$this->api->resendWelcomeMessage('UserToGet');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 * @expectedExceptionMessage Email address not available
	 */
	public function testResendWelcomeMessageNullEmail() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser = $this->getMockBuilder(IUser::class)
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
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->will($this->returnValue(null));

		$this->api->resendWelcomeMessage('UserToGet');
	}

	public function testResendWelcomeMessageSuccess() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser
			->method('getUID')
			->willReturn('user-id');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
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
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->will($this->returnValue('abc@example.org'));
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->newUserMailHelper
			->expects($this->at(0))
			->method('generateTemplate')
			->willReturn($emailTemplate);
		$this->newUserMailHelper
			->expects($this->at(1))
			->method('sendMail')
			->with($targetUser, $emailTemplate);

		$this->api->resendWelcomeMessage('UserToGet');
	}

	public function testResendWelcomeMessageSuccessWithFallbackLanguage() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser
			->method('getUID')
			->willReturn('user-id');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
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
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->will($this->returnValue('abc@example.org'));
		$l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->newUserMailHelper
			->expects($this->at(0))
			->method('generateTemplate')
			->willReturn($emailTemplate);
		$this->newUserMailHelper
			->expects($this->at(1))
			->method('sendMail')
			->with($targetUser, $emailTemplate);

		$this->api->resendWelcomeMessage('UserToGet');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 102
	 * @expectedExceptionMessage Sending email failed
	 */
	public function testResendWelcomeMessageFailed() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser
			->method('getUID')
			->willReturn('user-id');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($loggedInUser));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
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
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->will($this->returnValue('abc@example.org'));
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->newUserMailHelper
			->expects($this->at(0))
			->method('generateTemplate')
			->willReturn($emailTemplate);
		$this->newUserMailHelper
			->expects($this->at(1))
			->method('sendMail')
			->with($targetUser, $emailTemplate)
			->willThrowException(new \Exception());

		$this->api->resendWelcomeMessage('UserToGet');
	}


	public function dataGetEditableFields() {
		return [
			[false, false, []],
			[false,  true, [
				AccountManager::PROPERTY_PHONE,
				AccountManager::PROPERTY_ADDRESS,
				AccountManager::PROPERTY_WEBSITE,
				AccountManager::PROPERTY_TWITTER,
			]],
			[ true, false, [
				AccountManager::PROPERTY_DISPLAYNAME,
				AccountManager::PROPERTY_EMAIL,
			]],
			[ true,  true ,[
				AccountManager::PROPERTY_DISPLAYNAME,
				AccountManager::PROPERTY_EMAIL,
				AccountManager::PROPERTY_PHONE,
				AccountManager::PROPERTY_ADDRESS,
				AccountManager::PROPERTY_WEBSITE,
				AccountManager::PROPERTY_TWITTER,
			]]
		];
	}

	/**
	 * @dataProvider dataGetEditableFields
	 *
	 * @param bool $allowedToChangeDisplayName
	 * @param bool $federatedSharingEnabled
	 * @param array $expected
	 */
	public function testGetEditableFields(bool $allowedToChangeDisplayName, bool $federatedSharingEnabled, array $expected) {
		$this->config
			->method('getSystemValue')
			->with(
				$this->equalTo('allow_user_to_change_display_name'),
				$this->anything()
			)->willReturn($allowedToChangeDisplayName);
		$this->appManager
			->method('isEnabledForUser')
			->with($this->equalTo('federatedfilesharing'))
			->willReturn($federatedSharingEnabled);

		$shareprovider = $this->createMock(FederatedShareProvider::class);
		$shareprovider->method('isLookupServerUploadEnabled')->willReturn(true);

		$federatedFileSharing = $this->createMock(Application::class);
		$federatedFileSharing
			->method('getFederatedShareProvider')
			->willReturn($shareprovider);

		$this->federatedFileSharingFactory
			->method('get')
			->willReturn($federatedFileSharing);

		$expectedResp = new DataResponse($expected);
		$this->assertEquals($expectedResp, $this->api->getEditableFields());
	}
}
