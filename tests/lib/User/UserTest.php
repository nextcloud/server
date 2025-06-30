<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User;

use OC\AllConfig;
use OC\Files\Mount\ObjectHomeMountProvider;
use OC\Hooks\PublicEmitter;
use OC\User\Database;
use OC\User\User;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\FileInfo;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Server;
use OCP\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class UserTest
 *
 * @group DB
 *
 * @package Test\User
 */
class UserTest extends TestCase {
	/** @var IEventDispatcher|MockObject */
	protected $dispatcher;

	protected function setUp(): void {
		parent::setUp();
		$this->dispatcher = Server::get(IEventDispatcher::class);
	}

	public function testDisplayName(): void {
		/**
		 * @var \OC\User\Backend | MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Backend::class);
		$backend->expects($this->once())
			->method('getDisplayName')
			->with($this->equalTo('foo'))
			->willReturn('Foo');

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC\User\Backend::GET_DISPLAYNAME))
			->willReturn(true);

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertEquals('Foo', $user->getDisplayName());
	}

	/**
	 * if the display name contain whitespaces only, we expect the uid as result
	 */
	public function testDisplayNameEmpty(): void {
		/**
		 * @var \OC\User\Backend | MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Backend::class);
		$backend->expects($this->once())
			->method('getDisplayName')
			->with($this->equalTo('foo'))
			->willReturn('  ');

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC\User\Backend::GET_DISPLAYNAME))
			->willReturn(true);

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testDisplayNameNotSupported(): void {
		/**
		 * @var \OC\User\Backend | MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Backend::class);
		$backend->expects($this->never())
			->method('getDisplayName');

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC\User\Backend::GET_DISPLAYNAME))
			->willReturn(false);

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testSetPassword(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('setPassword')
			->with($this->equalTo('foo'), $this->equalTo('bar'));

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_PASSWORD) {
					return true;
				} else {
					return false;
				}
			});

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->setPassword('bar', ''));
	}

	public function testSetPasswordNotSupported(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('setPassword');

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->setPassword('bar', ''));
	}

	public function testChangeAvatarSupportedYes(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(AvatarUserDummy::class);
		$backend->expects($this->once())
			->method('canChangeAvatar')
			->with($this->equalTo('foo'))
			->willReturn(true);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::PROVIDE_AVATAR) {
					return true;
				} else {
					return false;
				}
			});

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->canChangeAvatar());
	}

	public function testChangeAvatarSupportedNo(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(AvatarUserDummy::class);
		$backend->expects($this->once())
			->method('canChangeAvatar')
			->with($this->equalTo('foo'))
			->willReturn(false);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::PROVIDE_AVATAR) {
					return true;
				} else {
					return false;
				}
			});

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->canChangeAvatar());
	}

	public function testChangeAvatarNotSupported(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(AvatarUserDummy::class);
		$backend->expects($this->never())
			->method('canChangeAvatar');

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->canChangeAvatar());
	}

	public function testDelete(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('deleteUser')
			->with($this->equalTo('foo'));

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->delete());
	}

	public function testDeleteWithDifferentHome(): void {
		/** @var ObjectHomeMountProvider $homeProvider */
		$homeProvider = Server::get(ObjectHomeMountProvider::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('foo');
		if ($homeProvider->getHomeMountForUser($user, $this->createMock(IStorageFactory::class)) !== null) {
			$this->markTestSkipped('Skipping test for non local home storage');
		}

		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->once())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::GET_HOME) {
					return true;
				} else {
					return false;
				}
			});

		// important: getHome MUST be called before deleteUser because
		// once the user is deleted, getHome implementations might not
		// return anything
		$backend->expects($this->once())
			->method('getHome')
			->with($this->equalTo('foo'))
			->willReturn('/home/foo');

		$backend->expects($this->once())
			->method('deleteUser')
			->with($this->equalTo('foo'));

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->delete());
	}

	public function testGetHome(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('getHome')
			->with($this->equalTo('foo'))
			->willReturn('/home/foo');

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::GET_HOME) {
					return true;
				} else {
					return false;
				}
			});

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertEquals('/home/foo', $user->getHome());
	}

	public function testGetBackendClassName(): void {
		$user = new User('foo', new \Test\Util\User\Dummy(), $this->dispatcher);
		$this->assertEquals('Dummy', $user->getBackendClassName());
		$user = new User('foo', new Database(), $this->dispatcher);
		$this->assertEquals('Database', $user->getBackendClassName());
	}

	public function testGetHomeNotSupported(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('getHome');

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$allConfig = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$allConfig->expects($this->any())
			->method('getUserValue')
			->willReturn(true);
		$allConfig->expects($this->any())
			->method('getSystemValueString')
			->with($this->equalTo('datadirectory'))
			->willReturn('arbitrary/path');

		$user = new User('foo', $backend, $this->dispatcher, null, $allConfig);
		$this->assertEquals('arbitrary/path/foo', $user->getHome());
	}

	public function testCanChangePassword(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_PASSWORD) {
					return true;
				} else {
					return false;
				}
			});

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->canChangePassword());
	}

	public function testCanChangePasswordNotSupported(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->canChangePassword());
	}

	public function testCanChangeDisplayName(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_DISPLAYNAME) {
					return true;
				} else {
					return false;
				}
			});

		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')
			->with('allow_user_to_change_display_name')
			->willReturn(true);

		$user = new User('foo', $backend, $this->dispatcher, null, $config);
		$this->assertTrue($user->canChangeDisplayName());
	}

	public function testCanChangeDisplayNameNotSupported(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->canChangeDisplayName());
	}

	public function testSetDisplayNameSupported(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(Database::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_DISPLAYNAME) {
					return true;
				} else {
					return false;
				}
			});

		$backend->expects($this->once())
			->method('setDisplayName')
			->with('foo', 'Foo')
			->willReturn(true);

		$user = new User('foo', $backend, $this->createMock(IEventDispatcher::class));
		$this->assertTrue($user->setDisplayName('Foo'));
		$this->assertEquals('Foo', $user->getDisplayName());
	}

	/**
	 * don't allow display names containing whitespaces only
	 */
	public function testSetDisplayNameEmpty(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(Database::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_DISPLAYNAME) {
					return true;
				} else {
					return false;
				}
			});

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->setDisplayName(' '));
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testSetDisplayNameNotSupported(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(Database::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$backend->expects($this->never())
			->method('setDisplayName');

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->setDisplayName('Foo'));
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testSetPasswordHooks(): void {
		$hooksCalled = 0;
		$test = $this;

		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('setPassword');

		/**
		 * @param User $user
		 * @param string $password
		 */
		$hook = function ($user, $password) use ($test, &$hooksCalled): void {
			$hooksCalled++;
			$test->assertEquals('foo', $user->getUID());
			$test->assertEquals('bar', $password);
		};

		$emitter = new PublicEmitter();
		$emitter->listen('\OC\User', 'preSetPassword', $hook);
		$emitter->listen('\OC\User', 'postSetPassword', $hook);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_PASSWORD) {
					return true;
				} else {
					return false;
				}
			});

		$user = new User('foo', $backend, $this->dispatcher, $emitter);

		$user->setPassword('bar', '');
		$this->assertEquals(2, $hooksCalled);
	}

	public static function dataDeleteHooks(): array {
		return [
			[true, 2],
			[false, 1],
		];
	}

	/**
	 * @dataProvider dataDeleteHooks
	 * @param bool $result
	 * @param int $expectedHooks
	 */
	public function testDeleteHooks($result, $expectedHooks): void {
		$hooksCalled = 0;
		$test = $this;

		/**
		 * @var UserInterface&MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('deleteUser')
			->willReturn($result);

		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->willReturnArgument(1);
		$config->method('getSystemValueString')
			->willReturnArgument(1);
		$config->method('getSystemValueBool')
			->willReturnArgument(1);
		$config->method('getSystemValueInt')
			->willReturnArgument(1);

		$emitter = new PublicEmitter();
		$user = new User('foo', $backend, $this->dispatcher, $emitter, $config);

		/**
		 * @param User $user
		 */
		$hook = function ($user) use ($test, &$hooksCalled): void {
			$hooksCalled++;
			$test->assertEquals('foo', $user->getUID());
		};

		$emitter->listen('\OC\User', 'preDelete', $hook);
		$emitter->listen('\OC\User', 'postDelete', $hook);

		$commentsManager = $this->createMock(ICommentsManager::class);
		$notificationManager = $this->createMock(INotificationManager::class);

		if ($result) {
			$config->expects($this->atLeastOnce())
				->method('deleteAllUserValues')
				->with('foo');

			$commentsManager->expects($this->once())
				->method('deleteReferencesOfActor')
				->with('users', 'foo');
			$commentsManager->expects($this->once())
				->method('deleteReadMarksFromUser')
				->with($user);

			$notification = $this->createMock(INotification::class);
			$notification->expects($this->once())
				->method('setUser')
				->with('foo');

			$notificationManager->expects($this->once())
				->method('createNotification')
				->willReturn($notification);
			$notificationManager->expects($this->once())
				->method('markProcessed')
				->with($notification);
		} else {
			$config->expects($this->never())
				->method('deleteAllUserValues');

			$commentsManager->expects($this->never())
				->method('deleteReferencesOfActor');
			$commentsManager->expects($this->never())
				->method('deleteReadMarksFromUser');

			$notificationManager->expects($this->never())
				->method('createNotification');
			$notificationManager->expects($this->never())
				->method('markProcessed');
		}

		$this->overwriteService(INotificationManager::class, $notificationManager);
		$this->overwriteService(ICommentsManager::class, $commentsManager);

		$this->assertSame($result, $user->delete());

		$this->restoreService(AllConfig::class);
		$this->restoreService(ICommentsManager::class);
		$this->restoreService(INotificationManager::class);

		$this->assertEquals($expectedHooks, $hooksCalled);
	}

	public function testDeleteRecoverState() {
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('deleteUser')
			->willReturn(true);

		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->willReturnArgument(1);
		$config->method('getSystemValueString')
			->willReturnArgument(1);
		$config->method('getSystemValueBool')
			->willReturnArgument(1);
		$config->method('getSystemValueInt')
			->willReturnArgument(1);

		$userConfig = [];
		$config->expects(self::atLeast(2))
			->method('setUserValue')
			->willReturnCallback(function (): void {
				$userConfig[] = func_get_args();
			});

		$commentsManager = $this->createMock(ICommentsManager::class);
		$commentsManager->expects($this->once())
			->method('deleteReferencesOfActor')
			->willThrowException(new \Error('Test exception'));

		$this->overwriteService(ICommentsManager::class, $commentsManager);
		$this->expectException(\Error::class);

		$user = $this->getMockBuilder(User::class)
			->onlyMethods(['getHome'])
			->setConstructorArgs(['foo', $backend, $this->dispatcher, null, $config])
			->getMock();

		$user->expects(self::atLeastOnce())
			->method('getHome')
			->willReturn('/home/path');

		$user->delete();

		$this->assertEqualsCanonicalizing(
			[
				['foo', 'core', 'deleted', 'true', null],
				['foo', 'core', 'deleted.backup-home', '/home/path', null],
			],
			$userConfig,
		);

		$this->restoreService(ICommentsManager::class);
	}

	public static function dataGetCloudId(): array {
		return [
			['https://localhost:8888/nextcloud', 'foo@localhost:8888/nextcloud'],
			['http://localhost:8888/nextcloud', 'foo@http://localhost:8888/nextcloud'],
		];
	}

	/**
	 * @dataProvider dataGetCloudId
	 */
	public function testGetCloudId(string $absoluteUrl, string $cloudId): void {
		/** @var Backend|MockObject $backend */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('getAbsoluteURL')
			->withAnyParameters()
			->willReturn($absoluteUrl);
		$user = new User('foo', $backend, $this->dispatcher, null, null, $urlGenerator);
		$this->assertEquals($cloudId, $user->getCloudId());
	}

	public function testSetEMailAddressEmpty(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$test = $this;
		$hooksCalled = 0;

		/**
		 * @param IUser $user
		 * @param string $feature
		 * @param string $value
		 */
		$hook = function (IUser $user, $feature, $value) use ($test, &$hooksCalled): void {
			$hooksCalled++;
			$test->assertEquals('eMailAddress', $feature);
			$test->assertEquals('', $value);
		};

		$emitter = new PublicEmitter();
		$emitter->listen('\OC\User', 'changeUser', $hook);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('deleteUserValue')
			->with(
				'foo',
				'settings',
				'email'
			);

		$user = new User('foo', $backend, $this->dispatcher, $emitter, $config);
		$user->setEMailAddress('');
	}

	public function testSetEMailAddress(): void {
		/**
		 * @var UserInterface | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$test = $this;
		$hooksCalled = 0;

		/**
		 * @param IUser $user
		 * @param string $feature
		 * @param string $value
		 */
		$hook = function (IUser $user, $feature, $value) use ($test, &$hooksCalled): void {
			$hooksCalled++;
			$test->assertEquals('eMailAddress', $feature);
			$test->assertEquals('foo@bar.com', $value);
		};

		$emitter = new PublicEmitter();
		$emitter->listen('\OC\User', 'changeUser', $hook);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('setUserValue')
			->with(
				'foo',
				'settings',
				'email',
				'foo@bar.com'
			);

		$user = new User('foo', $backend, $this->dispatcher, $emitter, $config);
		$user->setEMailAddress('foo@bar.com');
	}

	public function testSetEMailAddressNoChange(): void {
		/**
		 * @var UserInterface | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		/** @var PublicEmitter|MockObject $emitter */
		$emitter = $this->createMock(PublicEmitter::class);
		$emitter->expects($this->never())
			->method('emit');

		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->expects($this->never())
			->method('dispatch');

		$config = $this->createMock(IConfig::class);
		$config->expects($this->any())
			->method('getUserValue')
			->willReturn('foo@bar.com');
		$config->expects($this->any())
			->method('setUserValue');

		$user = new User('foo', $backend, $dispatcher, $emitter, $config);
		$user->setEMailAddress('foo@bar.com');
	}

	public function testSetQuota(): void {
		/**
		 * @var UserInterface | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$test = $this;
		$hooksCalled = 0;

		/**
		 * @param IUser $user
		 * @param string $feature
		 * @param string $value
		 */
		$hook = function (IUser $user, $feature, $value) use ($test, &$hooksCalled): void {
			$hooksCalled++;
			$test->assertEquals('quota', $feature);
			$test->assertEquals('23 TB', $value);
		};

		$emitter = new PublicEmitter();
		$emitter->listen('\OC\User', 'changeUser', $hook);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('setUserValue')
			->with(
				'foo',
				'files',
				'quota',
				'23 TB'
			);

		$user = new User('foo', $backend, $this->dispatcher, $emitter, $config);
		$user->setQuota('23 TB');
	}

	public function testGetDefaultUnlimitedQuota(): void {
		/**
		 * @var UserInterface | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		/** @var PublicEmitter|MockObject $emitter */
		$emitter = $this->createMock(PublicEmitter::class);
		$emitter->expects($this->never())
			->method('emit');

		$config = $this->createMock(IConfig::class);
		$user = new User('foo', $backend, $this->dispatcher, $emitter, $config);

		$userValueMap = [
			['foo', 'files', 'quota', 'default', 'default'],
		];
		$appValueMap = [
			['files', 'default_quota', 'none', 'none'],
			// allow unlimited quota
			['files', 'allow_unlimited_quota', '1', '1'],
		];
		$config->method('getUserValue')
			->willReturnMap($userValueMap);
		$config->method('getAppValue')
			->willReturnMap($appValueMap);

		$this->assertEquals('none', $user->getQuota());
		$this->assertEquals(FileInfo::SPACE_UNLIMITED, $user->getQuotaBytes());
	}

	public function testGetDefaultUnlimitedQuotaForbidden(): void {
		/**
		 * @var UserInterface | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		/** @var PublicEmitter|MockObject $emitter */
		$emitter = $this->createMock(PublicEmitter::class);
		$emitter->expects($this->never())
			->method('emit');

		$config = $this->createMock(IConfig::class);
		$user = new User('foo', $backend, $this->dispatcher, $emitter, $config);

		$userValueMap = [
			['foo', 'files', 'quota', 'default', 'default'],
		];
		$appValueMap = [
			['files', 'default_quota', 'none', 'none'],
			// do not allow unlimited quota
			['files', 'allow_unlimited_quota', '1', '0'],
			['files', 'quota_preset', '1 GB, 5 GB, 10 GB', '1 GB, 5 GB, 10 GB'],
			// expect seeing 1 GB used as fallback value
			['files', 'default_quota', '1 GB', '1 GB'],
		];
		$config->method('getUserValue')
			->willReturnMap($userValueMap);
		$config->method('getAppValue')
			->willReturnMap($appValueMap);

		$this->assertEquals('1 GB', $user->getQuota());
		$this->assertEquals(1024 * 1024 * 1024, $user->getQuotaBytes());
	}

	public function testSetQuotaAddressNoChange(): void {
		/**
		 * @var UserInterface | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		/** @var PublicEmitter|MockObject $emitter */
		$emitter = $this->createMock(PublicEmitter::class);
		$emitter->expects($this->never())
			->method('emit');

		$config = $this->createMock(IConfig::class);
		$config->expects($this->any())
			->method('getUserValue')
			->willReturn('23 TB');
		$config->expects($this->never())
			->method('setUserValue');

		$user = new User('foo', $backend, $this->dispatcher, $emitter, $config);
		$user->setQuota('23 TB');
	}

	public function testGetLastLogin(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->method('getUserValue')
			->willReturnCallback(function ($uid, $app, $key, $default) {
				if ($uid === 'foo' && $app === 'login' && $key === 'lastLogin') {
					return 42;
				} else {
					return $default;
				}
			});

		$user = new User('foo', $backend, $this->dispatcher, null, $config);
		$this->assertSame(42, $user->getLastLogin());
	}

	public function testSetEnabled(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('setUserValue')
			->with(
				$this->equalTo('foo'),
				$this->equalTo('core'),
				$this->equalTo('enabled'),
				'true'
			);
		/* dav event listener gets the manager list from config */
		$config->expects(self::any())
			->method('getUserValue')
			->willReturnCallback(
				fn ($user, $app, $key, $default) => ($key === 'enabled' ? 'false' : $default)
			);

		$user = new User('foo', $backend, $this->dispatcher, null, $config);
		$user->setEnabled(true);
	}

	public function testSetDisabled(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('setUserValue')
			->with(
				$this->equalTo('foo'),
				$this->equalTo('core'),
				$this->equalTo('enabled'),
				'false'
			);

		$user = $this->getMockBuilder(User::class)
			->setConstructorArgs([
				'foo',
				$backend,
				$this->dispatcher,
				null,
				$config,
			])
			->onlyMethods(['isEnabled', 'triggerChange'])
			->getMock();

		$user->expects($this->once())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->once())
			->method('triggerChange')
			->with(
				'enabled',
				false
			);

		$user->setEnabled(false);
	}

	public function testSetDisabledAlreadyDisabled(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->never())
			->method('setUserValue');

		$user = $this->getMockBuilder(User::class)
			->setConstructorArgs([
				'foo',
				$backend,
				$this->dispatcher,
				null,
				$config,
			])
			->onlyMethods(['isEnabled', 'triggerChange'])
			->getMock();

		$user->expects($this->once())
			->method('isEnabled')
			->willReturn(false);
		$user->expects($this->never())
			->method('triggerChange');

		$user->setEnabled(false);
	}

	public function testGetEMailAddress(): void {
		/**
		 * @var Backend | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->method('getUserValue')
			->willReturnCallback(function ($uid, $app, $key, $default) {
				if ($uid === 'foo' && $app === 'settings' && $key === 'email') {
					return 'foo@bar.com';
				} else {
					return $default;
				}
			});

		$user = new User('foo', $backend, $this->dispatcher, null, $config);
		$this->assertSame('foo@bar.com', $user->getEMailAddress());
	}
}
