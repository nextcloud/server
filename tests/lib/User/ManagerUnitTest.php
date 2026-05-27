<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ManagerUnitTest extends TestCase {
	private IConfig&MockObject $config;
	private IEventDispatcher&MockObject $eventDispatcher;
	private ICacheFactory&MockObject $cacheFactory;
	private ICache&MockObject $cache;
	private LoggerInterface&MockObject $logger;

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
	}

	public function testGetBackends(): void {
		$userDummyBackend = $this->createMock(\Test\Util\User\Dummy::class);
		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($userDummyBackend);
		$this->assertEquals([$userDummyBackend], $manager->getBackends());
		$dummyDatabaseBackend = $this->createMock(Database::class);
		$manager->registerBackend($dummyDatabaseBackend);
		$this->assertEquals([$userDummyBackend, $dummyDatabaseBackend], $manager->getBackends());
	}


	public function testUserExistsSingleBackendExists(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->assertTrue($manager->userExists('foo'));
	}

	public function testUserExistsTooLong(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->assertFalse($manager->userExists('foo' . str_repeat('a', 62)));
	}

	public function testUserExistsSingleBackendNotExists(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(false);

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->assertFalse($manager->userExists('foo'));
	}

	public function testUserExistsNoBackends(): void {
		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);

		$this->assertFalse($manager->userExists('foo'));
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$this->assertTrue($manager->userExists('foo'));
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$this->assertTrue($manager->userExists('foo'));
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$user = $manager->checkPassword('foo', 'bar');
		$this->assertTrue($user instanceof User);
	}

	public function testCheckPasswordNotSupported(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('checkPassword');

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->assertFalse($manager->checkPassword('foo', 'bar'));
	}

	public function testGetOneBackendExists(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(true);
		$backend->expects($this->never())
			->method('loginName2UserName');

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->assertEquals('foo', $manager->get('foo')->getUID());
	}

	public function testGetOneBackendNotExists(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(false);

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->assertEquals(null, $manager->get('foo'));
	}

	public function testGetTooLong(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('userExists')
			->with($this->equalTo('foo'))
			->willReturn(false);

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->assertEquals(null, $manager->get('foo' . str_repeat('a', 62)));
	}

	public function testGetOneBackendDoNotTranslateLoginNames(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('bLeNdEr'))
			->willReturn(true);
		$backend->expects($this->never())
			->method('loginName2UserName');

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->assertEquals('bLeNdEr', $manager->get('bLeNdEr')->getUID());
	}

	public function testSearchOneBackend(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'))
			->willReturn(['foo', 'afoo', 'Afoo1', 'Bfoo']);
		$backend->expects($this->never())
			->method('loginName2UserName');

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$result = $manager->search('fo');
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$result = $manager->search('fo', 3, 1);
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

	#[DataProvider('dataCreateUserInvalid')]
	public function testCreateUserInvalid($uid, $password, $exception): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('implementsActions')
			->with(\OC\User\Backend::CREATE_USER)
			->willReturn(true);


		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->expectException(\InvalidArgumentException::class, $exception);
		$manager->createUser($uid, $password);
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$user = $manager->createUser('foo', 'bar');
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$manager->createUser('foo', 'bar');
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$this->assertFalse($manager->createUser('foo', 'bar'));
	}

	public function testCreateUserNoBackends(): void {
		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);

		$this->assertFalse($manager->createUser('foo', 'bar'));
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->createUserFromBackend('MyUid', 'MyPassword', $backend);
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$manager->createUser('foo', 'bar');
	}

	public function testCountUsersNoBackend(): void {
		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);

		$result = $manager->countUsers();
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$result = $manager->countUsers();
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

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$result = $manager->countUsers();
		//because the backends have the same class name, only one value expected
		$this->assertEquals(1, count($result));
		$keys = array_keys($result);
		$this->assertTrue(strpos($keys[0], 'Mock_Test_Util_User_Dummy') !== false);

		$users = array_shift($result);
		//users from backends shall be summed up
		$this->assertEquals(7 + 16, $users);
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

		$manager = new Manager($config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$backend = new \Test\Util\User\Dummy();

		$manager->registerBackend($backend);
		$backend->createUser('foo', 'bar');
		$this->assertTrue($manager->userExists('foo'));
		$manager->get('foo')->delete();
		$this->assertFalse($manager->userExists('foo'));
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

		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([$this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger])
			->onlyMethods(['getUserConfig', 'get'])
			->getMock();
		$manager->method('getUserConfig')->willReturn($userConfig);
		$manager->expects($this->exactly(3))
			->method('get')
			->willReturnCallback(function (string $uid): ?IUser {
				if ($uid === 'uid99') {
					return null;
				}
				$user = $this->createMock(IUser::class);
				$user->method('getUID')->willReturn($uid);
				return $user;
			});

		$users = $manager->getByEmail('test@example.com');
		$this->assertCount(2, $users);
		$this->assertEquals('uid1', $users[0]->getUID());
		$this->assertEquals('uid2', $users[1]->getUID());
	}

	public function testGetExistingUser(): void {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->method('userExists')
			->with('foobar')
			->willReturn(true);
		$backend->method('getDisplayName')
			->willReturn('Foo Bar');
		$backend->method('implementsActions')
			->willReturnCallback(fn (int $action) => $action === \OC\User\Backend::GET_DISPLAYNAME);

		$manager = new Manager($this->config, $this->cacheFactory, $this->eventDispatcher, $this->logger);
		$manager->registerBackend($backend);

		$user = $manager->getExistingUser('foobar');
		$this->assertEquals('foobar', $user->getUID());
		$this->assertEquals('Foo Bar', $user->getDisplayName());

		$user = $manager->getExistingUser('nobody', 'None');
		$this->assertEquals('nobody', $user->getUID());
		$this->assertEquals('None', $user->getDisplayName());
	}
}
