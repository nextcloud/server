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
		$session->expects($this->once())
			->method('set')
			->with('user_id', 'foo');

		$manager = $this->getMock('\OC\User\Manager');

		$backend = $this->getMock('OC_User_Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(true));
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('foo'));

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

		$manager = $this->getMock('\OC\User\Manager');

		$backend = $this->getMock('OC_User_Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(false));

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

		$manager = $this->getMock('\OC\User\Manager');

		$backend = $this->getMock('OC_User_Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->never())
			->method('isEnabled');

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
}
