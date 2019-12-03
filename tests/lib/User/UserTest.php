<?php declare(strict_types=1);

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\User;

use OC\User\User;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\User\Events\BeforePasswordUpdatedEvent;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserDeletedEvent;
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
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
	}

	public function testDisplayName() {
		/**
		 * @var \OC\User\Backend|MockObject $backend
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

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertEquals('Foo', $user->getDisplayName());
	}

	/**
	 * if the display name contain whitespaces only, we expect the uid as result
	 */
	public function testDisplayNameEmpty() {
		/**
		 * @var \OC\User\UserInterface|MockObject $backend
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

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testDisplayNameNotSupported() {
		/**
		 * @var \OC\User\UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Backend::class);
		$backend->expects($this->never())
			->method('getDisplayName');

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC\User\Backend::GET_DISPLAYNAME))
			->will($this->returnValue(false));

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testSetPassword() {
		/**
		 * @var UserInterface|MockObject $backend
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

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->setPassword('bar',''));
	}

	public function testSetPasswordNotSupported() {
		/**
		 * @var UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('setPassword');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->setPassword('bar',''));
	}

	public function testChangeAvatarSupportedYes() {
		/**
		 * @var UserInterface|MockObject $backend
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

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->canChangeAvatar());
	}

	public function testChangeAvatarSupportedNo() {
		/**
		 * @var UserInterface|MockObject $backend
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

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->canChangeAvatar());
	}

	public function testChangeAvatarNotSupported() {
		/**
		 * @var UserInterface|MockObject $backend
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

	public function testDelete() {
		/**
		 * @var UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('deleteUser')
			->with($this->equalTo('foo'));

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->delete());
	}

	public function testDeleteWithDifferentHome() {
		/**
		 * @var UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->at(0))
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::GET_HOME) {
					return true;
				} else {
					return false;
				}
			}));

		// important: getHome MUST be called before deleteUser because
		// once the user is deleted, getHome implementations might not
		// return anything
		$backend->expects($this->at(1))
			->method('getHome')
			->with($this->equalTo('foo'))
			->will($this->returnValue('/home/foo'));

		$backend->expects($this->at(2))
			->method('deleteUser')
			->with($this->equalTo('foo'));

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->delete());
	}

	public function testGetHome() {
		/**
		 * @var UserInterface|MockObject $backend
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

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertEquals('/home/foo', $user->getHome());
	}

	public function testGetBackendClassName() {
		$user = new User('foo', new \Test\Util\User\Dummy(), $this->dispatcher);
		$this->assertEquals('Dummy', $user->getBackendClassName());
		$user = new User('foo', new \OC\User\Database(), $this->dispatcher);
		$this->assertEquals('Database', $user->getBackendClassName());
	}

	public function testGetHomeNotSupported() {
		/**
		 * @var UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->never())
			->method('getHome');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$allConfig = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$allConfig->expects($this->any())
			->method('getUserValue')
			->will($this->returnValue(true));
		$allConfig->expects($this->any())
			->method('getSystemValue')
			->with($this->equalTo('datadirectory'))
			->will($this->returnValue('arbitrary/path'));

		$user = new User('foo', $backend, $this->dispatcher, $allConfig);
		$this->assertEquals('arbitrary/path/foo', $user->getHome());
	}

	public function testCanChangePassword() {
		/**
		 * @var UserInterface|MockObject $backend
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

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->canChangePassword());
	}

	public function testCanChangePasswordNotSupported() {
		/**
		 * @var UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->canChangePassword());
	}

	public function testCanChangeDisplayName() {
		/**
		 * @var UserInterface|MockObject $backend
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

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->canChangeDisplayName());
	}

	public function testCanChangeDisplayNameNotSupported() {
		/**
		 * @var UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->canChangeDisplayName());
	}

	public function testSetDisplayNameSupported() {
		/**
		 * @var UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Database::class);

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

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertTrue($user->setDisplayName('Foo'));
		$this->assertEquals('Foo',$user->getDisplayName());
	}

	/**
	 * don't allow display names containing whitespaces only
	 */
	public function testSetDisplayNameEmpty() {
		/**
		 * @var UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Database::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\User\Backend::SET_DISPLAYNAME) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->setDisplayName(' '));
		$this->assertEquals('foo',$user->getDisplayName());
	}

	public function testSetDisplayNameNotSupported() {
		/**
		 * @var UserInterface|MockObject $backend
		 */
		$backend = $this->createMock(\OC\User\Database::class);

		$backend->expects($this->any())
			->method('implementsActions')
			->willReturn(false);

		$backend->expects($this->never())
			->method('setDisplayName');

		$user = new User('foo', $backend, $this->dispatcher);
		$this->assertFalse($user->setDisplayName('Foo'));
		$this->assertEquals('foo',$user->getDisplayName());
	}

	public function testSetPasswordEvents() {
		/**
		 * @var Backend|MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('setPassword');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				return $actions === \OC\User\Backend::SET_PASSWORD;
			}));
		$user = new User('foo', $backend, $this->dispatcher);
		$expectedEvent1 = new BeforePasswordUpdatedEvent($user, 'bar');
		$this->dispatcher->expects($this->at(0))
			->method('dispatchTyped')
			->with($this->equalTo($expectedEvent1));
		$expectedEvent2 = new PasswordUpdatedEvent($user, 'bar', '');
		$this->dispatcher->expects($this->at(1))
			->method('dispatchTyped')
			->with($this->equalTo($expectedEvent2));

		$user->setPassword('bar','');
	}

	public function dataDeleteHooks() {
		return [
			[true, true],
			[false, false],
		];
	}

	/**
	 * @dataProvider dataDeleteHooks
	 *
	 * @param bool $result
	 * @param bool $expectDeletedEvent
	 */
	public function testDeleteEvents(bool $result, bool $expectDeletedEvent) {
		/**
		 * @var Backend|MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$backend->expects($this->once())
			->method('deleteUser')
			->willReturn($result);
		$config = $this->createMock(IConfig::class);
		$commentsManager = $this->createMock(ICommentsManager::class);
		$notificationManager = $this->createMock(INotificationManager::class);

		$user = new User('foo', $backend, $this->dispatcher);
		$this->dispatcher->expects($this->at(0))
			->method('dispatchTyped')
			->with($this->equalTo(new BeforeUserDeletedEvent($user)));
		if ($expectDeletedEvent) {
			$this->dispatcher->expects($this->at(1))
				->method('dispatchTyped')
				->with($this->equalTo(new UserDeletedEvent($user)));
		} else {
			$this->dispatcher->expects($this->atMost(1))
				->method('dispatchTyped');
		}

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
	}

	public function testGetCloudId() {
		/** @var UserInterface|MockObject $backend */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$urlGenerator = $this->getMockBuilder('\OC\URLGenerator')
				->setMethods(['getAbsoluteURL'])
				->disableOriginalConstructor()->getMock();
		$urlGenerator
				->expects($this->any())
				->method('getAbsoluteURL')
				->withAnyParameters()
				->willReturn('http://localhost:8888/owncloud');
		$user = new User('foo', $backend, $this->dispatcher, null, $urlGenerator);
		$this->assertEquals('foo@localhost:8888/owncloud', $user->getCloudId());
	}

	public function testSetEMailAddressEmpty() {
		/** @var UserInterface|MockObject $backend */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('deleteUserValue')
			->with(
				'foo',
				'settings',
				'email'
			);
		$user = new User('foo', $backend, $this->dispatcher, $config);
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new UserChangedEvent($user, 'eMailAddress', ''));

		$user->setEMailAddress('');
	}

	public function testSetEMailAddress() {
		/** @var UserInterface|MockObject $backend */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('setUserValue')
			->with(
				'foo',
				'settings',
				'email',
				'foo@bar.com'
			);
		$user = new User('foo', $backend, $this->dispatcher, $config);
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new UserChangedEvent($user, 'eMailAddress', 'foo@bar.com'));

		$user->setEMailAddress('foo@bar.com');
	}

	public function testSetEMailAddressNoChange() {
		/**
		 * @var UserInterface | MockObject $backend
		 */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->any())
			->method('getUserValue')
			->willReturn('foo@bar.com');
		$config->expects($this->never())
			->method('setUserValue');
		$user = new User('foo', $backend, $this->dispatcher, $config);
		$this->dispatcher->expects($this->never())
			->method('dispatchTyped');

		$user->setEMailAddress('foo@bar.com');
	}

	public function testSetQuota() {
		/** @var UserInterface | MockObject $backend */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('setUserValue')
			->with(
				'foo',
				'files',
				'quota',
				'23 TB'
			);
		$user = new User('foo', $backend, $this->dispatcher, $config);
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new UserChangedEvent($user, 'quota', '23 TB'));

		$user->setQuota('23 TB');
	}

	public function testSetQuotaAddressNoChange() {
		/** @var UserInterface|MockObject $backend */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->expects($this->any())
			->method('getUserValue')
			->willReturn('23 TB');
		$config->expects($this->never())
			->method('setUserValue');
		$user = new User('foo', $backend, $this->dispatcher, $config);
		$this->dispatcher->expects($this->never())
			->method('dispatchTyped');

		$user->setQuota('23 TB');
	}

	public function testGetLastLogin() {
		/** @var UserInterface|MockObject $backend */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->method('getUserValue')
			->will($this->returnCallback(function ($uid, $app, $key, $default) {
				if ($uid === 'foo' && $app === 'login' && $key === 'lastLogin') {
					return 42;
				}

				return $default;
			}));

		$user = new User('foo', $backend, $this->dispatcher, $config);
		$this->assertSame(42, $user->getLastLogin());
	}

	public function testSetEnabled() {
		/** @var UserInterface|MockObject $backend */
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

		$user = new User('foo', $backend, $this->dispatcher, $config);
		$user->setEnabled(true);
	}

	public function testSetDisabled() {
		/** @var UserInterface|MockObject $backend */
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
				$config,
			])
			->setMethods(['isEnabled', 'triggerChange'])
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

	public function testSetDisabledAlreadyDisabled() {
		/**
		 * @var UserInterface|MockObject $backend
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
				$config,
			])
			->setMethods(['isEnabled', 'triggerChange'])
			->getMock();

		$user->expects($this->once())
			->method('isEnabled')
			->willReturn(false);
		$user->expects($this->never())
			->method('triggerChange');

		$user->setEnabled(false);
	}

	public function testGetEMailAddress() {
		/** @var UserInterface|MockObject $backend */
		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$config = $this->createMock(IConfig::class);
		$config->method('getUserValue')
			->will($this->returnCallback(function ($uid, $app, $key, $default) {
				if ($uid === 'foo' && $app === 'settings' && $key === 'email') {
					return 'foo@bar.com';
				} else {
					return $default;
				}
			}));

		$user = new User('foo', $backend, $this->dispatcher, $config);
		$this->assertSame('foo@bar.com', $user->getEMailAddress());
	}
}
