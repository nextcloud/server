<?php

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author GretaD <gretadoci@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author michag86 <micha_g@arcor.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sujith Haridasan <sujith.h@gmail.com>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author zulan <git@zulan.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Provisioning_API\Tests\Controller;

use Exception;
use OC\Authentication\Token\RemoteWipe;
use OC\Group\Manager;
use OC\KnownUser\KnownUserService;
use OC\PhoneNumberUtil;
use OC\SubAdmin;
use OCA\Provisioning_API\Controller\UsersController;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\IAccountPropertyCollection;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IL10N;
use OCP\IPhoneNumberUtil;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\ISecureRandom;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UsersControllerTest extends TestCase {
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var Manager|MockObject */
	protected $groupManager;
	/** @var IUserSession|MockObject */
	protected $userSession;
	/** @var LoggerInterface|MockObject */
	protected $logger;
	/** @var UsersController|MockObject */
	protected $api;
	/** @var IAccountManager|MockObject */
	protected $accountManager;
	/** @var IURLGenerator|MockObject */
	protected $urlGenerator;
	/** @var IRequest|MockObject */
	protected $request;
	/** @var IFactory|MockObject */
	private $l10nFactory;
	/** @var NewUserMailHelper|MockObject */
	private $newUserMailHelper;
	/** @var ISecureRandom|MockObject */
	private $secureRandom;
	/** @var RemoteWipe|MockObject */
	private $remoteWipe;
	/** @var KnownUserService|MockObject */
	private $knownUserService;
	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;
	/** @var IPhoneNumberUtil */
	private $phoneNumberUtil;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(Manager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->accountManager = $this->createMock(IAccountManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->newUserMailHelper = $this->createMock(NewUserMailHelper::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->remoteWipe = $this->createMock(RemoteWipe::class);
		$this->knownUserService = $this->createMock(KnownUserService::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->phoneNumberUtil = new PhoneNumberUtil();

		$this->api = $this->getMockBuilder(UsersController::class)
			->setConstructorArgs([
				'provisioning_api',
				$this->request,
				$this->userManager,
				$this->config,
				$this->groupManager,
				$this->userSession,
				$this->accountManager,
				$this->urlGenerator,
				$this->logger,
				$this->l10nFactory,
				$this->newUserMailHelper,
				$this->secureRandom,
				$this->remoteWipe,
				$this->knownUserService,
				$this->eventDispatcher,
				$this->phoneNumberUtil,
			])
			->onlyMethods(['fillStorageInfo'])
			->getMock();
	}

	public function testGetUsersAsAdmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->willReturn(true);
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('MyCustomSearch')
			->willReturn(['Admin' => [], 'Foo' => [], 'Bar' => []]);

		$expected = [
			'users' => [
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
			->willReturn('subadmin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->willReturn(false);
		$firstGroup = $this->getMockBuilder('OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$firstGroup
			->expects($this->once())
			->method('getGID')
			->willReturn('FirstGroup');
		$secondGroup = $this->getMockBuilder('OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$secondGroup
			->expects($this->once())
			->method('getGID')
			->willReturn('SecondGroup');
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdmin')
			->with($loggedInUser)
			->willReturn(true);
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($loggedInUser)
			->willReturn([$firstGroup, $secondGroup]);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
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


	public function testAddUserAlreadyExisting() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(102);

		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('AlreadyExistingUser')
			->willReturn(true);
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
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);
		$l10n = $this->createMock(IL10N::class);
		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->with('provisioning_api')
			->willReturn($l10n);

		$this->api->addUser('AlreadyExistingUser', 'password', '', '', []);
	}


	public function testAddUserNonExistingGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('group NonExistingGroup does not exist');
		$this->expectExceptionCode(104);

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
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
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


	public function testAddUserExistingGroupNonExistingGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('group NonExistingGroup does not exist');
		$this->expectExceptionCode(104);

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
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
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
			->willReturnMap([
				['ExistingGroup', true],
				['NonExistingGroup', false]
			]);

		$this->api->addUser('NewUser', 'pass', '', '', ['ExistingGroup', 'NonExistingGroup']);
	}

	public function testAddUserSuccessful() {
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
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
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
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
		$api = $this->getMockBuilder(UsersController::class)
			->setConstructorArgs([
				'provisioning_api',
				$this->request,
				$this->userManager,
				$this->config,
				$this->groupManager,
				$this->userSession,
				$this->accountManager,
				$this->urlGenerator,
				$this->logger,
				$this->l10nFactory,
				$this->newUserMailHelper,
				$this->secureRandom,
				$this->remoteWipe,
				$this->knownUserService,
				$this->eventDispatcher,
				$this->phoneNumberUtil,
			])
			->onlyMethods(['editUser'])
			->getMock();

		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
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
			->willReturn('adminUser');
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($loggedInUser);
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
			->willReturnCallback(function ($appid, $key, $default) {
				if ($key === 'newUser.generateUserID') {
					return 'yes';
				}
				return null;
			});
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->with($this->anything())
			->willReturn(false);
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
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);
		$this->secureRandom->expects($this->any())
			->method('generate')
			->with(10)
			->willReturnCallback(function () {
				return (string)rand(100000000, 999999999);
			});

		$this->assertTrue(key_exists(
			'id',
			$this->api->addUser('', 'PasswordOfTheNewUser')->getData()
		));
	}

	public function testAddUserSuccessfulGeneratePassword() {
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$newUser = $this->createMock(IUser::class);
		$newUser->expects($this->once())
			->method('setEMailAddress');
		$this->userManager
			->expects($this->once())
			->method('createUser')
			->willReturn($newUser);
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
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);
		$this->eventDispatcher
			->expects($this->once())
			->method('dispatchTyped')
			->with(new GenerateSecurePasswordEvent());

		$this->assertTrue(key_exists(
			'id',
			$this->api->addUser('NewUser', '', '', 'foo@bar')->getData()
		));
	}


	public function testAddUserFailedToGenerateUserID() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Could not create non-existing user id');
		$this->expectExceptionCode(111);

		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(function ($appid, $key, $default) {
				if ($key === 'newUser.generateUserID') {
					return 'yes';
				}
				return null;
			});
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->with($this->anything())
			->willReturn(true);
		$this->userManager
			->expects($this->never())
			->method('createUser');
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);

		$this->api->addUser('', 'PasswordOfTheNewUser')->getData();
	}


	public function testAddUserEmailRequired() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Required email address was not provided');
		$this->expectExceptionCode(110);

		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(function ($appid, $key, $default) {
				if ($key === 'newUser.requireEmail') {
					return 'yes';
				}
				return null;
			});
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$this->userManager
			->expects($this->never())
			->method('createUser');
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
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
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
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


	public function testAddUserUnsuccessful() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Bad request');
		$this->expectExceptionCode(101);

		$exception = new Exception('User backend not found.');
		$this->userManager
			->expects($this->once())
			->method('userExists')
			->with('NewUser')
			->willReturn(false);
		$this->userManager
			->expects($this->once())
			->method('createUser')
			->with('NewUser', 'PasswordOfTheNewUser')
			->will($this->throwException($exception));
		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Failed addUser attempt with exception.',
				[
					'app' => 'ocs_api',
					'exception' => $exception
				]
			);
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('adminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('adminUser')
			->willReturn(true);

		$this->api->addUser('NewUser', 'PasswordOfTheNewUser');
	}


	public function testAddUserAsSubAdminNoGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('no group specified (required for subadmins)');
		$this->expectExceptionCode(106);

		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('regularUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
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


	public function testAddUserAsSubAdminValidGroupNotSubAdmin() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('insufficient privileges for group ExistingGroup');
		$this->expectExceptionCode(105);

		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('regularUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
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
			->willReturn('subAdminUser');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
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
			->willReturnMap([
				['ExistingGroup1', $existingGroup1],
				['ExistingGroup2', $existingGroup2]
			]);
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


	public function testGetUserTargetDoesNotExist() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('User does not exist');
		$this->expectExceptionCode(404);

		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->willReturn(null);

		$this->api->getUser('UserToGet');
	}

	public function testGetUserDataAsAdmin() {
		$group0 = $this->createMock(IGroup::class);
		$group1 = $this->createMock(IGroup::class);
		$group2 = $this->createMock(IGroup::class);
		$group3 = $this->createMock(IGroup::class);
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser->expects($this->once())
			->method('getSystemEMailAddress')
			->willReturn('demo@nextcloud.com');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->exactly(2))
			->method('get')
			->with('UID')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);
		$this->groupManager
			->expects($this->any())
			->method('getUserGroups')
			->willReturn([$group0, $group1, $group2]);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->willReturn([$group3]);
		$group0->expects($this->once())
			->method('getGID')
			->willReturn('group0');
		$group1->expects($this->once())
			->method('getGID')
			->willReturn('group1');
		$group2->expects($this->once())
			->method('getGID')
			->willReturn('group2');
		$group3->expects($this->once())
			->method('getGID')
			->willReturn('group3');

		$this->mockAccount($targetUser, [
			IAccountManager::PROPERTY_ADDRESS => ['value' => 'address'],
			IAccountManager::PROPERTY_PHONE => ['value' => 'phone'],
			IAccountManager::PROPERTY_TWITTER => ['value' => 'twitter'],
			IAccountManager::PROPERTY_FEDIVERSE => ['value' => 'fediverse'],
			IAccountManager::PROPERTY_WEBSITE => ['value' => 'website'],
			IAccountManager::PROPERTY_ORGANISATION => ['value' => 'organisation'],
			IAccountManager::PROPERTY_ROLE => ['value' => 'role'],
			IAccountManager::PROPERTY_HEADLINE => ['value' => 'headline'],
			IAccountManager::PROPERTY_BIOGRAPHY => ['value' => 'biography'],
			IAccountManager::PROPERTY_PROFILE_ENABLED => ['value' => '1'],
		]);
		$this->config
			->method('getUserValue')
			->willReturnMap([
				['UID', 'core', 'enabled', 'true', 'true'],
			]);
		$this->api
			->expects($this->once())
			->method('fillStorageInfo')
			->with('UID')
			->willReturn(['DummyValue']);

		$backend = $this->createMock(UserInterface::class);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$targetUser
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Demo User');
		$targetUser
			->expects($this->once())
			->method('getHome')
			->willReturn('/var/www/newtcloud/data/UID');
		$targetUser
			->expects($this->once())
			->method('getLastLogin')
			->willReturn(1521191471);
		$targetUser
			->expects($this->once())
			->method('getBackendClassName')
			->willReturn('Database');
		$targetUser
			->expects($this->once())
			->method('getBackend')
			->willReturn($backend);
		$targetUser
			->method('getUID')
			->willReturn('UID');

		$this->l10nFactory
			->expects($this->once())
			->method('getUserLanguage')
			->with($targetUser)
			->willReturn('de');

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
			'display-name' => 'Demo User',
			'phone' => 'phone',
			'address' => 'address',
			'website' => 'website',
			'twitter' => 'twitter',
			'fediverse' => 'fediverse',
			'groups' => ['group0', 'group1', 'group2'],
			'language' => 'de',
			'locale' => null,
			'backendCapabilities' => [
				'setDisplayName' => true,
				'setPassword' => true,
			],
			'additional_mail' => [],
			'organisation' => 'organisation',
			'role' => 'role',
			'headline' => 'headline',
			'biography' => 'biography',
			'profile_enabled' => '1',
			'notify_email' => null,
			'manager' => '',
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
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser
			->expects($this->once())
			->method('getSystemEMailAddress')
			->willReturn('demo@nextcloud.com');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->exactly(2))
			->method('get')
			->with('UID')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);
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
			->willReturn(true);
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->willReturn([]);
		$this->groupManager
			->expects($this->exactly(2))
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->config
			->method('getUserValue')
			->willReturnMap([
				['UID', 'core', 'enabled', 'true', 'true'],
			]);
		$this->api
			->expects($this->once())
			->method('fillStorageInfo')
			->with('UID')
			->willReturn(['DummyValue']);

		$backend = $this->createMock(UserInterface::class);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$targetUser
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Demo User');
		$targetUser
			->expects($this->never())
			->method('getHome');
		$targetUser
			->expects($this->once())
			->method('getLastLogin')
			->willReturn(1521191471);
		$targetUser
			->expects($this->once())
			->method('getBackendClassName')
			->willReturn('Database');
		$targetUser
			->expects($this->once())
			->method('getBackend')
			->willReturn($backend);
		$targetUser
			->method('getUID')
			->willReturn('UID');

		$this->mockAccount($targetUser, [
			IAccountManager::PROPERTY_ADDRESS => ['value' => 'address'],
			IAccountManager::PROPERTY_PHONE => ['value' => 'phone'],
			IAccountManager::PROPERTY_TWITTER => ['value' => 'twitter'],
			IAccountManager::PROPERTY_FEDIVERSE => ['value' => 'fediverse'],
			IAccountManager::PROPERTY_WEBSITE => ['value' => 'website'],
			IAccountManager::PROPERTY_ORGANISATION => ['value' => 'organisation'],
			IAccountManager::PROPERTY_ROLE => ['value' => 'role'],
			IAccountManager::PROPERTY_HEADLINE => ['value' => 'headline'],
			IAccountManager::PROPERTY_BIOGRAPHY => ['value' => 'biography'],
			IAccountManager::PROPERTY_PROFILE_ENABLED => ['value' => '1'],
		]);

		$this->l10nFactory
			->expects($this->once())
			->method('getUserLanguage')
			->with($targetUser)
			->willReturn('da');

		$expected = [
			'id' => 'UID',
			'enabled' => true,
			'lastLogin' => 1521191471000,
			'backend' => 'Database',
			'subadmin' => [],
			'quota' => ['DummyValue'],
			'email' => 'demo@nextcloud.com',
			'displayname' => 'Demo User',
			'display-name' => 'Demo User',
			'phone' => 'phone',
			'address' => 'address',
			'website' => 'website',
			'twitter' => 'twitter',
			'fediverse' => 'fediverse',
			'groups' => [],
			'language' => 'da',
			'locale' => null,
			'backendCapabilities' => [
				'setDisplayName' => true,
				'setPassword' => true,
			],
			'additional_mail' => [],
			'organisation' => 'organisation',
			'role' => 'role',
			'headline' => 'headline',
			'biography' => 'biography',
			'profile_enabled' => '1',
			'notify_email' => null,
			'manager' => '',
		];
		$this->assertEquals($expected, $this->invokePrivate($this->api, 'getUserData', ['UID']));
	}



	public function testGetUserDataAsSubAdminAndUserIsNotAccessible() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(998);

		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->exactly(3))
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(false);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);

		$this->invokePrivate($this->api, 'getUser', ['UserToGet']);
	}

	public function testGetUserDataAsSubAdminSelfLookup() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->exactly(2))
			->method('get')
			->with('UID')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('UID')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(false);
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->willReturn([]);
		$this->groupManager
			->expects($this->exactly(2))
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->groupManager
			->expects($this->any())
			->method('getUserGroups')
			->willReturn([]);
		$this->api
			->expects($this->once())
			->method('fillStorageInfo')
			->with('UID')
			->willReturn(['DummyValue']);

		$backend = $this->createMock(UserInterface::class);
		$backend->expects($this->atLeastOnce())
			->method('implementsActions')
			->willReturn(false);

		$targetUser
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Subadmin User');
		$targetUser
			->expects($this->once())
			->method('getSystemEMailAddress')
			->willReturn('subadmin@nextcloud.com');
		$targetUser
			->method('getUID')
			->willReturn('UID');
		$targetUser
			->expects($this->never())
			->method('getHome');
		$targetUser
			->expects($this->once())
			->method('getLastLogin')
			->willReturn(1521191471);
		$targetUser
			->expects($this->once())
			->method('getBackendClassName')
			->willReturn('Database');
		$targetUser
			->expects($this->once())
			->method('getBackend')
			->willReturn($backend);
		$this->mockAccount($targetUser, [
			IAccountManager::PROPERTY_ADDRESS => ['value' => 'address'],
			IAccountManager::PROPERTY_PHONE => ['value' => 'phone'],
			IAccountManager::PROPERTY_TWITTER => ['value' => 'twitter'],
			IAccountManager::PROPERTY_FEDIVERSE => ['value' => 'fediverse'],
			IAccountManager::PROPERTY_WEBSITE => ['value' => 'website'],
			IAccountManager::PROPERTY_ORGANISATION => ['value' => 'organisation'],
			IAccountManager::PROPERTY_ROLE => ['value' => 'role'],
			IAccountManager::PROPERTY_HEADLINE => ['value' => 'headline'],
			IAccountManager::PROPERTY_BIOGRAPHY => ['value' => 'biography'],
			IAccountManager::PROPERTY_PROFILE_ENABLED => ['value' => '1'],
		]);

		$this->l10nFactory
			->expects($this->once())
			->method('getUserLanguage')
			->with($targetUser)
			->willReturn('ru');

		$expected = [
			'id' => 'UID',
			'lastLogin' => 1521191471000,
			'backend' => 'Database',
			'subadmin' => [],
			'quota' => ['DummyValue'],
			'email' => 'subadmin@nextcloud.com',
			'displayname' => 'Subadmin User',
			'display-name' => 'Subadmin User',
			'phone' => 'phone',
			'address' => 'address',
			'website' => 'website',
			'twitter' => 'twitter',
			'fediverse' => 'fediverse',
			'groups' => [],
			'language' => 'ru',
			'locale' => null,
			'backendCapabilities' => [
				'setDisplayName' => false,
				'setPassword' => false,
			],
			'additional_mail' => [],
			'organisation' => 'organisation',
			'role' => 'role',
			'headline' => 'headline',
			'biography' => 'biography',
			'profile_enabled' => '1',
			'notify_email' => null,
			'manager' => '',
		];
		$this->assertEquals($expected, $this->invokePrivate($this->api, 'getUserData', ['UID']));
	}

	public function dataSearchByPhoneNumbers(): array {
		return [
			'Invalid country' => ['Not a country code', ['12345' => ['NaN']], 400, null, null, []],
			'No number to search' => ['DE', ['12345' => ['NaN']], 200, null, null, []],
			'Valid number but no match' => ['DE', ['12345' => ['0711 / 25 24 28-90']], 200, ['+4971125242890'], [], []],
			'Invalid number' => ['FR', ['12345' => ['0711 / 25 24 28-90']], 200, null, null, []],
			'Invalid and valid number' => ['DE', ['12345' => ['NaN', '0711 / 25 24 28-90']], 200, ['+4971125242890'], [], []],
			'Valid and invalid number' => ['DE', ['12345' => ['0711 / 25 24 28-90', 'NaN']], 200, ['+4971125242890'], [], []],
			'Valid number and a match' => ['DE', ['12345' => ['0711 / 25 24 28-90']], 200, ['+4971125242890'], ['+4971125242890' => 'admin'], ['12345' => 'admin@localhost']],
			'Same number twice, later hits' => ['DE', ['12345' => ['0711 / 25 24 28-90'], '23456' => ['0711 / 25 24 28-90']], 200, ['+4971125242890'], ['+4971125242890' => 'admin'], ['23456' => 'admin@localhost']],
		];
	}

	/**
	 * @dataProvider dataSearchByPhoneNumbers
	 * @param string $location
	 * @param array $search
	 * @param int $status
	 * @param array $expected
	 */
	public function testSearchByPhoneNumbers(string $location, array $search, int $status, ?array $searchUsers, ?array $userMatches, array $expected) {
		$knownTo = 'knownTo';
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($knownTo);
		$this->userSession->method('getUser')
			->willReturn($user);

		if ($searchUsers === null) {
			$this->accountManager->expects($this->never())
				->method('searchUsers');
		} else {
			$this->accountManager->expects($this->once())
				->method('searchUsers')
				->with(IAccountManager::PROPERTY_PHONE, $searchUsers)
				->willReturn($userMatches);

			$this->knownUserService->expects($this->once())
				->method('deleteKnownTo')
				->with($knownTo);

			$this->knownUserService->expects($this->exactly(count($expected)))
				->method('storeIsKnownToUser')
				->with($knownTo, $this->anything());
		}

		$this->urlGenerator->method('getAbsoluteURL')
			->with('/')
			->willReturn('https://localhost/');

		$response = $this->api->searchByPhoneNumbers($location, $search);

		self::assertEquals($status, $response->getStatus());
		self::assertEquals($expected, $response->getData());
	}

	public function testEditUserRegularUserSelfEditChangeDisplayName() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$targetUser
			->expects($this->once())
			->method('getBackend')
			->willReturn($this->createMock(ISetDisplayNameBackend::class));
		$targetUser
			->expects($this->once())
			->method('setDisplayName')
			->with('NewDisplayName')
			->willReturn(true);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'display', 'NewDisplayName')->getData());
	}

	public function testEditUserRegularUserSelfEditChangeEmailValid() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$targetUser
			->expects($this->once())
			->method('setEMailAddress')
			->with('demo@nextcloud.com');
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'email', 'demo@nextcloud.com')->getData());
	}

	public function testEditUserRegularUserSelfEditAddAdditionalEmailValid(): void {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$userAccount = $this->createMock(IAccount::class);

		$this->accountManager
			->expects($this->once())
			->method('getAccount')
			->with($targetUser)
			->willReturn($userAccount);
		$this->accountManager
			->expects($this->once())
			->method('updateAccount')
			->with($userAccount);

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'additional_mail', 'demo1@nextcloud.com')->getData());
	}

	public function testEditUserRegularUserSelfEditAddAdditionalEmailMainAddress(): void {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);
		$targetUser
			->expects($this->any())
			->method('getSystemEMailAddress')
			->willReturn('demo@nextcloud.com');

		$userAccount = $this->createMock(IAccount::class);

		$this->accountManager
			->expects($this->never())
			->method('getAccount')
			->with($targetUser)
			->willReturn($userAccount);
		$this->accountManager
			->expects($this->never())
			->method('updateAccount')
			->with($userAccount);

		$this->expectException(OCSException::class);
		$this->expectExceptionCode(102);
		$this->api->editUser('UserToEdit', 'additional_mail', 'demo@nextcloud.com')->getData();
	}

	public function testEditUserRegularUserSelfEditAddAdditionalEmailDuplicate(): void {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$property = $this->createMock(IAccountProperty::class);
		$property->method('getValue')
			->willReturn('demo1@nextcloud.com');
		$collection = $this->createMock(IAccountPropertyCollection::class);
		$collection->method('getPropertyByValue')
			->with('demo1@nextcloud.com')
			->willReturn($property);

		$userAccount = $this->createMock(IAccount::class);
		$userAccount->method('getPropertyCollection')
			->with(IAccountManager::COLLECTION_EMAIL)
			->willReturn($collection);

		$this->accountManager
			->expects($this->once())
			->method('getAccount')
			->with($targetUser)
			->willReturn($userAccount);
		$this->accountManager
			->expects($this->never())
			->method('updateAccount')
			->with($userAccount);

		$this->expectException(OCSException::class);
		$this->expectExceptionCode(102);
		$this->api->editUser('UserToEdit', 'additional_mail', 'demo1@nextcloud.com')->getData();
	}

	public function testEditUserRegularUserSelfEditChangeEmailInvalid() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(102);

		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$this->api->editUser('UserToEdit', 'email', 'demo.org');
	}

	public function selfEditChangePropertyProvider() {
		return [
			[IAccountManager::PROPERTY_TWITTER, '@oldtwitter', '@newtwitter'],
			[IAccountManager::PROPERTY_FEDIVERSE, '@oldFediverse@floss.social', '@newFediverse@floss.social'],
			[IAccountManager::PROPERTY_PHONE, '1234', '12345'],
			[IAccountManager::PROPERTY_ADDRESS, 'Something street 2', 'Another street 3'],
			[IAccountManager::PROPERTY_WEBSITE, 'https://examplesite1', 'https://examplesite2'],
			[IAccountManager::PROPERTY_ORGANISATION, 'Organisation A', 'Organisation B'],
			[IAccountManager::PROPERTY_ROLE, 'Human', 'Alien'],
			[IAccountManager::PROPERTY_HEADLINE, 'Hi', 'Hello'],
			[IAccountManager::PROPERTY_BIOGRAPHY, 'A biography', 'Another biography'],
			[IAccountManager::PROPERTY_PROFILE_ENABLED, '1', '0'],
		];
	}

	/**
	 * @dataProvider selfEditChangePropertyProvider
	 */
	public function testEditUserRegularUserSelfEditChangeProperty($propertyName, $oldValue, $newValue) {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($loggedInUser);

		$backend = $this->createMock(UserInterface::class);
		$loggedInUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$propertyMock = $this->createMock(IAccountProperty::class);
		$propertyMock->expects($this->any())
			->method('getName')
			->willReturn($propertyName);
		$propertyMock->expects($this->any())
			->method('getValue')
			->willReturn($oldValue);
		$propertyMock->expects($this->once())
			->method('setValue')
			->with($newValue)
			->willReturnSelf();
		$propertyMock->expects($this->any())
			->method('getScope')
			->willReturn(IAccountManager::SCOPE_LOCAL);

		$accountMock = $this->createMock(IAccount::class);
		$accountMock->expects($this->any())
			->method('getProperty')
			->with($propertyName)
			->willReturn($propertyMock);

		$this->accountManager->expects($this->atLeastOnce())
			->method('getAccount')
			->with($loggedInUser)
			->willReturn($accountMock);
		$this->accountManager->expects($this->once())
			->method('updateAccount')
			->with($accountMock);

		$this->assertEquals([], $this->api->editUser('UserToEdit', $propertyName, $newValue)->getData());
	}

	public function selfEditChangePropertyScopeProvider() {
		return [
			[IAccountManager::PROPERTY_AVATAR, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_DISPLAYNAME, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_EMAIL, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_TWITTER, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_FEDIVERSE, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_PHONE, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_ADDRESS, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_WEBSITE, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_ORGANISATION, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_ROLE, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_HEADLINE, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_BIOGRAPHY, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
			[IAccountManager::PROPERTY_PROFILE_ENABLED, IAccountManager::SCOPE_LOCAL, IAccountManager::SCOPE_FEDERATED],
		];
	}

	/**
	 * @dataProvider selfEditChangePropertyProvider
	 */
	public function testEditUserRegularUserSelfEditChangePropertyScope($propertyName, $oldScope, $newScope) {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($loggedInUser);

		$backend = $this->createMock(UserInterface::class);
		$loggedInUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$propertyMock = $this->createMock(IAccountProperty::class);
		$propertyMock->expects($this->any())
			->method('getName')
			->willReturn($propertyName);
		$propertyMock->expects($this->any())
			->method('getValue')
			->willReturn('somevalue');
		$propertyMock->expects($this->any())
			->method('getScope')
			->willReturn($oldScope);
		$propertyMock->expects($this->atLeastOnce())
			->method('setScope')
			->with($newScope)
			->willReturnSelf();

		$accountMock = $this->createMock(IAccount::class);
		$accountMock->expects($this->any())
			->method('getProperty')
			->with($propertyName)
			->willReturn($propertyMock);

		$this->accountManager->expects($this->atLeastOnce())
			->method('getAccount')
			->with($loggedInUser)
			->willReturn($accountMock);
		$this->accountManager->expects($this->once())
			->method('updateAccount')
			->with($accountMock);

		$this->assertEquals([], $this->api->editUser('UserToEdit', $propertyName . 'Scope', $newScope)->getData());
	}

	public function testEditUserRegularUserSelfEditChangePassword() {
		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$targetUser
			->expects($this->once())
			->method('canChangePassword')
			->willReturn(true);
		$targetUser
			->expects($this->once())
			->method('setPassword')
			->with('NewPassword');
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'password', 'NewPassword')->getData());
	}



	public function testEditUserRegularUserSelfEditChangeQuota() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(103);

		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$this->api->editUser('UserToEdit', 'quota', 'NewQuota');
	}

	public function testEditUserAdminUserSelfEditChangeValidQuota() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->willReturnCallback(function ($appid, $key, $default) {
				if ($key === 'max_quota') {
					return '-1';
				}
				return null;
			});
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setQuota')
			->with('2.9 MB');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->exactly(3))
			->method('isAdmin')
			->with('UID')
			->willReturn(true);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'quota', '3042824')->getData());
	}



	public function testEditUserAdminUserSelfEditChangeInvalidQuota() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Invalid quota value ABC');
		$this->expectExceptionCode(102);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->exactly(3))
			->method('isAdmin')
			->with('UID')
			->willReturn(true);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$this->api->editUser('UserToEdit', 'quota', 'ABC');
	}

	public function testEditUserAdminUserEditChangeValidQuota() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->willReturnCallback(function ($appid, $key, $default) {
				if ($key === 'max_quota') {
					return '-1';
				}
				return null;
			});
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('admin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setQuota')
			->with('2.9 MB');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

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
			->willReturn('UserToEdit');
		$targetUser = $this->createMock(IUser::class);
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('UserToEdit', 'core', 'lang', 'de');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->atLeastOnce())
			->method('isAdmin')
			->with('UserToEdit')
			->willReturn(false);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UserToEdit');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

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
	 */
	public function testEditUserSelfEditChangeLanguageButForced($forced) {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);

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
			->willReturn('UserToEdit');
		$targetUser = $this->createMock(IUser::class);
		$this->config->expects($this->never())
			->method('setUserValue');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->atLeastOnce())
			->method('isAdmin')
			->with('UserToEdit')
			->willReturn(false);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UserToEdit');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

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
			->willReturn('admin');
		$targetUser = $this->createMock(IUser::class);
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('UserToEdit', 'core', 'lang', 'de');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);
		$subAdminManager = $this->createMock(SubAdmin::class);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UserToEdit');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'language', 'de')->getData());
	}

	/**
	 * @dataProvider dataEditUserSelfEditChangeLanguageButForced
	 */
	public function testEditUserAdminEditChangeLanguageInvalidLanguage() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);


		$this->l10nFactory->expects($this->once())
			->method('findAvailableLanguages')
			->willReturn(['en', 'de', 'sv']);

		$loggedInUser = $this->createMock(IUser::class);
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('admin');
		$targetUser = $this->createMock(IUser::class);
		$this->config->expects($this->never())
			->method('setUserValue');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);
		$subAdminManager = $this->createMock(SubAdmin::class);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UserToEdit');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'language', 'ru')->getData());
	}

	public function testEditUserSubadminUserAccessible() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->willReturnCallback(function ($appid, $key, $default) {
				if ($key === 'max_quota') {
					return '-1';
				}
				return null;
			});
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser->expects($this->once())
			->method('setQuota')
			->with('2.9 MB');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$backend = $this->createMock(UserInterface::class);
		$targetUser
			->expects($this->any())
			->method('getBackend')
			->willReturn($backend);

		$this->assertEquals([], $this->api->editUser('UserToEdit', 'quota', '3042824')->getData());
	}


	public function testEditUserSubadminUserInaccessible() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(998);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToEdit')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(false);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');

		$this->api->editUser('UserToEdit', 'quota', 'value');
	}


	public function testDeleteUserNotExistingUser() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(998);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UserToEdit');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->willReturn(null);

		$this->api->deleteUser('UserToDelete');
	}


	public function testDeleteUserSelf() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(101);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('UID');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UID');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->willReturn($targetUser);

		$this->api->deleteUser('UserToDelete');
	}

	public function testDeleteSuccessfulUserAsAdmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('admin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UID');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);
		$targetUser
			->expects($this->once())
			->method('delete')
			->willReturn(true);

		$this->assertEquals([], $this->api->deleteUser('UserToDelete')->getData());
	}


	public function testDeleteUnsuccessfulUserAsAdmin() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(101);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('admin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UID');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);
		$targetUser
			->expects($this->once())
			->method('delete')
			->willReturn(false);

		$this->api->deleteUser('UserToDelete');
	}

	public function testDeleteSuccessfulUserAsSubadmin() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UID');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->once())
			->method('delete')
			->willReturn(true);

		$this->assertEquals([], $this->api->deleteUser('UserToDelete')->getData());
	}


	public function testDeleteUnsuccessfulUserAsSubadmin() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(101);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UID');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->once())
			->method('delete')
			->willReturn(false);

		$this->api->deleteUser('UserToDelete');
	}


	public function testDeleteUserAsSubAdminAndUserIsNotAccessible() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(998);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UID');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToDelete')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(false);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);

		$this->api->deleteUser('UserToDelete');
	}


	public function testGetUsersGroupsTargetUserNotExisting() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(998);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);

		$this->api->getUsersGroups('UserToLookup');
	}

	public function testGetUsersGroupsSelfTargetted() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UserToLookup');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UserToLookup');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToLookup')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('getUserGroupIds')
			->with($targetUser)
			->willReturn(['DummyValue']);

		$this->assertEquals(['groups' => ['DummyValue']], $this->api->getUsersGroups('UserToLookup')->getData());
	}

	public function testGetUsersGroupsForAdminUser() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->willReturn('admin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UserToLookup');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToLookup')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('getUserGroupIds')
			->with($targetUser)
			->willReturn(['DummyValue']);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$this->assertEquals(['groups' => ['DummyValue']], $this->api->getUsersGroups('UserToLookup')->getData());
	}

	public function testGetUsersGroupsForSubAdminUserAndUserIsAccessible() {
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UserToLookup');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToLookup')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$group1 = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$group1
			->expects($this->any())
			->method('getGID')
			->willReturn('Group1');
		$group2 = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$group2
			->expects($this->any())
			->method('getGID')
			->willReturn('Group2');
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($loggedInUser)
			->willReturn([$group1, $group2]);
		$this->groupManager
			->expects($this->any())
			->method('getUserGroupIds')
			->with($targetUser)
			->willReturn(['Group1']);

		$this->assertEquals(['groups' => ['Group1']], $this->api->getUsersGroups('UserToLookup')->getData());
	}


	public function testGetUsersGroupsForSubAdminUserAndUserIsInaccessible() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(998);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('UserToLookup');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToLookup')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(false);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->groupManager
			->expects($this->any())
			->method('getUserGroupIds')
			->with($targetUser)
			->willReturn(['Group1']);

		$this->api->getUsersGroups('UserToLookup');
	}


	public function testAddToGroupWithTargetGroupNotExisting() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(102);

		$this->groupManager->expects($this->once())
			->method('get')
			->with('GroupToAddTo')
			->willReturn(null);

		$this->api->addToGroup('TargetUser', 'GroupToAddTo');
	}


	public function testAddToGroupWithNoGroupSpecified() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(101);

		$this->api->addToGroup('TargetUser');
	}


	public function testAddToGroupWithTargetUserNotExisting() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(103);

		$targetGroup = $this->createMock(IGroup::class);
		$this->groupManager->expects($this->once())
			->method('get')
			->with('GroupToAddTo')
			->willReturn($targetGroup);

		$this->api->addToGroup('TargetUser', 'GroupToAddTo');
	}


	public function testAddToGroupNoSubadmin() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(104);

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


	public function testRemoveFromGroupWithNoTargetGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(101);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);

		$this->api->removeFromGroup('TargetUser', '');
	}


	public function testRemoveFromGroupWithEmptyTargetGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(101);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);

		$this->api->removeFromGroup('TargetUser', '');
	}


	public function testRemoveFromGroupWithNotExistingTargetGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(102);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->willReturn(null);

		$this->api->removeFromGroup('TargetUser', 'TargetGroup');
	}


	public function testRemoveFromGroupWithNotExistingTargetUser() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(103);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->willReturn($targetGroup);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('TargetUser')
			->willReturn(null);

		$this->api->removeFromGroup('TargetUser', 'TargetGroup');
	}


	public function testRemoveFromGroupWithoutPermission() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(104);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('unauthorizedUser');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->willReturn($targetGroup);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('TargetUser')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('unauthorizedUser')
			->willReturn(false);

		$this->api->removeFromGroup('TargetUser', 'TargetGroup');
	}


	public function testRemoveFromGroupAsAdminFromAdmin() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Cannot remove yourself from the admin group');
		$this->expectExceptionCode(105);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('admin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$targetGroup
			->expects($this->once())
			->method('getGID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('admin')
			->willReturn($targetGroup);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('Admin')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$this->api->removeFromGroup('Admin', 'admin');
	}


	public function testRemoveFromGroupAsSubAdminFromSubAdmin() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Cannot remove yourself from this group as you are a SubAdmin');
		$this->expectExceptionCode(105);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetUser
			->expects($this->once())
			->method('getUID')
			->willReturn('subadmin');
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$targetGroup
			->expects($this->any())
			->method('getGID')
			->willReturn('subadmin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('subadmin')
			->willReturn($targetGroup);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('SubAdmin')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($loggedInUser, $targetGroup)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);

		$this->api->removeFromGroup('SubAdmin', 'subadmin');
	}


	public function testRemoveFromGroupAsSubAdminFromLastSubAdminGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Not viable to remove user from the last group you are SubAdmin of');
		$this->expectExceptionCode(105);

		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->any())
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$targetGroup
			->expects($this->any())
			->method('getGID')
			->willReturn('subadmin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('subadmin')
			->willReturn($targetGroup);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('AnotherUser')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($loggedInUser, $targetGroup)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($loggedInUser)
			->willReturn([$targetGroup]);

		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);
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
			->willReturn('admin');
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('admin')
			->willReturn($targetGroup);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('AnotherUser')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);
		$targetGroup
			->expects($this->once())
			->method('removeUser')
			->with($targetUser);

		$this->assertEquals([], $this->api->removeFromGroup('AnotherUser', 'admin')->getData());
	}


	public function testAddSubAdminWithNotExistingTargetUser() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('User does not exist');
		$this->expectExceptionCode(101);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->willReturn(null);

		$this->api->addSubAdmin('NotExistingUser', '');
	}


	public function testAddSubAdminWithNotExistingTargetGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Group does not exist');
		$this->expectExceptionCode(102);


		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('NotExistingGroup')
			->willReturn(null);

		$this->api->addSubAdmin('ExistingUser', 'NotExistingGroup');
	}


	public function testAddSubAdminToAdminGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Cannot create subadmins for admin group');
		$this->expectExceptionCode(103);

		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$targetGroup
			->expects($this->once())
			->method('getGID')
			->willReturn('admin');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('ADmiN')
			->willReturn($targetGroup);

		$this->api->addSubAdmin('ExistingUser', 'ADmiN');
	}

	public function testAddSubAdminTwice() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->willReturn($targetGroup);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);

		$this->assertEquals([], $this->api->addSubAdmin('ExistingUser', 'TargetGroup')->getData());
	}

	public function testAddSubAdminSuccessful() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('TargetGroup')
			->willReturn($targetGroup);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->willReturn(false);
		$subAdminManager
			->expects($this->once())
			->method('createSubAdmin')
			->with($targetUser, $targetGroup);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);

		$this->assertEquals([], $this->api->addSubAdmin('ExistingUser', 'TargetGroup')->getData());
	}


	public function testRemoveSubAdminNotExistingTargetUser() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('User does not exist');
		$this->expectExceptionCode(101);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->willReturn(null);

		$this->api->removeSubAdmin('NotExistingUser', 'GroupToDeleteFrom');
	}


	public function testRemoveSubAdminNotExistingTargetGroup() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Group does not exist');
		$this->expectExceptionCode(101);

		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('GroupToDeleteFrom')
			->willReturn(null);

		$this->api->removeSubAdmin('ExistingUser', 'GroupToDeleteFrom');
	}



	public function testRemoveSubAdminFromNotASubadmin() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('User is not a subadmin of this group');
		$this->expectExceptionCode(102);

		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('GroupToDeleteFrom')
			->willReturn($targetGroup);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->willReturn(false);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);

		$this->api->removeSubAdmin('ExistingUser', 'GroupToDeleteFrom');
	}

	public function testRemoveSubAdminSuccessful() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('GroupToDeleteFrom')
			->willReturn($targetGroup);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isSubAdminOfGroup')
			->with($targetUser, $targetGroup)
			->willReturn(true);
		$subAdminManager
			->expects($this->once())
			->method('deleteSubAdmin')
			->with($targetUser, $targetGroup);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);

		$this->assertEquals([], $this->api->removeSubAdmin('ExistingUser', 'GroupToDeleteFrom')->getData());
	}


	public function testGetUserSubAdminGroupsNotExistingTargetUser() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('User does not exist');
		$this->expectExceptionCode(404);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->willReturn(null);

		$this->api->getUserSubAdminGroups('RequestedUser');
	}

	public function testGetUserSubAdminGroupsWithGroups() {
		$targetUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$targetGroup = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$targetGroup
			->expects($this->once())
			->method('getGID')
			->willReturn('TargetGroup');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('RequestedUser')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()->getMock();
		$subAdminManager
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($targetUser)
			->willReturn([$targetGroup]);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);

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
			->willReturn($targetUser);
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->willReturn(true);

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
			->willReturn($targetUser);
		$loggedInUser = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$loggedInUser
			->expects($this->exactly(2))
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->willReturn(true);

		$this->assertEquals([], $this->api->disableUser('RequestedUser')->getData());
	}

	public function testGetCurrentUserLoggedIn() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('UID');

		$this->userSession->expects($this->once())->method('getUser')
			->willReturn($user);

		/** @var UsersController | MockObject $api */
		$api = $this->getMockBuilder(UsersController::class)
			->setConstructorArgs([
				'provisioning_api',
				$this->request,
				$this->userManager,
				$this->config,
				$this->groupManager,
				$this->userSession,
				$this->accountManager,
				$this->urlGenerator,
				$this->logger,
				$this->l10nFactory,
				$this->newUserMailHelper,
				$this->secureRandom,
				$this->remoteWipe,
				$this->knownUserService,
				$this->eventDispatcher,
				$this->phoneNumberUtil,
			])
			->onlyMethods(['getUserData'])
			->getMock();

		$api->expects($this->once())->method('getUserData')->with('UID', true)
			->willReturn(
				[
					'id' => 'UID',
					'enabled' => 'true',
					'quota' => ['DummyValue'],
					'email' => 'demo@nextcloud.com',
					'displayname' => 'Demo User',
					'display-name' => 'Demo User',
					'phone' => 'phone',
					'address' => 'address',
					'website' => 'website',
					'twitter' => 'twitter',
					'fediverse' => 'fediverse',
					'organisation' => 'organisation',
					'role' => 'role',
					'headline' => 'headline',
					'biography' => 'biography',
					'profile_enabled' => '1'
				]
			);

		$expected = [
			'id' => 'UID',
			'enabled' => 'true',
			'quota' => ['DummyValue'],
			'email' => 'demo@nextcloud.com',
			'displayname' => 'Demo User',
			'display-name' => 'Demo User',
			'phone' => 'phone',
			'address' => 'address',
			'website' => 'website',
			'twitter' => 'twitter',
			'fediverse' => 'fediverse',
			'organisation' => 'organisation',
			'role' => 'role',
			'headline' => 'headline',
			'biography' => 'biography',
			'profile_enabled' => '1',
		];

		$this->assertSame($expected, $api->getCurrentUser()->getData());
	}


	public function testGetCurrentUserNotLoggedIn() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);


		$this->userSession->expects($this->once())->method('getUser')
			->willReturn(null);

		$this->api->getCurrentUser();
	}

	public function testGetUser() {
		$loggedInUser = $this->createMock(IUser::class);
		$loggedInUser
			->method('getUID')
			->willReturn('currentuser');
		$this->userSession
			->method('getUser')
			->willReturn($loggedInUser);

		/** @var UsersController | MockObject $api */
		$api = $this->getMockBuilder(UsersController::class)
			->setConstructorArgs([
				'provisioning_api',
				$this->request,
				$this->userManager,
				$this->config,
				$this->groupManager,
				$this->userSession,
				$this->accountManager,
				$this->urlGenerator,
				$this->logger,
				$this->l10nFactory,
				$this->newUserMailHelper,
				$this->secureRandom,
				$this->remoteWipe,
				$this->knownUserService,
				$this->eventDispatcher,
				$this->phoneNumberUtil,
			])
			->onlyMethods(['getUserData'])
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
			'fediverse' => 'fediverse',
			'displayname' => 'Demo User',
			'display-name' => 'Demo User',
			'organisation' => 'organisation',
			'role' => 'role',
			'headline' => 'headline',
			'biography' => 'biography',
			'profile_enabled' => '1'
		];

		$api->expects($this->exactly(2))
			->method('getUserData')
			->withConsecutive(
				['uid', false],
				['currentuser', true],
			)
			->willReturn($expected);

		$this->assertSame($expected, $api->getUser('uid')->getData());

		$this->assertSame($expected, $api->getUser('currentuser')->getData());
	}


	public function testResendWelcomeMessageWithNotExistingTargetUser() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(998);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->willReturn(null);

		$this->api->resendWelcomeMessage('NotExistingUser');
	}


	public function testResendWelcomeMessageAsSubAdminAndUserIsNotAccessible() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionCode(998);

		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$loggedInUser
			->expects($this->exactly(1))
			->method('getUID')
			->willReturn('subadmin');
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->willReturn($targetUser);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('subadmin')
			->willReturn(false);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(false);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);

		$this->api->resendWelcomeMessage('UserToGet');
	}


	public function testResendWelcomeMessageNoEmail() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Email address not available');
		$this->expectExceptionCode(101);

		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->willReturn('');

		$this->api->resendWelcomeMessage('UserToGet');
	}


	public function testResendWelcomeMessageNullEmail() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Email address not available');
		$this->expectExceptionCode(101);

		$loggedInUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$targetUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->willReturn(null);

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
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->willReturn('abc@example.org');
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->newUserMailHelper
			->expects($this->once())
			->method('generateTemplate')
			->willReturn($emailTemplate);
		$this->newUserMailHelper
			->expects($this->once())
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
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->willReturn('abc@example.org');
		$l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->newUserMailHelper
			->expects($this->once())
			->method('generateTemplate')
			->willReturn($emailTemplate);
		$this->newUserMailHelper
			->expects($this->once())
			->method('sendMail')
			->with($targetUser, $emailTemplate);

		$this->api->resendWelcomeMessage('UserToGet');
	}


	public function testResendWelcomeMessageFailed() {
		$this->expectException(\OCP\AppFramework\OCS\OCSException::class);
		$this->expectExceptionMessage('Sending email failed');
		$this->expectExceptionCode(102);

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
			->willReturn($loggedInUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('UserToGet')
			->willReturn($targetUser);
		$subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subAdminManager
			->expects($this->once())
			->method('isUserAccessible')
			->with($loggedInUser, $targetUser)
			->willReturn(true);
		$this->groupManager
			->expects($this->once())
			->method('getSubAdmin')
			->willReturn($subAdminManager);
		$targetUser
			->expects($this->once())
			->method('getEmailAddress')
			->willReturn('abc@example.org');
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->newUserMailHelper
			->expects($this->once())
			->method('generateTemplate')
			->willReturn($emailTemplate);
		$this->newUserMailHelper
			->expects($this->once())
			->method('sendMail')
			->with($targetUser, $emailTemplate)
			->willThrowException(new \Exception());

		$this->api->resendWelcomeMessage('UserToGet');
	}


	public function dataGetEditableFields() {
		return [
			[false, ISetDisplayNameBackend::class, [
				IAccountManager::COLLECTION_EMAIL,
				IAccountManager::PROPERTY_PHONE,
				IAccountManager::PROPERTY_ADDRESS,
				IAccountManager::PROPERTY_WEBSITE,
				IAccountManager::PROPERTY_TWITTER,
				IAccountManager::PROPERTY_FEDIVERSE,
				IAccountManager::PROPERTY_ORGANISATION,
				IAccountManager::PROPERTY_ROLE,
				IAccountManager::PROPERTY_HEADLINE,
				IAccountManager::PROPERTY_BIOGRAPHY,
				IAccountManager::PROPERTY_PROFILE_ENABLED,
			]],
			[true, ISetDisplayNameBackend::class, [
				IAccountManager::PROPERTY_DISPLAYNAME,
				IAccountManager::PROPERTY_EMAIL,
				IAccountManager::COLLECTION_EMAIL,
				IAccountManager::PROPERTY_PHONE,
				IAccountManager::PROPERTY_ADDRESS,
				IAccountManager::PROPERTY_WEBSITE,
				IAccountManager::PROPERTY_TWITTER,
				IAccountManager::PROPERTY_FEDIVERSE,
				IAccountManager::PROPERTY_ORGANISATION,
				IAccountManager::PROPERTY_ROLE,
				IAccountManager::PROPERTY_HEADLINE,
				IAccountManager::PROPERTY_BIOGRAPHY,
				IAccountManager::PROPERTY_PROFILE_ENABLED,
			]],
			[true, UserInterface::class, [
				IAccountManager::PROPERTY_EMAIL,
				IAccountManager::COLLECTION_EMAIL,
				IAccountManager::PROPERTY_PHONE,
				IAccountManager::PROPERTY_ADDRESS,
				IAccountManager::PROPERTY_WEBSITE,
				IAccountManager::PROPERTY_TWITTER,
				IAccountManager::PROPERTY_FEDIVERSE,
				IAccountManager::PROPERTY_ORGANISATION,
				IAccountManager::PROPERTY_ROLE,
				IAccountManager::PROPERTY_HEADLINE,
				IAccountManager::PROPERTY_BIOGRAPHY,
				IAccountManager::PROPERTY_PROFILE_ENABLED,
			]],
		];
	}

	/**
	 * @dataProvider dataGetEditableFields
	 *
	 * @param bool $allowedToChangeDisplayName
	 * @param string $userBackend
	 * @param array $expected
	 */
	public function testGetEditableFields(bool $allowedToChangeDisplayName, string $userBackend, array $expected) {
		$this->config
			->method('getSystemValue')
			->with(
				$this->equalTo('allow_user_to_change_display_name'),
				$this->anything()
			)->willReturn($allowedToChangeDisplayName);

		$user = $this->createMock(IUser::class);
		$this->userSession->method('getUser')
			->willReturn($user);

		$backend = $this->createMock($userBackend);

		$user->method('getUID')
			->willReturn('userId');
		$user->method('getBackend')
			->willReturn($backend);

		$expectedResp = new DataResponse($expected);
		$this->assertEquals($expectedResp, $this->api->getEditableFields('userId'));
	}

	private function mockAccount($targetUser, $accountProperties) {
		$mockedProperties = [];

		foreach ($accountProperties as $propertyName => $data) {
			$mockedProperty = $this->createMock(IAccountProperty::class);
			$mockedProperty->method('getValue')->willReturn($data['value'] ?? '');
			$mockedProperty->method('getScope')->willReturn($data['scope'] ?? '');
			$mockedProperties[] = [$propertyName, $mockedProperty];
		}

		$account = $this->createMock(IAccount::class);
		$account->method('getProperty')
			->will($this->returnValueMap($mockedProperties));

		$this->accountManager->expects($this->any())->method('getAccount')
			->with($targetUser)
			->willReturn($account);
	}
}
