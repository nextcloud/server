<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\User;

use OC\AllConfig;
use OC\Hooks\PublicEmitter;

class User extends \PHPUnit_Framework_TestCase {
	public function testDisplayName() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Backend');
		$backend->expects($this->once())
			->method('getDisplayName')
			->with($this->equalTo('foo'))
			->will($this->returnValue('Foo'));

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC_USER_BACKEND_GET_DISPLAYNAME))
			->will($this->returnValue(true));

		$user = new \OC\User\User('foo', $backend);
		$this->assertEquals('Foo', $user->getDisplayName());
	}

	/**
	 * if the display name contain whitespaces only, we expect the uid as result
	 */
	public function testDisplayNameEmpty() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Backend');
		$backend->expects($this->once())
			->method('getDisplayName')
			->with($this->equalTo('foo'))
			->will($this->returnValue('  '));

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC_USER_BACKEND_GET_DISPLAYNAME))
			->will($this->returnValue(true));

		$user = new \OC\User\User('foo', $backend);
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testDisplayNameNotSupported() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Backend');
		$backend->expects($this->never())
			->method('getDisplayName');

		$backend->expects($this->any())
			->method('implementsActions')
			->with($this->equalTo(\OC_USER_BACKEND_GET_DISPLAYNAME))
			->will($this->returnValue(false));

		$user = new \OC\User\User('foo', $backend);
		$this->assertEquals('foo', $user->getDisplayName());
	}

	public function testSetPassword() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
		$backend->expects($this->once())
			->method('setPassword')
			->with($this->equalTo('foo'), $this->equalTo('bar'));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_SET_PASSWORD) {
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
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
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
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		require_once 'avataruserdummy.php';
		$backend = $this->getMock('Avatar_User_Dummy');
		$backend->expects($this->once())
			->method('canChangeAvatar')
			->with($this->equalTo('foo'))
			->will($this->returnValue(true));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_PROVIDE_AVATAR) {
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
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		require_once 'avataruserdummy.php';
		$backend = $this->getMock('Avatar_User_Dummy');
		$backend->expects($this->once())
			->method('canChangeAvatar')
			->with($this->equalTo('foo'))
			->will($this->returnValue(false));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_PROVIDE_AVATAR) {
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
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		require_once 'avataruserdummy.php';
		$backend = $this->getMock('Avatar_User_Dummy');
		$backend->expects($this->never())
			->method('canChangeAvatar');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
					return false;
			}));

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->canChangeAvatar());
	}

	public function testDelete() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
		$backend->expects($this->once())
			->method('deleteUser')
			->with($this->equalTo('foo'));

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->delete());
	}

	public function testGetHome() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
		$backend->expects($this->once())
			->method('getHome')
			->with($this->equalTo('foo'))
			->will($this->returnValue('/home/foo'));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_GET_HOME) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend);
		$this->assertEquals('/home/foo', $user->getHome());
	}

	public function testGetHomeNotSupported() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
		$backend->expects($this->never())
			->method('getHome');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$allConfig = new AllConfig();

		$user = new \OC\User\User('foo', $backend, null, $allConfig);
		$this->assertEquals(\OC_Config::getValue("datadirectory", \OC::$SERVERROOT . "/data") . '/foo', $user->getHome());
	}

	public function testCanChangePassword() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_SET_PASSWORD) {
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
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$user = new \OC\User\User('foo', $backend);
		$this->assertFalse($user->canChangePassword());
	}

	public function testCanChangeDisplayName() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_SET_DISPLAYNAME) {
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
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$user = new \OC\User\User('foo', $backend);
		$this->assertFalse($user->canChangeDisplayName());
	}

	public function testSetDisplayNameSupported() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Database');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_SET_DISPLAYNAME) {
					return true;
				} else {
					return false;
				}
			}));

		$backend->expects($this->once())
			->method('setDisplayName')
			->with('foo','Foo');

		$user = new \OC\User\User('foo', $backend);
		$this->assertTrue($user->setDisplayName('Foo'));
		$this->assertEquals('Foo',$user->getDisplayName());
	}

	/**
	 * don't allow display names containing whitespaces only
	 */
	public function testSetDisplayNameEmpty() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Database');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_SET_DISPLAYNAME) {
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
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Database');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
					return false;
			}));

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
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
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
				if ($actions === \OC_USER_BACKEND_SET_PASSWORD) {
					return true;
				} else {
					return false;
				}
			}));

		$user = new \OC\User\User('foo', $backend, $emitter);

		$user->setPassword('bar','');
		$this->assertEquals(2, $hooksCalled);
	}

	public function testDeleteHooks() {
		$hooksCalled = 0;
		$test = $this;

		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
		$backend->expects($this->once())
			->method('deleteUser');

		/**
		 * @param \OC\User\User $user
		 */
		$hook = function ($user) use ($test, &$hooksCalled) {
			$hooksCalled++;
			$test->assertEquals('foo', $user->getUID());
		};

		$emitter = new PublicEmitter();
		$emitter->listen('\OC\User', 'preDelete', $hook);
		$emitter->listen('\OC\User', 'postDelete', $hook);

		$user = new \OC\User\User('foo', $backend, $emitter);
		$this->assertTrue($user->delete());
		$this->assertEquals(2, $hooksCalled);
	}
}
