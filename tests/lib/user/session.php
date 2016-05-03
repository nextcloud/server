<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\User;

use OC\Session\Memory;
use OC\User\User;
use OCP\ISession;
use OCP\IUserManager;
use OCP\UserInterface;
use Test\TestCase;

/**
 * @group DB
 * @package Test\User
 */
class Session extends TestCase {
	public function testGetUser() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->once())
			->method('get')
			->with('user_id')
			->will($this->returnValue('foo'));

		/** @var UserInterface | \PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->once())
			->method('userExists')
			->with('foo')
			->will($this->returnValue(true));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$userSession = new \OC\User\Session($manager, $session);
		$user = $userSession->getUser();
		$this->assertEquals('foo', $user->getUID());
	}

	public function testIsLoggedIn() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->once())
			->method('get')
			->with('user_id')
			->will($this->returnValue('foo'));

		/** @var UserInterface | \PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->once())
			->method('userExists')
			->with('foo')
			->will($this->returnValue(true));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$userSession = new \OC\User\Session($manager, $session);
		$isLoggedIn = $userSession->isLoggedIn();
		$this->assertTrue($isLoggedIn);
	}

	public function testNotLoggedIn() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->once())
			->method('get')
			->with('user_id')
			->will($this->returnValue(null));

		/** @var UserInterface | \PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->never())
			->method('userExists');

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$userSession = new \OC\User\Session($manager, $session);
		$isLoggedIn = $userSession->isLoggedIn();
		$this->assertFalse($isLoggedIn);
	}

	public function testSetUser() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->once())
			->method('set')
			->with('user_id', 'foo');

		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $manager */
		$manager = $this->getMock('\OC\User\Manager');

		$backend = $this->getMock('\Test\Util\User\Dummy');

		/** @var User | \PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('foo'));

		$userSession = new \OC\User\Session($manager, $session);
		$userSession->setUser($user);
	}

	public function testLoginValidPasswordEnabled() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->once())
			->method('regenerateId');
		$session->expects($this->exactly(2))
			->method('set')
			->with($this->callback(function ($key) {
					switch ($key) {
						case 'user_id':
						case 'loginname':
							return true;
							break;
						default:
							return false;
							break;
					}
				},
				'foo'));

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach ($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if ($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $manager */
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('\Test\Util\User\Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->exactly(2))
			->method('isEnabled')
			->will($this->returnValue(true));
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('foo'));
		$user->expects($this->once())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('checkPassword')
			->with('foo', 'bar')
			->will($this->returnValue($user));

		$userSession = new \OC\User\Session($manager, $session);
		$userSession->login('foo', 'bar');
		$this->assertEquals($user, $userSession->getUser());
	}

	/**
	 * @expectedException \OC\User\LoginException
	 * @expectedExceptionMessage User disabled
	 */
	public function testLoginValidPasswordDisabled() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
				->method('regenerateId');

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach ($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if ($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $manager */
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('\Test\Util\User\Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(false));
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('checkPassword')
			->with('foo', 'bar')
			->will($this->returnValue($user));

		$userSession = new \OC\User\Session($manager, $session);
		$userSession->login('foo', 'bar');
	}

	public function testLoginInvalidPassword() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
				->method('regenerateId');

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach ($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if ($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $manager */
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('\Test\Util\User\Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->never())
			->method('isEnabled');
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('checkPassword')
			->with('foo', 'bar')
			->will($this->returnValue(false));

		$userSession = new \OC\User\Session($manager, $session);
		$userSession->login('foo', 'bar');
	}

	public function testLoginNonExisting() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
				->method('regenerateId');

		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $manager */
		$manager = $this->getMock('\OC\User\Manager');

		$manager->expects($this->once())
			->method('checkPassword')
			->with('foo', 'bar')
			->will($this->returnValue(false));

		$userSession = new \OC\User\Session($manager, $session);
		$userSession->login('foo', 'bar');
	}

	public function testRememberLoginValidToken() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->exactly(1))
			->method('set')
			->with($this->callback(function ($key) {
					switch ($key) {
						case 'user_id':
							return true;
						default:
							return false;
					}
				},
				'foo'));
		$session->expects($this->once())
				->method('regenerateId');

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach ($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if ($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('\Test\Util\User\Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));

		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('foo'));
		$user->expects($this->once())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($user));

		//prepare login token
		$token = 'goodToken';
		\OC::$server->getConfig()->setUserValue('foo', 'login_token', $token, time());

		/** @var \OC\User\Session $userSession */
		$userSession = $this->getMock(
			'\OC\User\Session',
			//override, otherwise tests will fail because of setcookie()
			array('setMagicInCookie'),
			//there  are passed as parameters to the constructor
			array($manager, $session));

		$granted = $userSession->loginWithCookie('foo', $token);

		$this->assertSame($granted, true);
	}

	public function testRememberLoginInvalidToken() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
				->method('regenerateId');

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach ($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if ($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $manager */
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('\Test\Util\User\Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));

		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('foo'));
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($user));

		//prepare login token
		$token = 'goodToken';
		\OC::$server->getConfig()->setUserValue('foo', 'login_token', $token, time());

		$userSession = new \OC\User\Session($manager, $session);
		$granted = $userSession->loginWithCookie('foo', 'badToken');

		$this->assertSame($granted, false);
	}

	public function testRememberLoginInvalidUser() {
		/** @var ISession | \PHPUnit_Framework_MockObject_MockObject $session */
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
				->method('regenerateId');

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach ($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if ($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $manager */
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('\Test\Util\User\Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));

		$user->expects($this->never())
			->method('getUID');
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue(null));

		//prepare login token
		$token = 'goodToken';
		\OC::$server->getConfig()->setUserValue('foo', 'login_token', $token, time());

		$userSession = new \OC\User\Session($manager, $session);
		$granted = $userSession->loginWithCookie('foo', $token);

		$this->assertSame($granted, false);
	}

	public function testActiveUserAfterSetSession() {
		$users = array(
			'foo' => new User('foo', null),
			'bar' => new User('bar', null)
		);

		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $manager */
		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();

		$manager->expects($this->any())
			->method('get')
			->will($this->returnCallback(function ($uid) use ($users) {
				return $users[$uid];
			}));

		$session = new Memory('');
		$session->set('user_id', 'foo');
		$userSession = new \OC\User\Session($manager, $session);
		$this->assertEquals($users['foo'], $userSession->getUser());

		$session2 = new Memory('');
		$session2->set('user_id', 'bar');
		$userSession->setSession($session2);
		$this->assertEquals($users['bar'], $userSession->getUser());
	}
}
