<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\User;

use OC\Hooks\PublicEmitter;
use OC\User\Database;
use OC\User\User;
use OCP\Comments\ICommentsManager;
use OCP\IConfig;
use OCP\IUser;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\UserInterface;

/**
 * Class UserTest
 *
 * @group DB
 *
 * @package Test\User
 */
class UserTest extends \Test\TestCase {
	public function testDisplayName() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Backend::class);
		$backend->expects($this->once())
			->method('getDisplayName')
			->with($this->equalTo('foo'))
			->will($this->returnValue('Foo'));

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC\User\Backend::GET_DISPLAYNAME))
			->will($this->returnValue(true));

		$user = new \OC\User\User('foo', $backend);
		$this->assertEquals('Foo', $user->getDisplayName());
	}

	/**
	 * if the display name contain whitespaces only, we expect the uid as result
	 */
	public function testDisplayNameEmpty() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Backend::class);
		$backend->expects($this->once())
			->method('getDisplayName')
			->with($this->equalTo('foo'))
			->will($this->returnValue('  '));

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC\User\Backend::GET_DISPLAYNAME))
			->will($this->returnValue(true));

		$user = new \OC\User\User('foo', $backend);
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testDisplayNameNotSupported() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Backend::class);
		$backend->expects($this->never())
			->method('getDisplayName');

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC\User\Backend::GET_DISPLAYNAME))
			->will($this->returnValue(false));

		$user = new \OC\User\User('foo', $backend);
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testSetPassword() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('setPassword')
			->with($this->equalTo('foo'), $this->equalTo('bar'));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_PASSWORD) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->setPassword('bar',''));
	}

	public function testSetPasswordNotSupported() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('setPassword');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$user = new \OC\User\User('foo', $backend);
		$this->assertFalse($user->setPassword('bar',''));
	}

	public function testChangeAvatarSupportedYes() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(AvatarUserDummy::class);
		$backend->expects($this->once())
			->method('canChangeAvatar')
			->with($this->equalTo('foo'))
			->will($this->returnValue(true));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::PROVIDE_AVATAR) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->canChangeAvatar());
	}

	public function testChangeAvatarSupportedNo() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(AvatarUserDummy::class);
		$backend->expects($this->once())
			->method('canChangeAvatar')
			->with($this->equalTo('foo'))
			->will($this->returnValue(false));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::PROVIDE_AVATAR) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend);
		$this->assertFalse($user->canChangeAvatar());
	}

	public function testChangeAvatarNotSupported() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(AvatarUserDummy::class);
		$backend->expects($this->never())
			->method('canChangeAvatar');

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->canChangeAvatar());
	}

	public function testDelete() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('deleteUser')
			->with($this->equalTo('foo'));

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->delete());
	}

	public function testGetHome() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('getHome')
			->with($this->equalTo('foo'))
			->will($this->returnValue('/home/foo'));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::GET_HOME) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend);
		$this->assertEquals('/home/foo', $user->getHome());
	}

	public function testGetBackendClassName() {
		$user = new \OC\User\User('foo', new \Test\Util\User\Dummy());
		$this->assertEquals('Dummy', $user->getBackendClassName());
		$user = new \OC\User\User('foo', new \OC\User\Database());
		$this->assertEquals('Database', $user->getBackendClassName());
	}

	public function testGetHomeNotSupported() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('getHome');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$allConfig = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$allConfig->expects($this->any())
			->method('getUserValue')
			->will($this->returnValue(true));
		$allConfig->expects($this->any())
			->method('getSystemValue')
			->with($this->equalTo('datadirectory'))
			->will($this->returnValue('arbitrary/path'));

		$user = new \OC\User\User('foo', $backend, null, $allConfig);
		$this->assertEquals('arbitrary/path/foo', $user->getHome());
	}

	public function testCanChangePassword() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_PASSWORD) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->canChangePassword());
	}

	public function testCanChangePasswordNotSupported() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$user = new \OC\User\User('foo', $backend);
		$this->assertFalse($user->canChangePassword());
	}

	public function testCanChangeDisplayName() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_DISPLAYNAME) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->canChangeDisplayName());
	}

	public function testCanChangeDisplayNameNotSupported() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$user = new \OC\User\User('foo', $backend);
		$this->assertFalse($user->canChangeDisplayName());
	}

	public function testSetDisplayNameSupported() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(Database::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_DISPLAYNAME) {
					return true;
				} else {
					return false;
				}
			}));

		$backend->expects($this->once())
			->method('setDisplayName')
			->with('foo','Foo')
			->willReturn(true);

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->setDisplayName('Foo'));
		$this->assertEquals('Foo',$user->getDisplayName());
	}

	/**
	 * don't allow display names containing whitespaces only
	 */
	public function testSetDisplayNameEmpty() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(Database::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_DISPLAYNAME) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend);
		$this->assertFalse($user->setDisplayName(' '));
		$this->assertEquals('foo',$user->getDisplayName());
	}

	public function testSetDisplayNameNotSupported() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(Database::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$backend->expects($this->never())
			->method('setDisplayName');

		$user = new \OC\User\User('foo', $backend);
		$this->assertFalse($user->setDisplayName('Foo'));
		$this->assertEquals('foo',$user->getDisplayName());
	}

	public function testSetPasswordHooks() {
		$hooksCalled = 0;
		$test = $this;

		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('setPassword');

		/**
		 * @param \OC\User\User $user
		 * @param string $password
		 */
		$hook = function ($user, $password) use ($test, &$hooksCalled) {
			$hooksCalled++;
			$test->assertEquals('foo', $user->getUID());
			$test->assertEquals('bar', $password);
		};

		$emitter = new PublicEmitter();
		$emitter->listen('\OC\User', 'preSetPassword', $hook);
		$emitter->listen('\OC\User', 'postSetPassword', $hook);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_PASSWORD) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend, $emitter);

		$user->setPassword('bar','');
		$this->assertEquals(2, $hooksCalled);
	}

	public function dataDeleteHooks() {
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
	public function testDeleteHooks($result, $expectedHooks) {
		$hooksCalled = 0;
		$test = $this;

		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('deleteUser')
			->willReturn($result);
		$emitter = new PublicEmitter();
		$user = new \OC\User\User('foo', $backend, $emitter);

		/**
		 * @param \OC\User\User $user
		 */
		$hook = function ($user) use ($test, &$hooksCalled) {
			$hooksCalled++;
			$test->assertEquals('foo', $user->getUID());
		};

		$emitter->listen('\OC\User', 'preDelete', $hook);
		$emitter->listen('\OC\User', 'postDelete', $hook);

		$config = $this->createMock(IConfig::class);
		$commentsManager = $this->createMock(ICommentsManager::class);
		$notificationManager = $this->createMock(INotificationManager::class);

		if ($result) {
			$config->expects($this->once())
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

		$this->overwriteService('NotificationManager', $notificationManager);
		$this->overwriteService('CommentsManager', $commentsManager);
		$this->overwriteService('AllConfig', $config);

		$this->assertSame($result, $user->delete());

		$this->restoreService('AllConfig');
		$this->restoreService('CommentsManager');
		$this->restoreService('NotificationManager');

		$this->assertEquals($expectedHooks, $hooksCalled);
	}

	public function testGetCloudId() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$urlGenerator = $this->getMockBuilder('\OC\URLGenerator')
				->setMethods(['getAbsoluteURL'])
				->disableOriginalConstructor()->getMock();
		$urlGenerator
				->expects($this->any())
				->method('getAbsoluteURL')
				->withAnyParameters()
				->willReturn('http://localhost:8888/owncloud');
		$user = new \OC\User\User('foo', $backend, null, null, $urlGenerator);
		$this->assertEquals("foo@localhost:8888/owncloud", $user->getCloudId());
	}

	public function testSetEMailAddressEmpty() {
		/**
		 * @var Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$test = $this;
		$hooksCalled = 0;

		/**
		 * @param IUser $user
		 * @param string $feature
		 * @param string $value
		 */
		$hook = function (IUser $user, $feature, $value) use ($test, &$hooksCalled) {
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

		$user = new User('foo', $backend, $emitter, $config);
		$user->setEMailAddress('');
	}

	public function testSetEMailAddress() {
		/**
		 * @var UserInterface | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$test = $this;
		$hooksCalled = 0;

		/**
		 * @param IUser $user
		 * @param string $feature
		 * @param string $value
		 */
		$hook = function (IUser $user, $feature, $value) use ($test, &$hooksCalled) {
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

		$user = new User('foo', $backend, $emitter, $config);
		$user->setEMailAddress('foo@bar.com');
	}

	public function testSetEMailAddressNoChange() {
		/**
		 * @var UserInterface | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		/** @var PublicEmitter|\PHPUnit_Framework_MockObject_MockObject $emitter */
		$emitter = $this->createMock(PublicEmitter::class);
		$emitter->expects($this->never())
			->method('emit');

		$config = $this->createMock(IConfig::class);
		$config->expects($this->any())
			->method('getUserValue')
			->willReturn('foo@bar.com');
		$config->expects($this->once())
			->method('setUserValue')
			->with(
				'foo',
				'settings',
				'email',
				'foo@bar.com'
			);

		$user = new User('foo', $backend, $emitter, $config);
		$user->setEMailAddress('foo@bar.com');
	}

	public function testSetQuota() {
		/**
		 * @var UserInterface | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$test = $this;
		$hooksCalled = 0;

		/**
		 * @param IUser $user
		 * @param string $feature
		 * @param string $value
		 */
		$hook = function (IUser $user, $feature, $value) use ($test, &$hooksCalled) {
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

		$user = new User('foo', $backend, $emitter, $config);
		$user->setQuota('23 TB');
	}

	public function testSetQuotaAddressNoChange() {
		/**
		 * @var UserInterface | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		/** @var PublicEmitter|\PHPUnit_Framework_MockObject_MockObject $emitter */
		$emitter = $this->createMock(PublicEmitter::class);
		$emitter->expects($this->never())
			->method('emit');

		$config = $this->createMock(IConfig::class);
		$config->expects($this->any())
			->method('getUserValue')
			->willReturn('23 TB');
		$config->expects($this->once())
			->method('setUserValue')
			->with(
				'foo',
				'files',
				'quota',
				'23 TB'
			);

		$user = new User('foo', $backend, $emitter, $config);
		$user->setQuota('23 TB');
	}
}
