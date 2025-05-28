<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests;

use OC\User\Backend;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\Mapping\AbstractMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\OfflineUser;
use OCA\User_LDAP\User\User;
use OCA\User_LDAP\User_LDAP;
use OCA\User_LDAP\User_LDAP as UserLDAP;
use OCA\User_LDAP\UserPluginManager;
use OCP\HintException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class Test_User_Ldap_Direct
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class User_LDAPTest extends TestCase {
	protected Access&MockObject $access;
	protected OfflineUser&MockObject $offlineUser;
	protected INotificationManager&MockObject $notificationManager;
	protected UserPluginManager&MockObject $pluginManager;
	protected Connection&MockObject $connection;
	protected Manager&MockObject $userManager;
	protected LoggerInterface&MockObject $logger;
	protected DeletedUsersIndex&MockObject $deletedUsersIndex;
	protected User_LDAP $backend;

	protected function setUp(): void {
		parent::setUp();

		Server::get(IUserManager::class)->clearBackends();
		Server::get(IGroupManager::class)->clearBackends();

		$this->connection = $this->getMockBuilder(Connection::class)
			->setConstructorArgs([$this->createMock(ILDAPWrapper::class)])
			->getMock();
		$this->userManager = $this->createMock(Manager::class);

		$this->access = $this->createMock(Access::class);
		$this->access->connection = $this->connection;
		$this->access->userManager = $this->userManager;

		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->pluginManager = $this->createMock(UserPluginManager::class);

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->deletedUsersIndex = $this->createMock(DeletedUsersIndex::class);

		$this->backend = new User_LDAP(
			$this->access,
			$this->notificationManager,
			$this->pluginManager,
			$this->logger,
			$this->deletedUsersIndex,
		);
	}

	private function prepareMockForUserExists(): void {
		$this->access->expects($this->any())
			->method('username2dn')
			->willReturnCallback(function ($uid) {
				switch ($uid) {
					case 'gunslinger':
						return 'dnOfRoland,dc=test';
						break;
					case 'formerUser':
						return 'dnOfFormerUser,dc=test';
						break;
					case 'newyorker':
						return 'dnOfNewYorker,dc=test';
						break;
					case 'ladyofshadows':
						return 'dnOfLadyOfShadows,dc=test';
						break;
					default:
						return false;
				}
			});

		$this->access->method('fetchUsersByLoginName')
			->willReturn([]);
	}

	/**
	 * Prepares the Access mock for checkPassword tests
	 */
	private function prepareAccessForCheckPassword(bool $noDisplayName = false): void {
		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapLoginFilter') {
					return '%uid';
				}
				return null;
			});

		$this->access->expects($this->any())
			->method('fetchListOfUsers')
			->willReturnCallback(function ($filter) {
				if ($filter === 'roland') {
					return [['dn' => ['dnOfRoland,dc=test']]];
				}
				return [];
			});
		$this->access->expects($this->any())
			->method('fetchUsersByLoginName')
			->willReturnCallback(function ($uid) {
				if ($uid === 'roland') {
					return [['dn' => ['dnOfRoland,dc=test']]];
				}
				return [];
			});

		$retVal = 'gunslinger';
		if ($noDisplayName === true) {
			$retVal = false;
		}
		$this->access->expects($this->any())
			->method('dn2username')
			->with($this->equalTo('dnOfRoland,dc=test'))
			->willReturn($retVal);
		$this->access->expects($this->any())
			->method('username2dn')
			->with($this->equalTo('gunslinger'))
			->willReturn('dnOfRoland,dc=test');
		$this->access->expects($this->any())
			->method('stringResemblesDN')
			->with($this->equalTo('dnOfRoland,dc=test'))
			->willReturn(true);
		$this->access->expects($this->any())
			->method('areCredentialsValid')
			->willReturnCallback(function ($dn, $pwd) {
				if ($pwd === 'dt19') {
					return true;
				}
				return false;
			});

		$this->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['dn', 'uid', 'mail', 'displayname']);
	}

	public function testCheckPasswordUidReturn(): void {
		$user = $this->createMock(User::class);
		$user->expects($this->any())
			->method('getUsername')
			->willReturn('gunslinger');

		$this->prepareAccessForCheckPassword();
		$this->userManager->expects($this->any())
			->method('get')
			->willReturn($user);

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		Server::get(IUserManager::class)->registerBackend($backend);

		$result = $backend->checkPassword('roland', 'dt19');
		$this->assertEquals('gunslinger', $result);
	}

	public function testCheckPasswordWrongPassword(): void {
		$this->prepareAccessForCheckPassword();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$result = $backend->checkPassword('roland', 'wrong');
		$this->assertFalse($result);
	}

	public function testCheckPasswordWrongUser(): void {
		$this->prepareAccessForCheckPassword();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$result = $backend->checkPassword('mallory', 'evil');
		$this->assertFalse($result);
	}

	public function testCheckPasswordNoDisplayName(): void {
		$this->prepareAccessForCheckPassword(true);

		$this->prepareAccessForCheckPassword();
		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->willReturn(null);

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$result = $backend->checkPassword('roland', 'dt19');
		$this->assertFalse($result);
	}

	public function testCheckPasswordPublicAPI(): void {
		$user = $this->createMock(User::class);
		$user->expects($this->any())
			->method('getUsername')
			->willReturn('gunslinger');

		$this->prepareAccessForCheckPassword();
		$this->userManager->expects($this->any())
			->method('get')
			->willReturn($user);

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$user = Server::get(IUserManager::class)->checkPassword('roland', 'dt19');
		$result = false;
		if ($user !== false) {
			$result = $user->getUID();
		}
		$this->assertEquals('gunslinger', $result);
	}

	public function testCheckPasswordPublicAPIWrongPassword(): void {
		$this->prepareAccessForCheckPassword();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$user = Server::get(IUserManager::class)->checkPassword('roland', 'wrong');
		$result = false;
		if ($user !== false) {
			$result = $user->getUID();
		}
		$this->assertFalse($result);
	}

	public function testCheckPasswordPublicAPIWrongUser(): void {
		$this->prepareAccessForCheckPassword();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$user = Server::get(IUserManager::class)->checkPassword('mallory', 'evil');
		$result = false;
		if ($user !== false) {
			$result = $user->getUID();
		}
		$this->assertFalse($result);
	}

	public function testDeleteUserCancel(): void {
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$result = $backend->deleteUser('notme');
		$this->assertFalse($result);
	}

	public function testDeleteUserSuccess(): void {
		$uid = 'jeremy';
		$home = '/var/vhome/jdings/';

		$mapping = $this->createMock(UserMapping::class);
		$mapping->expects($this->once())
			->method('unmap')
			->willReturn(true);
		$this->access->expects($this->once())
			->method('getUserMapper')
			->willReturn($mapping);
		$this->connection->expects($this->any())
			->method('getConnectionResource')
			->willReturn(ldap_connect('ldap://example.com'));

		$this->deletedUsersIndex->expects($this->once())
			->method('isUserMarked')
			->with($uid)
			->willReturn(true);

		$offlineUser = $this->createMock(OfflineUser::class);
		$offlineUser->expects($this->once())
			->method('getHomePath')
			->willReturn($home);
		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->willReturn($offlineUser);
		$this->userManager->expects($this->once())
			->method('exists')
			->with($uid)
			->willReturn(true);

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		$result = $backend->deleteUser($uid);
		$this->assertTrue($result);
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertSame($backend->getHome($uid), $home);
	}

	public function testDeleteUserWithPlugin(): void {
		$this->pluginManager->expects($this->once())
			->method('canDeleteUser')
			->willReturn(true);
		$this->pluginManager->expects($this->once())
			->method('deleteUser')
			->with('uid')
			->willReturn(true);

		$this->deletedUsersIndex->expects($this->once())
			->method('isUserMarked')
			->with('uid')
			->willReturn(true);

		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->once())
			->method('unmap')
			->with('uid');

		$this->access->expects($this->atLeastOnce())
			->method('getUserMapper')
			->willReturn($mapper);

		$this->userManager->expects($this->once())
			->method('invalidate')
			->with('uid');

		$this->assertTrue($this->backend->deleteUser('uid'));
	}

	/**
	 * Prepares the Access mock for getUsers tests
	 */
	private function prepareAccessForGetUsers() {
		$this->access->expects($this->once())
			->method('escapeFilterPart')
			->willReturnCallback(function ($search) {
				return $search;
			});
		$this->access->expects($this->any())
			->method('getFilterPartForUserSearch')
			->willReturnCallback(function ($search) {
				return $search;
			});
		$this->access->expects($this->any())
			->method('combineFilterWithAnd')
			->willReturnCallback(function ($param) {
				return $param[2];
			});
		$this->access->expects($this->any())
			->method('fetchListOfUsers')
			->willReturnCallback(function ($search, $a, $l, $o) {
				$users = ['gunslinger', 'newyorker', 'ladyofshadows'];
				if (empty($search)) {
					$result = $users;
				} else {
					$result = [];
					foreach ($users as $user) {
						if (stripos($user, $search) !== false) {
							$result[] = $user;
						}
					}
				}
				if (!is_null($l) || !is_null($o)) {
					$result = array_slice($result, $o, $l);
				}
				return $result;
			});
		$this->access->expects($this->any())
			->method('nextcloudUserNames')
			->willReturnArgument(0);
		$this->access->method('fetchUsersByLoginName')
			->willReturn([]);

		$this->access->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['dn', 'uid', 'mail', 'displayname']);
	}

	public function testGetUsersNoParam(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		$result = $backend->getUsers();
		$this->assertCount(3, $result);
	}

	public function testGetUsersLimitOffset(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		$result = $backend->getUsers('', 1, 2);
		$this->assertCount(1, $result);
	}

	public function testGetUsersLimitOffset2(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		$result = $backend->getUsers('', 2, 1);
		$this->assertCount(2, $result);
	}

	public function testGetUsersSearchWithResult(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		$result = $backend->getUsers('yo');
		$this->assertCount(2, $result);
	}

	public function testGetUsersSearchEmptyResult(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		$result = $backend->getUsers('nix');
		$this->assertCount(0, $result);
	}

	private function getUsers($search = '', $limit = null, $offset = null) {
		$users = Server::get(IUserManager::class)->search($search, $limit, $offset);
		$uids = array_map(function (IUser $user) {
			return $user->getUID();
		}, $users);
		return $uids;
	}

	public function testGetUsersViaAPINoParam(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$result = $this->getUsers();
		$this->assertCount(3, $result);
	}

	public function testGetUsersViaAPILimitOffset(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$result = $this->getUsers('', 1, 2);
		$this->assertCount(1, $result);
	}

	public function testGetUsersViaAPILimitOffset2(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$result = $this->getUsers('', 2, 1);
		$this->assertCount(2, $result);
	}

	public function testGetUsersViaAPISearchWithResult(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$result = $this->getUsers('yo');
		$this->assertCount(2, $result);
	}

	public function testGetUsersViaAPISearchEmptyResult(): void {
		$this->prepareAccessForGetUsers();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$result = $this->getUsers('nix');
		$this->assertCount(0, $result);
	}

	public function testUserExists(): void {
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();

		$this->userManager->expects($this->never())
			->method('get');
		$this->userManager->expects($this->once())
			->method('exists')
			->with('gunslinger')
			->willReturn(true);
		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($this->createMock(UserMapping::class));

		//test for existing user
		/** @noinspection PhpUnhandledExceptionInspection */
		$result = $backend->userExists('gunslinger');
		$this->assertTrue($result);
	}

	public function testUserExistsForDeleted(): void {
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();

		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->any())
			->method('getUUIDByDN')
			->with('dnOfFormerUser,dc=test')
			->willReturn('45673458748');

		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($mapper);

		$this->userManager->expects($this->never())
			->method('get');
		$this->userManager->expects($this->once())
			->method('exists')
			->with('formerUser')
			->willReturn(true);

		//test for deleted user – always returns true as long as we have the user in DB
		$this->assertTrue($backend->userExists('formerUser'));
	}

	public function testUserExistsForNeverExisting(): void {
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();

		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn) {
				if ($dn === 'dnOfRoland,dc=test') {
					return [];
				}
				return false;
			});

		//test for never-existing user
		/** @noinspection PhpUnhandledExceptionInspection */
		$result = $backend->userExists('mallory');
		$this->assertFalse($result);
	}

	public function testUserExistsPublicAPI(): void {
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();
		Server::get(IUserManager::class)->registerBackend($backend);

		$user = $this->createMock(User::class);
		$user->expects($this->any())
			->method('getDN')
			->willReturn('dnOfRoland,dc=test');

		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn) {
				if ($dn === 'dnOfRoland,dc=test') {
					return [];
				}
				return false;
			});
		$this->userManager->expects($this->never())
			->method('get');
		$this->userManager->expects($this->once())
			->method('exists')
			->with('gunslinger')
			->willReturn(true);
		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($this->createMock(UserMapping::class));

		//test for existing user
		$result = Server::get(IUserManager::class)->userExists('gunslinger');
		$this->assertTrue($result);
	}

	public function testDeleteUserExisting(): void {
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		//we do not support deleting existing users at all
		$result = $backend->deleteUser('gunslinger');
		$this->assertFalse($result);
	}

	public function testGetHomeAbsolutePath(): void {
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();

		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'homeFolderNamingRule') {
					return 'attr:testAttribute';
				}
				return null;
			});

		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn, $attr) {
				switch ($dn) {
					case 'dnOfRoland,dc=test':
						if ($attr === 'testAttribute') {
							return ['/tmp/rolandshome/'];
						}
						return [];
						break;
					default:
						return false;
				}
			});

		$user = $this->createMock(User::class);
		$user->expects($this->any())
			->method('getUsername')
			->willReturn('gunslinger');
		$user->expects($this->any())
			->method('getDN')
			->willReturn('dnOfRoland,dc=test');
		$user->expects($this->any())
			->method('getHomePath')
			->willReturn('/tmp/rolandshome/');

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with('gunslinger')
			->willReturn($user);
		$this->userManager->expects($this->once())
			->method('exists')
			->with('gunslinger')
			->willReturn(true);

		//absolute path
		/** @noinspection PhpUnhandledExceptionInspection */
		$result = $backend->getHome('gunslinger');
		$this->assertEquals('/tmp/rolandshome/', $result);
	}

	public function testGetHomeRelative(): void {
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();

		$dataDir = Server::get(IConfig::class)->getSystemValue(
			'datadirectory', \OC::$SERVERROOT . '/data');

		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'homeFolderNamingRule') {
					return 'attr:testAttribute';
				}
				return null;
			});

		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn, $attr) {
				switch ($dn) {
					case 'dnOfLadyOfShadows,dc=test':
						if ($attr === 'testAttribute') {
							return ['susannah/'];
						}
						return [];
						break;
					default:
						return false;
				}
			});

		$user = $this->createMock(User::class);
		$user->expects($this->any())
			->method('getUsername')
			->willReturn('ladyofshadows');
		$user->expects($this->any())
			->method('getDN')
			->willReturn('dnOfLadyOfShadows,dc=test');
		$user->expects($this->any())
			->method('getHomePath')
			->willReturn($dataDir . '/susannah/');

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->willReturn($user);
		$this->userManager->expects($this->once())
			->method('exists')
			->with('ladyofshadows')
			->willReturn(true);

		/** @noinspection PhpUnhandledExceptionInspection */
		$result = $backend->getHome('ladyofshadows');
		$this->assertEquals($dataDir . '/susannah/', $result);
	}


	public function testGetHomeNoPath(): void {
		$this->expectException(\Exception::class);

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();

		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'homeFolderNamingRule') {
					return 'attr:testAttribute';
				}
				return null;
			});
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn, $attr) {
				switch ($dn) {
					default:
						return false;
				}
			});

		$user = $this->createMock(User::class);
		$user->expects($this->any())
			->method('getUsername')
			->willReturn('newyorker');
		$user->expects($this->any())
			->method('getHomePath')
			->willThrowException(new \Exception());

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with('newyorker')
			->willReturn($user);
		$this->userManager->expects($this->once())
			->method('exists')
			->with('newyorker')
			->willReturn(true);

		//no path at all – triggers OC default behaviour
		$result = $backend->getHome('newyorker');
		$this->assertFalse($result);
	}

	public function testGetHomeDeletedUser(): void {
		$uid = 'newyorker';

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();

		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'homeFolderNamingRule') {
					return 'attr:testAttribute';
				}
				return null;
			});

		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturn([]);

		$userMapper = $this->createMock(UserMapping::class);

		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($userMapper);

		$offlineUser = $this->createMock(OfflineUser::class);
		$offlineUser->expects($this->atLeastOnce())
			->method('getHomePath')
			->willReturn('');

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with($uid)
			->willReturn($offlineUser);
		$this->userManager->expects($this->once())
			->method('exists')
			->with($uid)
			->willReturn(true);

		$result = $backend->getHome($uid);
		$this->assertFalse($result);
	}

	public function testGetHomeWithPlugin(): void {
		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::GET_HOME)
			->willReturn(true);
		$this->pluginManager->expects($this->once())
			->method('getHome')
			->with('uid')
			->willReturn('result');

		$this->connection->expects($this->any())
			->method('getFromCache')
			->willReturnCallback(function ($uid) {
				return true;
			});

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals($this->backend->getHome('uid'), 'result');
	}

	private function prepareAccessForGetDisplayName() {
		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapUserDisplayName') {
					return 'displayname';
				} elseif ($name === 'ldapUserDisplayName2') {
					return 'displayname2';
				}
				return null;
			});

		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn, $attr) {
				switch ($dn) {
					case 'dnOfRoland,dc=test':
						if ($attr === 'displayname') {
							return ['Roland Deschain'];
						}
						return [];
						break;

					default:
						return false;
				}
			});
		$this->access->method('fetchUsersByLoginName')
			->willReturn([]);
	}

	public function testGetDisplayName(): void {
		$this->prepareAccessForGetDisplayName();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();

		$this->connection->expects($this->any())
			->method('getConnectionResource')
			->willReturnCallback(function () {
				return true;
			});

		$user1 = $this->createMock(User::class);
		$user1->expects($this->once())
			->method('composeAndStoreDisplayName')
			->willReturn('Roland Deschain');
		$user1->expects($this->any())
			->method('getDN')
			->willReturn('dnOfRoland,dc=test');

		$user2 = $this->createMock(User::class);
		$user2->expects($this->never())
			->method('composeAndStoreDisplayName');
		$user2->expects($this->any())
			->method('getDN')
			->willReturn('another DN');

		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->any())
			->method('getUUIDByDN')
			->willReturnCallback(function ($dn) {
				return $dn;
			});

		$this->userManager->expects($this->any())
			->method('get')
			->willReturnCallback(function ($uid) use ($user1, $user2) {
				if ($uid === 'gunslinger') {
					return $user1;
				} elseif ($uid === 'newyorker') {
					return $user2;
				}
				return null;
			});
		$this->userManager->expects($this->any())
			->method('exists')
			->willReturnCallback(function ($uid) use ($user1, $user2) {
				if ($uid === 'gunslinger') {
					return true;
				} elseif ($uid === 'newyorker') {
					return true;
				}
				return false;
			});
		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($mapper);
		$this->access->expects($this->any())
			->method('getUserDnByUuid')
			->willReturnCallback(function ($uuid) {
				return $uuid . '1';
			});

		//with displayName
		$result = $backend->getDisplayName('gunslinger');
		$this->assertEquals('Roland Deschain', $result);

		//empty displayname retrieved
		$result = $backend->getDisplayName('newyorker');
		$this->assertEquals(null, $result);
	}

	public function testGetDisplayNamePublicAPI(): void {
		$this->access->expects($this->any())
			->method('username2dn')
			->willReturnCallback(function ($uid) {
				switch ($uid) {
					case 'gunslinger':
						return 'dnOfRoland,dc=test';
						break;
					case 'formerUser':
						return 'dnOfFormerUser,dc=test';
						break;
					case 'newyorker':
						return 'dnOfNewYorker,dc=test';
						break;
					case 'ladyofshadows':
						return 'dnOfLadyOfShadows,dc=test';
						break;
					default:
						return false;
				}
			});
		$this->prepareAccessForGetDisplayName();
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->prepareMockForUserExists();

		$this->connection->expects($this->any())
			->method('getConnectionResource')
			->willReturnCallback(function () {
				return true;
			});

		Server::get(IUserManager::class)->registerBackend($backend);

		$user1 = $this->createMock(User::class);
		$user1->expects($this->once())
			->method('composeAndStoreDisplayName')
			->willReturn('Roland Deschain');
		$user1->expects($this->any())
			->method('getDN')
			->willReturn('dnOfRoland,dc=test');

		$user2 = $this->createMock(User::class);
		$user2->expects($this->never())
			->method('composeAndStoreDisplayName');
		$user2->expects($this->any())
			->method('getDN')
			->willReturn('another DN');

		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->any())
			->method('getUUIDByDN')
			->willReturnCallback(function ($dn) {
				return $dn;
			});

		$this->userManager->expects($this->any())
			->method('get')
			->willReturnCallback(function ($uid) use ($user1, $user2) {
				if ($uid === 'gunslinger') {
					return $user1;
				} elseif ($uid === 'newyorker') {
					return $user2;
				}
				return null;
			});
		$this->userManager->expects($this->any())
			->method('exists')
			->willReturnCallback(function ($uid) use ($user1, $user2) {
				if ($uid === 'gunslinger') {
					return true;
				} elseif ($uid === 'newyorker') {
					return true;
				}
				return false;
			});
		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($mapper);
		$this->access->expects($this->any())
			->method('getUserDnByUuid')
			->willReturnCallback(function ($uuid) {
				return $uuid . '1';
			});

		//with displayName
		$result = Server::get(IUserManager::class)->get('gunslinger')?->getDisplayName();
		$this->assertEquals('Roland Deschain', $result);

		//empty displayname retrieved
		$result = Server::get(IUserManager::class)->get('newyorker') === null ? 'newyorker' : Server::get(IUserManager::class)->get('newyorker')->getDisplayName();
		$this->assertEquals('newyorker', $result);
	}

	public function testGetDisplayNameWithPlugin(): void {
		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::GET_DISPLAYNAME)
			->willReturn(true);
		$this->pluginManager->expects($this->once())
			->method('getDisplayName')
			->with('uid')
			->willReturn('result');

		$this->assertEquals($this->backend->getDisplayName('uid'), 'result');
	}

	//no test for getDisplayNames, because it just invokes getUsers and
	//getDisplayName

	public function testCountUsers(): void {
		$this->access->expects($this->once())
			->method('countUsers')
			->willReturn(5);

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		$result = $backend->countUsers();
		$this->assertEquals(5, $result);
	}

	public function testCountUsersFailing(): void {
		$this->access->expects($this->once())
			->method('countUsers')
			->willReturn(false);

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);

		$result = $backend->countUsers();
		$this->assertFalse($result);
	}

	public function testCountUsersWithPlugin(): void {
		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::COUNT_USERS)
			->willReturn(true);
		$this->pluginManager->expects($this->once())
			->method('countUsers')
			->willReturn(42);

		$this->assertEquals($this->backend->countUsers(), 42);
	}

	public function testLoginName2UserNameSuccess(): void {
		$loginName = 'Alice';
		$username = 'alice';
		$dn = 'uid=alice,dc=what,dc=ever';

		$this->access->expects($this->once())
			->method('fetchUsersByLoginName')
			->with($this->equalTo($loginName))
			->willReturn([['dn' => [$dn]]]);
		$this->access->expects($this->any())
			->method('stringResemblesDN')
			->with($this->equalTo($dn))
			->willReturn(true);
		$this->access->expects($this->any())
			->method('dn2username')
			->with($this->equalTo($dn))
			->willReturn($username);

		$this->connection->expects($this->exactly(2))
			->method('getFromCache')
			->with($this->equalTo('loginName2UserName-' . $loginName))
			->willReturnOnConsecutiveCalls(null, $username);
		$this->connection->expects($this->once())
			->method('writeToCache')
			->with($this->equalTo('loginName2UserName-' . $loginName), $this->equalTo($username));

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$user = $this->createMock(User::class);
		$user->expects($this->any())
			->method('getUsername')
			->willReturn('alice');

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with($dn)
			->willReturn($user);
		$this->userManager->expects($this->never())
			->method('exists');
		$this->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['dn', 'uid', 'mail', 'displayname']);

		$name = $backend->loginName2UserName($loginName);
		$this->assertSame($username, $name);

		// and once again to verify that caching works
		$backend->loginName2UserName($loginName);
	}

	public function testLoginName2UserNameNoUsersOnLDAP(): void {
		$loginName = 'Loki';

		$this->access->expects($this->once())
			->method('fetchUsersByLoginName')
			->with($this->equalTo($loginName))
			->willReturn([]);
		$this->access->expects($this->never())
			->method('stringResemblesDN');
		$this->access->expects($this->never())
			->method('dn2username');

		$this->connection->expects($this->exactly(2))
			->method('getFromCache')
			->with($this->equalTo('loginName2UserName-' . $loginName))
			->willReturnOnConsecutiveCalls(null, false);
		$this->connection->expects($this->once())
			->method('writeToCache')
			->with($this->equalTo('loginName2UserName-' . $loginName), false);

		$this->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['dn', 'uid', 'mail', 'displayname']);

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$name = $backend->loginName2UserName($loginName);
		$this->assertSame(false, $name);

		// and once again to verify that caching works
		$backend->loginName2UserName($loginName);
	}

	public function testLoginName2UserNameOfflineUser(): void {
		$loginName = 'Alice';
		$dn = 'uid=alice,dc=what,dc=ever';

		$offlineUser = $this->getMockBuilder(OfflineUser::class)
			->disableOriginalConstructor()
			->getMock();

		$this->access->expects($this->once())
			->method('fetchUsersByLoginName')
			->with($this->equalTo($loginName))
			->willReturn([['dn' => [$dn]]]);

		$this->connection->expects($this->exactly(2))
			->method('getFromCache')
			->with($this->equalTo('loginName2UserName-' . $loginName))
			->willReturnOnConsecutiveCalls(null, false);
		$this->connection->expects($this->once())
			->method('writeToCache')
			->with($this->equalTo('loginName2UserName-' . $loginName), $this->equalTo(false));

		$this->userManager->expects($this->any())
			->method('get')
			->with($dn)
			->willReturn($offlineUser);
		$this->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['dn', 'uid', 'mail', 'displayname']);

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$name = $backend->loginName2UserName($loginName);
		$this->assertSame(false, $name);

		// and once again to verify that caching works
		$backend->loginName2UserName($loginName);
	}

	/**
	 * Prepares the Access mock for setPassword tests
	 */
	private function prepareAccessForSetPassword($enablePasswordChange = true) {
		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) use (&$enablePasswordChange) {
				if ($name === 'ldapLoginFilter') {
					return '%uid';
				}
				if ($name === 'turnOnPasswordChange') {
					return $enablePasswordChange?1:0;
				}
				return null;
			});
		$this->connection->expects($this->any())
			->method('getFromCache')
			->willReturnCallback(function ($uid) {
				if ($uid === 'userExists' . 'roland') {
					return true;
				}
				return null;
			});

		$this->access->expects($this->any())
			->method('fetchListOfUsers')
			->willReturnCallback(function ($filter) {
				if ($filter === 'roland') {
					return [['dn' => ['dnOfRoland,dc=test']]];
				}
				return [];
			});
		$this->access->expects($this->any())
			->method('fetchUsersByLoginName')
			->willReturnCallback(function ($uid) {
				if ($uid === 'roland') {
					return [['dn' => ['dnOfRoland,dc=test']]];
				}
				return [];
			});
		$this->access->expects($this->any())
			->method('dn2username')
			->with($this->equalTo('dnOfRoland,dc=test'))
			->willReturn('roland');
		$this->access->expects($this->any())
			->method('stringResemblesDN')
			->with($this->equalTo('dnOfRoland,dc=test'))
			->willReturn(true);
		$this->access->expects($this->any())
			->method('setPassword')
			->willReturnCallback(function ($uid, $password) {
				if (strlen($password) <= 5) {
					throw new HintException('Password fails quality checking policy', '', 19);
				}
				return true;
			});
	}


	public function testSetPasswordInvalid(): void {
		$this->expectException(HintException::class);
		$this->expectExceptionMessage('Password fails quality checking policy');

		$this->prepareAccessForSetPassword($this->access);
		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->willReturn($this->createMock(User::class));
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$this->assertTrue(\OC_User::setPassword('roland', 'dt'));
	}

	public function testSetPasswordValid(): void {
		$this->prepareAccessForSetPassword($this->access);

		$this->userManager->expects($this->any())
			->method('get')
			->willReturn($this->createMock(User::class));

		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		$this->userManager->expects($this->any())
			->method('get')
			->willReturn($this->createMock(User::class));

		Server::get(IUserManager::class)->registerBackend($backend);

		$this->assertTrue(\OC_User::setPassword('roland', 'dt12234$'));
	}

	public function testSetPasswordValidDisabled(): void {
		$this->userManager->expects($this->any())
			->method('get')
			->willReturn($this->createMock(User::class));

		$this->prepareAccessForSetPassword(false);
		$backend = new UserLDAP($this->access, $this->notificationManager, $this->pluginManager, $this->logger, $this->deletedUsersIndex);
		Server::get(IUserManager::class)->registerBackend($backend);

		$this->assertFalse(\OC_User::setPassword('roland', 'dt12234$'));
	}


	public function testSetPasswordWithInvalidUser(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('LDAP setPassword: Could not get user object for uid NotExistingUser. Maybe the LDAP entry has no set display name attribute?');

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->willReturn(null);

		$this->backend->setPassword('NotExistingUser', 'Password');
	}

	public function testSetPasswordWithUsernameFalse(): void {
		$user = $this->createMock(User::class);
		$user
			->expects($this->once())
			->method('getUsername')
			->willReturn(false);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->willReturn($user);

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertFalse($this->backend->setPassword('NotExistingUser', 'Password'));
	}

	public function testSetPasswordWithPlugin(): void {
		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::SET_PASSWORD)
			->willReturn(true);
		$this->pluginManager->expects($this->once())
			->method('setPassword')
			->with('uid', 'password')
			->willReturn('result');

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals($this->backend->setPassword('uid', 'password'), 'result');
	}

	public static function avatarDataProvider(): array {
		return [
			[ 'validImageData', false ],
			[ 'corruptImageData', true ],
			[ false, true]
		];
	}

	/**
	 * @dataProvider avatarDataProvider
	 */
	public function testCanChangeAvatar(string|bool $imageData, bool $expected): void {
		$isValidImage = str_starts_with((string)$imageData, 'valid');

		$user = $this->createMock(User::class);
		$user->expects($this->once())
			->method('getAvatarImage')
			->willReturn($imageData);
		$user->expects($this->atMost(1))
			->method('updateAvatar')
			->willReturn($isValidImage);

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->willReturn($user);

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertSame($expected, $this->backend->canChangeAvatar('uid'));
	}

	public function testCanChangeAvatarWithPlugin(): void {
		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::PROVIDE_AVATAR)
			->willReturn(true);
		$this->pluginManager->expects($this->once())
			->method('canChangeAvatar')
			->with('uid')
			->willReturn('result');

		$this->assertEquals($this->backend->canChangeAvatar('uid'), 'result');
	}

	public function testSetDisplayNameWithPlugin(): void {
		$newDisplayName = 'J. Baker';
		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::SET_DISPLAYNAME)
			->willReturn(true);
		$this->pluginManager->expects($this->once())
			->method('setDisplayName')
			->with('uid', $newDisplayName)
			->willReturn($newDisplayName);
		$this->access->expects($this->once())
			->method('cacheUserDisplayName');

		$this->assertEquals($newDisplayName, $this->backend->setDisplayName('uid', $newDisplayName));
	}


	public function testSetDisplayNameErrorWithPlugin(): void {
		$this->expectException(HintException::class);

		$newDisplayName = 'J. Baker';
		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::SET_DISPLAYNAME)
			->willReturn(true);
		$this->pluginManager->expects($this->once())
			->method('setDisplayName')
			->with('uid', $newDisplayName)
			->willThrowException(new HintException('something happned'));
		$this->access->expects($this->never())
			->method('cacheUserDisplayName');

		$this->backend->setDisplayName('uid', $newDisplayName);
	}

	public function testSetDisplayNameFailing(): void {
		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::SET_DISPLAYNAME)
			->willReturn(false);
		$this->access->expects($this->never())
			->method('cacheUserDisplayName');

		$this->assertFalse($this->backend->setDisplayName('uid', 'displayName'));
	}

	public function testCreateUserWithPlugin(): void {
		$uid = 'alien6372';
		$uuid = '123-2345-36756-123-2345234-4431';
		$pwd = 'passwørd';

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::CREATE_USER)
			->willReturn(true);
		$this->pluginManager->expects($this->once())
			->method('createUser')
			->with($uid, $pwd)
			->willReturn('result');

		$this->access->expects($this->atLeastOnce())
			->method('getUUID')
			->willReturn($uuid);
		$this->access->expects($this->once())
			->method('mapAndAnnounceIfApplicable')
			->with($this->isInstanceOf(AbstractMapping::class), $this->anything(), $uid, $uuid, true);
		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($this->createMock(UserMapping::class));

		$this->assertEquals($this->backend->createUser($uid, $pwd), true);
	}

	public function testCreateUserFailing(): void {
		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(Backend::CREATE_USER)
			->willReturn(false);

		$this->assertFalse($this->backend->createUser('uid', 'password'));
	}

	public static function actionProvider(): array {
		return [
			[ 'ldapUserAvatarRule', 'default', Backend::PROVIDE_AVATAR, true],
			[ 'ldapUserAvatarRule', 'data:selfiePhoto', Backend::PROVIDE_AVATAR, true],
			[ 'ldapUserAvatarRule', 'none', Backend::PROVIDE_AVATAR, false],
			[ 'turnOnPasswordChange', 0, Backend::SET_PASSWORD, false],
			[ 'turnOnPasswordChange', 1, Backend::SET_PASSWORD, true],
		];
	}

	/**
	 * @dataProvider actionProvider
	 */
	public function testImplementsAction(string $configurable, string|int $value, int $actionCode, bool $expected): void {
		$this->pluginManager->expects($this->once())
			->method('getImplementedActions')
			->willReturn(0);

		$this->connection->expects($this->any())
			->method('__get')
			->willReturnMap([
				[$configurable, $value],
			]);

		$this->assertSame($expected, $this->backend->implementsActions($actionCode));
	}
}
