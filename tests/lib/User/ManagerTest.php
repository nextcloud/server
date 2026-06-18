<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User;

use OC\AllConfig;
use OC\USER\BACKEND;
use OC\User\Database;
use OC\User\Manager;
use OC\User\User;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[Group('DB')]
class ManagerTest extends TestCase {
	private IConfig&MockObject $config;
	private IEventDispatcher&MockObject $eventDispatcher;
	private ICacheFactory&MockObject $cacheFactory;
	private ICache&MockObject $cache;
	private LoggerInterface&MockObject $logger;
	private IUserManager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->cacheFactory->method('createDistributed')
			->willReturn($this->cache);

		$this->manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
	}

	public function testGetBackends(): void {
		$userDummyBackend = $this->createMock(\Test\Util\User\Dummy::class);
		$this->manager->registerBackend($userDummyBackend);
		$this->assertEquals([$userDummyBackend], $this->manager->getBackends());
		$dummyDatabaseBackend = $this->createMock(Database::class);
		$this->manager->registerBackend($dummyDatabaseBackend);
		$this->assertEquals([$userDummyBackend, $dummyDatabaseBackend], $this->manager->getBackends());
	}

	public function testUserExistsSingleBackendExists(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);

		$this->manager->registerBackend($backend);

		$this->assertTrue($this->manager->userExists('foo'));
	}

	public function testUserExistsTooLong(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);

		$this->manager->registerBackend($backend);

		$this->assertFalse($this->manager->userExists('foo' . str_repeat('a', 62)));
	}

	public function testUserExistsSingleBackendNotExists(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(false);

		$this->manager->registerBackend($backend);

		$this->assertFalse($this->manager->userExists('foo'));
	}

	public function testUserExistsNoBackends(): void {
		$this->assertFalse($this->manager->userExists('foo'));
	}

	public function testUserExistsTwoBackendsSecondExists(): void {
		$backend1 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend1->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(false);

		$backend2 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend2->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);

		$this->manager->registerBackend($backend1);
		$this->manager->registerBackend($backend2);

		$this->assertTrue($this->manager->userExists('foo'));
	}

	public function testUserExistsTwoBackendsFirstExists(): void {
		$backend1 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend1->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);

		$backend2 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend2->expects($this->never())
			->method('userExists');

		$this->manager->registerBackend($backend1);
		$this->manager->registerBackend($backend2);

		$this->assertTrue($this->manager->userExists('foo'));
	}

	public function testCheckPassword(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('checkPassword')
			->with($this->equalTo('foo'), $this->equalTo('bar'))
			->willReturn('foo');

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === BACKEND::CHECK_PASSWORD) {
					return true;
				} else {
					return false;
				}
			});

		$this->manager->registerBackend($backend);

		$user = $this->manager->checkPassword('foo', 'bar');
		$this->assertTrue($user instanceof User);
	}

	public function testCheckPasswordNotSupported(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('checkPassword');

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$this->manager->registerBackend($backend);

		$this->assertFalse($this->manager->checkPassword('foo', 'bar'));
	}

	public function testGetOneBackendExists(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);
		$backend->expects($this->never())
			->method('loginName2UserName');

		$this->manager->registerBackend($backend);

		$this->assertEquals('foo', $this->manager->get('foo')->getUID());
	}

	public function testGetOneBackendNotExists(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(false);

		$this->manager->registerBackend($backend);

		$this->assertEquals(null, $this->manager->get('foo'));
	}

	public function testGetTooLong(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(false);

		$this->manager->registerBackend($backend);

		$this->assertEquals(null, $this->manager->get('foo' . str_repeat('a', 62)));
	}

	public function testGetOneBackendDoNotTranslateLoginNames(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('bLeNdEr'))
			->willReturn(true);
		$backend->expects($this->never())
			->method('loginName2UserName');

		$this->manager->registerBackend($backend);

		$this->assertEquals('bLeNdEr', $this->manager->get('bLeNdEr')->getUID());
	}

	public function testSearchOneBackend(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'))
			->willReturn(['foo', 'afoo', 'Afoo1', 'Bfoo']);
		$backend->expects($this->never())
			->method('loginName2UserName');

		$this->manager->registerBackend($backend);

		$result = $this->manager->search('fo');
		$this->assertEquals(4, count($result));
		$this->assertEquals('afoo', array_shift($result)->getUID());
		$this->assertEquals('Afoo1', array_shift($result)->getUID());
		$this->assertEquals('Bfoo', array_shift($result)->getUID());
		$this->assertEquals('foo', array_shift($result)->getUID());
	}

	public function testSearchTwoBackendLimitOffset(): void {
		$backend1 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend1->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'), $this->equalTo(3), $this->equalTo(1))
			->willReturn(['foo1', 'foo2']);
		$backend1->expects($this->never())
			->method('loginName2UserName');

		$backend2 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend2->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'), $this->equalTo(3), $this->equalTo(1))
			->willReturn(['foo3']);
		$backend2->expects($this->never())
			->method('loginName2UserName');

		$this->manager->registerBackend($backend1);
		$this->manager->registerBackend($backend2);

		$result = $this->manager->search('fo', 3, 1);
		$this->assertEquals(3, count($result));
		$this->assertEquals('foo1', array_shift($result)->getUID());
		$this->assertEquals('foo2', array_shift($result)->getUID());
		$this->assertEquals('foo3', array_shift($result)->getUID());
	}

	public static function dataCreateUserInvalid(): array {
		return [
			['te?st', 'foo', 'Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'],
			["te\tst", '', 'Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'],
			["te\nst", '', 'Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'],
			["te\rst", '', 'Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'],
			["te\0st", '', 'Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'],
			["te\x0Bst", '', 'Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'],
			["te\xe2st", '', 'Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'],
			["te\x80st", '', 'Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'],
			["te\x8bst", '', 'Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'],
			['', 'foo', 'A valid username must be provided'],
			[' ', 'foo', 'A valid username must be provided'],
			[' test', 'foo', 'Username contains whitespace at the beginning or at the end'],
			['test ', 'foo', 'Username contains whitespace at the beginning or at the end'],
			['.', 'foo', 'Username must not consist of dots only'],
			['..', 'foo', 'Username must not consist of dots only'],
			['.test', '', 'A valid password must be provided'],
			['test', '', 'A valid password must be provided'],
			['test' . str_repeat('a', 61), '', 'Login is too long'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataCreateUserInvalid')]
	public function testCreateUserInvalid($uid, $password, $exception): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('implementsActions')
			->with(\OC\User\Backend::CREATE_USER)
			->willReturn(true);

		$this->manager->registerBackend($backend);

		$this->expectException(\InvalidArgumentException::class, $exception);
		$this->manager->createUser($uid, $password);
	}

	public static function dataCreateUserValid(): array {
		return [
			['foo', 'bar'],
			['Foo', 'bar'],
			['FOO', 'bar'],
			['123', 'bar'],
			['foo bar', 'bar'],
			['foo_bar', 'bar'],
			['foo.bar', 'bar'],
			['foo@bar', 'bar'],
			["foo-bar", 'bar'],
			["foo'bar", 'bar'],
			['a', 'bar'],
			['test' . str_repeat('a', 59), 'bar'], // 63 chars total, still valid
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataCreateUserValid')]
	public function testCreateUserValid(string $uid, string $password): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend->expects($this->once())
			->method('createUser')
			->with($this->equalTo($uid), $this->equalTo($password))
			->willReturn(true);

		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo($uid))
			->willReturn(false);

		$backend->expects($this->never())
			->method('loginName2UserName');

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$user = $manager->createUser($uid, $password);
		$this->assertEquals($uid, $user->getUID());
	}

	public function testCreateUserSingleBackendNotExists(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend->expects($this->once())
			->method('createUser')
			->with($this->equalTo('foo'), $this->equalTo('bar'))
			->willReturn(true);

		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(false);
		$backend->expects($this->never())
			->method('loginName2UserName');

		$this->manager->registerBackend($backend);

		$user = $this->manager->createUser('foo', 'bar');
		$this->assertEquals('foo', $user->getUID());
	}

	public function testCreateUserSingleBackendExists(): void {
		$this->expectException(\Exception::class);

		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend->expects($this->never())
			->method('createUser');

		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);

		$this->manager->registerBackend($backend);

		$this->manager->createUser('foo', 'bar');
	}

	public function testCreateUserSingleBackendNotSupported(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$backend->expects($this->never())
			->method('createUser');

		$backend->expects($this->never())
			->method('userExists');

		$this->manager->registerBackend($backend);

		$this->assertFalse($this->manager->createUser('foo', 'bar'));
	}

	public function testCreateUserNoBackends(): void {
		$this->assertFalse($this->manager->createUser('foo', 'bar'));
	}

	public function testCreateUserFromBackendWithBackendError(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Could not create account');

		/** @var \Test\Util\User\Dummy&MockObject $backend */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend
			->expects($this->once())
			->method('createUser')
			->with('MyUid', 'MyPassword')
			->willReturn(false);

		$this->manager->createUserFromBackend('MyUid', 'MyPassword', $backend);
	}

	public function testCreateUserTwoBackendExists(): void {
		$this->expectException(\Exception::class);

		$backend1 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend1->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend1->expects($this->never())
			->method('createUser');

		$backend1->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(false);

		$backend2 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend2->expects($this->any())
			->method('implementsActions')
			->willReturn(true);

		$backend2->expects($this->never())
			->method('createUser');

		$backend2->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);

		$this->manager->registerBackend($backend1);
		$this->manager->registerBackend($backend2);

		$this->manager->createUser('foo', 'bar');
	}

	public function testCountUsersNoBackend(): void {
		$result = $this->manager->countUsers();
		$this->assertTrue(is_array($result));
		$this->assertTrue(empty($result));
	}

	public function testCountUsersOneBackend(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('countUsers')
			->willReturn(7);

		$backend->expects($this->once())
			->method('implementsActions')
			->with(BACKEND::COUNT_USERS)
			->willReturn(true);

		$backend->expects($this->once())
			->method('getBackendName')
			->willReturn('Mock_Test_Util_User_Dummy');

		$this->manager->registerBackend($backend);

		$result = $this->manager->countUsers();
		$keys = array_keys($result);
		$this->assertTrue(strpos($keys[0], 'Mock_Test_Util_User_Dummy') !== false);

		$users = array_shift($result);
		$this->assertEquals(7, $users);
	}

	public function testCountUsersTwoBackends(): void {
		/**
		 * @var \Test\Util\User\Dummy&MockObject $backend
		 */
		$backend1 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend1->expects($this->once())
			->method('countUsers')
			->willReturn(7);

		$backend1->expects($this->once())
			->method('implementsActions')
			->with(BACKEND::COUNT_USERS)
			->willReturn(true);
		$backend1->expects($this->once())
			->method('getBackendName')
			->willReturn('Mock_Test_Util_User_Dummy');

		$backend2 = $this->createMock(\Test\Util\User\Dummy::class);
		$backend2->expects($this->once())
			->method('countUsers')
			->willReturn(16);

		$backend2->expects($this->once())
			->method('implementsActions')
			->with(BACKEND::COUNT_USERS)
			->willReturn(true);
		$backend2->expects($this->once())
			->method('getBackendName')
			->willReturn('Mock_Test_Util_User_Dummy');

		$this->manager->registerBackend($backend1);
		$this->manager->registerBackend($backend2);

		$result = $this->manager->countUsers();
		//because the backends have the same class name, only one value expected
		$this->assertEquals(1, count($result));
		$keys = array_keys($result);
		$this->assertTrue(strpos($keys[0], 'Mock_Test_Util_User_Dummy') !== false);

		$users = array_shift($result);
		//users from backends shall be summed up
		$this->assertEquals(7 + 16, $users);
	}

	public function testCountUsersOnlyDisabled(): void {
		$this->manager = Server::get(IUserManager::class);
		// count other users in the db before adding our own
		$countBefore = $this->manager->countDisabledUsers();

		//Add test users
		$user1 = $this->manager->createUser('testdisabledcount1', 'testdisabledcount1');

		$user2 = $this->manager->createUser('testdisabledcount2', 'testdisabledcount2');
		$user2->setEnabled(false);

		$user3 = $this->manager->createUser('testdisabledcount3', 'testdisabledcount3');

		$user4 = $this->manager->createUser('testdisabledcount4', 'testdisabledcount4');
		$user4->setEnabled(false);

		$this->assertEquals($countBefore + 2, $this->manager->countDisabledUsers());

		//cleanup
		$user1->delete();
		$user2->delete();
		$user3->delete();
		$user4->delete();
	}

	public function testCountUsersOnlySeen(): void {
		$this->manager = Server::get(IUserManager::class);
		// count other users in the db before adding our own
		$countBefore = $this->manager->countSeenUsers();

		//Add test users
		$user1 = $this->manager->createUser('testseencount1', 'testseencount1');
		$user1->updateLastLoginTimestamp();

		$user2 = $this->manager->createUser('testseencount2', 'testseencount2');
		$user2->updateLastLoginTimestamp();

		$user3 = $this->manager->createUser('testseencount3', 'testseencount3');

		$user4 = $this->manager->createUser('testseencount4', 'testseencount4');
		$user4->updateLastLoginTimestamp();

		$this->assertEquals($countBefore + 3, $this->manager->countSeenUsers());

		//cleanup
		$user1->delete();
		$user2->delete();
		$user3->delete();
		$user4->delete();
	}

	public function testCallForSeenUsers(): void {
		$this->manager = Server::get(IUserManager::class);
		// count other users in the db before adding our own
		$count = 0;
		$function = function (IUser $user) use (&$count): void {
			$count++;
		};
		$this->manager->callForAllUsers($function, '', true);
		$countBefore = $count;

		//Add test users
		$user1 = $this->manager->createUser('testseen1', 'testseen10');
		$user1->updateLastLoginTimestamp();

		$user2 = $this->manager->createUser('testseen2', 'testseen20');
		$user2->updateLastLoginTimestamp();

		$user3 = $this->manager->createUser('testseen3', 'testseen30');

		$user4 = $this->manager->createUser('testseen4', 'testseen40');
		$user4->updateLastLoginTimestamp();

		$count = 0;
		$this->manager->callForAllUsers($function, '', true);

		$this->assertEquals($countBefore + 3, $count);

		//cleanup
		$user1->delete();
		$user2->delete();
		$user3->delete();
		$user4->delete();
	}

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	#[\PHPUnit\Framework\Attributes\PreserveGlobalState(enabled: false)]
	public function testRecentlyActive(): void {
		$config = Server::get(IConfig::class);
		$this->manager = Server::get(IUserManager::class);

		// Create some users
		$now = (string)time();
		$user1 = $this->manager->createUser('test_active_1', 'test_active_1');
		$config->setUserValue('test_active_1', 'login', 'lastLogin', $now);
		$user1->setDisplayName('test active 1');
		$user1->setSystemEMailAddress('roger@active.com');

		$user2 = $this->manager->createUser('TEST_ACTIVE_2_FRED', 'TEST_ACTIVE_2');
		$config->setUserValue('TEST_ACTIVE_2_FRED', 'login', 'lastLogin', $now);
		$user2->setDisplayName('TEST ACTIVE 2 UPPER');
		$user2->setSystemEMailAddress('Fred@Active.Com');

		$user3 = $this->manager->createUser('test_active_3', 'test_active_3');
		$config->setUserValue('test_active_3', 'login', 'lastLogin', $now + 1);
		$user3->setDisplayName('test active 3');

		$user4 = $this->manager->createUser('test_active_4', 'test_active_4');
		$config->setUserValue('test_active_4', 'login', 'lastLogin', $now);
		$user4->setDisplayName('Test Active 4');

		$user5 = $this->manager->createUser('test_inactive_1', 'test_inactive_1');
		$user5->setDisplayName('Test Inactive 1');
		$user2->setSystemEMailAddress('jeanne@Active.Com');

		// Search recently active
		//  - No search, case-insensitive order
		$users = $this->manager->getLastLoggedInUsers(4);
		$this->assertEquals(['test_active_3', 'test_active_1', 'TEST_ACTIVE_2_FRED', 'test_active_4'], $users);
		//  - Search, case-insensitive order
		$users = $this->manager->getLastLoggedInUsers(search: 'act');
		$this->assertEquals(['test_active_3', 'test_active_1', 'TEST_ACTIVE_2_FRED', 'test_active_4'], $users);
		//  - No search with offset
		$users = $this->manager->getLastLoggedInUsers(2, 2);
		$this->assertEquals(['TEST_ACTIVE_2_FRED', 'test_active_4'], $users);
		//  - Case insensitive search (email)
		$users = $this->manager->getLastLoggedInUsers(search: 'active.com');
		$this->assertEquals(['test_active_1', 'TEST_ACTIVE_2_FRED'], $users);
		//  - Case insensitive search (display name)
		$users = $this->manager->getLastLoggedInUsers(search: 'upper');
		$this->assertEquals(['TEST_ACTIVE_2_FRED'], $users);
		//  - Case insensitive search (uid)
		$users = $this->manager->getLastLoggedInUsers(search: 'fred');
		$this->assertEquals(['TEST_ACTIVE_2_FRED'], $users);

		// Delete users and config keys
		$user1->delete();
		$user2->delete();
		$user3->delete();
		$user4->delete();
		$user5->delete();
	}

	public function testDeleteUser(): void {
		/** @var AllConfig&MockObject */
		$config = $this->getMockBuilder(AllConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$config
			->expects($this->any())
			->method('getUserValue')
			->willReturnArgument(3);
		$config
			->expects($this->any())
			->method('getAppValue')
			->willReturnArgument(2);

		$this->manager = new Manager($config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$backend = new \Test\Util\User\Dummy();

		$this->manager->registerBackend($backend);
		$backend->createUser('foo', 'bar');
		$this->assertTrue($this->manager->userExists('foo'));
		$this->manager->get('foo')->delete();
		$this->assertFalse($this->manager->userExists('foo'));
	}

	public function testGetByEmail(): void {
		$userConfig = $this->createMock(IUserConfig::class);
		$userConfig->expects($this->once())
			->method('searchUsersByValueString')
			->with('settings', 'email', 'test@example.com')
			->willReturnCallback(function () {
				yield 'uid1';
				yield 'uid99';
				yield 'uid2';
			});

		$this->manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([$this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger])
			->onlyMethods(['getUserConfig', 'get'])
			->getMock();
		$this->manager->method('getUserConfig')->willReturn($userConfig);
		$this->manager->expects($this->exactly(3))
			->method('get')
			->willReturnCallback(function (string $uid): ?IUser {
				if ($uid === 'uid99') {
					return null;
				}
				$user = $this->createMock(IUser::class);
				$user->method('getUID')->willReturn($uid);
				return $user;
			});

		$users = $this->manager->getByEmail('test@example.com');
		$this->assertCount(2, $users);
		$this->assertEquals('uid1', $users[0]->getUID());
		$this->assertEquals('uid2', $users[1]->getUID());
	}

	public function testGetExistingUser() {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->method('userExists')
			->with('foobar')
			->willReturn(true);
		$backend->method('getDisplayName')
			->willReturn('Foo Bar');
		$backend->method('implementsActions')
			->willReturnCallback(fn (int $action) => $action === Backend::GET_DISPLAYNAME);

		$this->manager->registerBackend($backend);

		$user = $this->manager->getExistingUser('foobar');
		$this->assertEquals('foobar', $user->getUID());
		$this->assertEquals('Foo Bar', $user->getDisplayName());

		$user = $this->manager->getExistingUser('nobody', 'None');
		$this->assertEquals('nobody', $user->getUID());
		$this->assertEquals('None', $user->getDisplayName());
	}

	public function testGetAvatarUrlLight(): void {
		$this->assertEquals('http://localhost/index.php/avatar/userid/64', $this->manager->getAvatarUrlLight('userid', 64));
	}

	public function testGetAvatarUrlDark(): void {
		$this->assertEquals('http://localhost/index.php/avatar/userid/64/dark', $this->manager->getAvatarUrlDark('userid', 64));
	}
}
