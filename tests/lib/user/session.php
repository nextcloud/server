<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\User;

class Session extends \PHPUnit_Framework_TestCase {
	public function testGetUser() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->once())
			->method('get')
			->with('user_id')
			->will($this->returnValue('foo'));

		$backend = $this->getMock('OC_User_Dummy');
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

	public function testSetUser() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->once())
			->method('set')
			->with('user_id', 'foo');

		$manager = $this->getMock('\OC\User\Manager');

		$backend = $this->getMock('OC_User_Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('foo'));

		$userSession = new \OC\User\Session($manager, $session);
		$userSession->setUser($user);
	}

	public function testLoginValidPasswordEnabled() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->exactly(2))
			->method('set')
			->with($this->callback(function($key) {
						switch($key) {
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
		foreach($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('OC_User_Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->once())
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

	public function testLoginValidPasswordDisabled() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('OC_User_Dummy');

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

	public function testLoginInValidPassword() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('OC_User_Dummy');

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
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');

		$manager = $this->getMock('\OC\User\Manager');

		$backend = $this->getMock('OC_User_Dummy');

		$manager->expects($this->once())
			->method('checkPassword')
			->with('foo', 'bar')
			->will($this->returnValue(false));

		$userSession = new \OC\User\Session($manager, $session);
		$userSession->login('foo', 'bar');
	}

	public function testRememberLoginValidToken() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->exactly(1))
			->method('set')
			->with($this->callback(function($key) {
						switch($key) {
							case 'user_id':
								return true;
							default:
								return false;
						}
					},
					'foo'));

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('OC_User_Dummy');

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
		\OC_Preferences::setValue('foo', 'login_token', $token, time());

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
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('OC_User_Dummy');

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
		\OC_Preferences::setValue('foo', 'login_token', $token, time());

		$userSession = new \OC\User\Session($manager, $session);
		$granted = $userSession->loginWithCookie('foo', 'badToken');

		$this->assertSame($granted, false);
	}

	public function testRememberLoginInvalidUser() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');

		$managerMethods = get_class_methods('\OC\User\Manager');
		//keep following methods intact in order to ensure hooks are
		//working
		$doNotMock = array('__construct', 'emit', 'listen');
		foreach($doNotMock as $methodName) {
			$i = array_search($methodName, $managerMethods, true);
			if($i !== false) {
				unset($managerMethods[$i]);
			}
		}
		$manager = $this->getMock('\OC\User\Manager', $managerMethods, array());

		$backend = $this->getMock('OC_User_Dummy');

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
		\OC_Preferences::setValue('foo', 'login_token', $token, time());

		$userSession = new \OC\User\Session($manager, $session);
		$granted = $userSession->loginWithCookie('foo', $token);

		$this->assertSame($granted, false);
	}
}
