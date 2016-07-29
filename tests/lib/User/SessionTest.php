<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\User;

use OC\Security\Bruteforce\Throttler;
use OC\Session\Memory;
use OC\User\User;

/**
 * @group DB
 * @package Test\User
 */
class SessionTest extends \Test\TestCase {
	/** @var \OCP\AppFramework\Utility\ITimeFactory */
	private $timeFactory;
	/** @var \OC\Authentication\Token\DefaultTokenProvider */
	protected $tokenProvider;
	/** @var \OCP\IConfig */
	private $config;
	/** @var Throttler */
	private $throttler;

	protected function setUp() {
		parent::setUp();

		$this->timeFactory = $this->getMock('\OCP\AppFramework\Utility\ITimeFactory');
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->will($this->returnValue(10000));
		$this->tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$this->config = $this->getMock('\OCP\IConfig');
		$this->throttler = $this->getMockBuilder('\OC\Security\Bruteforce\Throttler')
			->disableOriginalConstructor()->getMock();
	}

	public function testGetUser() {
		$token = new \OC\Authentication\Token\DefaultToken();
		$token->setLoginName('User123');
		$token->setLastCheck(200);

		$expectedUser = $this->getMock('\OCP\IUser');
		$expectedUser->expects($this->any())
			->method('getUID')
			->will($this->returnValue('user123'));
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->at(0))
			->method('get')
			->with('user_id')
			->will($this->returnValue($expectedUser->getUID()));
		$sessionId = 'abcdef12345';

		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$session->expects($this->at(1))
			->method('get')
			->with('app_password')
			->will($this->returnValue(null)); // No password set -> browser session
		$session->expects($this->once())
			->method('getId')
			->will($this->returnValue($sessionId));
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($sessionId)
			->will($this->returnValue($token));
		$this->tokenProvider->expects($this->once())
			->method('getPassword')
			->with($token, $sessionId)
			->will($this->returnValue('passme'));
		$manager->expects($this->once())
			->method('checkPassword')
			->with('User123', 'passme')
			->will($this->returnValue(true));
		$expectedUser->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(true));

		$this->tokenProvider->expects($this->once())
			->method('updateTokenActivity')
			->with($token);

		$manager->expects($this->any())
			->method('get')
			->with($expectedUser->getUID())
			->will($this->returnValue($expectedUser));

		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);
		$user = $userSession->getUser();
		$this->assertSame($expectedUser, $user);
		$this->assertSame(10000, $token->getLastCheck());
	}

	public function isLoggedInData() {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider isLoggedInData
	 */
	public function testIsLoggedIn($isLoggedIn) {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));

		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();

		$userSession = $this->getMockBuilder('\OC\User\Session')
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config])
			->setMethods([
				'getUser'
			])
			->getMock();
		$user = new User('sepp', null);
		$userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($isLoggedIn ? $user : null));
		$this->assertEquals($isLoggedIn, $userSession->isLoggedIn());
	}

	public function testSetUser() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->once())
			->method('set')
			->with('user_id', 'foo');

		$manager = $this->getMock('\OC\User\Manager');

		$backend = $this->getMock('\Test\Util\User\Dummy');

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('foo'));

		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);
		$userSession->setUser($user);
	}

	public function testLoginValidPasswordEnabled() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->once())
			->method('regenerateId');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('bar')
			->will($this->throwException(new \OC\Authentication\Exceptions\InvalidTokenException()));
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
				}, 'foo'));

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

		$userSession = $this->getMockBuilder('\OC\User\Session')
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config])
			->setMethods([
				'prepareUserLogin'
			])
			->getMock();
		$userSession->expects($this->once())
			->method('prepareUserLogin');
		$userSession->login('foo', 'bar');
		$this->assertEquals($user, $userSession->getUser());
	}

	/**
	 * @expectedException \OC\User\LoginException
	 */
	public function testLoginValidPasswordDisabled() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
			->method('regenerateId');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('bar')
			->will($this->throwException(new \OC\Authentication\Exceptions\InvalidTokenException()));

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
			->method('isEnabled')
			->will($this->returnValue(false));
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('checkPassword')
			->with('foo', 'bar')
			->will($this->returnValue($user));

		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);
		$userSession->login('foo', 'bar');
	}

	public function testLoginInvalidPassword() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
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
		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);

		$user = $this->getMock('\OC\User\User', array(), array('foo', $backend));

		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
			->method('regenerateId');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('bar')
			->will($this->throwException(new \OC\Authentication\Exceptions\InvalidTokenException()));

		$user->expects($this->never())
			->method('isEnabled');
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('checkPassword')
			->with('foo', 'bar')
			->will($this->returnValue(false));

		$userSession->login('foo', 'bar');
	}

	public function testLoginNonExisting() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$manager = $this->getMock('\OC\User\Manager');
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);

		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
			->method('regenerateId');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('bar')
			->will($this->throwException(new \OC\Authentication\Exceptions\InvalidTokenException()));

		$manager->expects($this->once())
			->method('checkPassword')
			->with('foo', 'bar')
			->will($this->returnValue(false));

		$userSession->login('foo', 'bar');
	}

	/**
	 * When using a device token, the loginname must match the one that was used
	 * when generating the token on the browser.
	 */
	public function testLoginWithDifferentTokenLoginName() {
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$manager = $this->getMock('\OC\User\Manager');
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);
		$username = 'user123';
		$token = new \OC\Authentication\Token\DefaultToken();
		$token->setLoginName($username);

		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
			->method('regenerateId');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('bar')
			->will($this->returnValue($token));

		$manager->expects($this->once())
			->method('checkPassword')
			->with('foo', 'bar')
			->will($this->returnValue(false));

		$userSession->login('foo', 'bar');
	}

	/**
	 * @expectedException \OC\Authentication\Exceptions\PasswordLoginForbiddenException
	 */
	public function testLogClientInNoTokenPasswordWith2fa() {
		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$session = $this->getMock('\OCP\ISession');
		$request = $this->getMock('\OCP\IRequest');

		/** @var \OC\User\Session $userSession */
		$userSession = $this->getMockBuilder('\OC\User\Session')
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config])
			->setMethods(['login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('doe')
			->will($this->throwException(new \OC\Authentication\Exceptions\InvalidTokenException()));
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('token_auth_enforced', false)
			->will($this->returnValue(true));
		$request
			->expects($this->exactly(2))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(0);

		$userSession->logClientIn('john', 'doe', $request, $this->throttler);
	}

	public function testLogClientInWithTokenPassword() {
		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$session = $this->getMock('\OCP\ISession');
		$request = $this->getMock('\OCP\IRequest');

		/** @var \OC\User\Session $userSession */
		$userSession = $this->getMockBuilder('\OC\User\Session')
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config])
			->setMethods(['isTokenPassword', 'login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$userSession->expects($this->once())
			->method('isTokenPassword')
			->will($this->returnValue(true));
		$userSession->expects($this->once())
			->method('login')
			->with('john', 'I-AM-AN-APP-PASSWORD')
			->will($this->returnValue(true));

		$session->expects($this->once())
			->method('set')
			->with('app_password', 'I-AM-AN-APP-PASSWORD');
		$request
			->expects($this->exactly(2))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(0);

		$this->assertTrue($userSession->logClientIn('john', 'I-AM-AN-APP-PASSWORD', $request, $this->throttler));
	}

	/**
	 * @expectedException \OC\Authentication\Exceptions\PasswordLoginForbiddenException
	 */
	public function testLogClientInNoTokenPasswordNo2fa() {
		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$session = $this->getMock('\OCP\ISession');
		$request = $this->getMock('\OCP\IRequest');

		/** @var \OC\User\Session $userSession */
		$userSession = $this->getMockBuilder('\OC\User\Session')
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config])
			->setMethods(['login', 'isTwoFactorEnforced'])
			->getMock();

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('doe')
			->will($this->throwException(new \OC\Authentication\Exceptions\InvalidTokenException()));
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('token_auth_enforced', false)
			->will($this->returnValue(false));

		$userSession->expects($this->once())
			->method('isTwoFactorEnforced')
			->with('john')
			->will($this->returnValue(true));

		$request
			->expects($this->exactly(2))
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelay')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(0);

		$userSession->logClientIn('john', 'doe', $request, $this->throttler);
	}

	public function testRememberLoginValidToken() {
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
				}, 'foo'));
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

		$userSession = $this->getMock(
			'\OC\User\Session',
			//override, otherwise tests will fail because of setcookie()
			array('setMagicInCookie'),
			//there  are passed as parameters to the constructor
			array($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config));

		$granted = $userSession->loginWithCookie('foo', $token);

		$this->assertSame($granted, true);
	}

	public function testRememberLoginInvalidToken() {
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

		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);
		$granted = $userSession->loginWithCookie('foo', 'badToken');

		$this->assertSame($granted, false);
	}

	public function testRememberLoginInvalidUser() {
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

		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);
		$granted = $userSession->loginWithCookie('foo', $token);

		$this->assertSame($granted, false);
	}

	public function testActiveUserAfterSetSession() {
		$users = array(
			'foo' => new User('foo', null),
			'bar' => new User('bar', null)
		);

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
		$userSession = $this->getMockBuilder('\OC\User\Session')
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config])
			->setMethods([
				'validateSession'
			])
			->getMock();
		$userSession->expects($this->any())
			->method('validateSession');

		$this->assertEquals($users['foo'], $userSession->getUser());

		$session2 = new Memory('');
		$session2->set('user_id', 'bar');
		$userSession->setSession($session2);
		$this->assertEquals($users['bar'], $userSession->getUser());
	}

	public function testCreateSessionToken() {
		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$session = $this->getMock('\OCP\ISession');
		$token = $this->getMock('\OC\Authentication\Token\IToken');
		$user = $this->getMock('\OCP\IUser');
		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);

		$random = $this->getMock('\OCP\Security\ISecureRandom');
		$config = $this->getMock('\OCP\IConfig');
		$csrf = $this->getMockBuilder('\OC\Security\CSRF\CsrfTokenManager')
			->disableOriginalConstructor()
			->getMock();
		$request = new \OC\AppFramework\Http\Request([
			'server' => [
				'HTTP_USER_AGENT' => 'Firefox',
			]
		], $random, $config, $csrf);

		$uid = 'user123';
		$loginName = 'User123';
		$password = 'passme';
		$sessionId = 'abcxyz';

		$manager->expects($this->once())
			->method('get')
			->with($uid)
			->will($this->returnValue($user));
		$session->expects($this->once())
			->method('getId')
			->will($this->returnValue($sessionId));
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($password)
			->will($this->throwException(new \OC\Authentication\Exceptions\InvalidTokenException()));
		
		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with($sessionId, $uid, $loginName, $password, 'Firefox');

		$this->assertTrue($userSession->createSessionToken($request, $uid, $loginName, $password));
	}

	public function testCreateSessionTokenWithTokenPassword() {
		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$session = $this->getMock('\OCP\ISession');
		$token = $this->getMock('\OC\Authentication\Token\IToken');
		$user = $this->getMock('\OCP\IUser');
		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);

		$random = $this->getMock('\OCP\Security\ISecureRandom');
		$config = $this->getMock('\OCP\IConfig');
		$csrf = $this->getMockBuilder('\OC\Security\CSRF\CsrfTokenManager')
			->disableOriginalConstructor()
			->getMock();
		$request = new \OC\AppFramework\Http\Request([
			'server' => [
				'HTTP_USER_AGENT' => 'Firefox',
			]
		], $random, $config, $csrf);

		$uid = 'user123';
		$loginName = 'User123';
		$password = 'iamatoken';
		$realPassword = 'passme';
		$sessionId = 'abcxyz';

		$manager->expects($this->once())
			->method('get')
			->with($uid)
			->will($this->returnValue($user));
		$session->expects($this->once())
			->method('getId')
			->will($this->returnValue($sessionId));
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($password)
			->will($this->returnValue($token));
		$this->tokenProvider->expects($this->once())
			->method('getPassword')
			->with($token, $password)
			->will($this->returnValue($realPassword));
		
		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with($sessionId, $uid, $loginName, $realPassword, 'Firefox');

		$this->assertTrue($userSession->createSessionToken($request, $uid, $loginName, $password));
	}

	public function testCreateSessionTokenWithNonExistentUser() {
		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$session = $this->getMock('\OCP\ISession');
		$userSession = new \OC\User\Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config);
		$request = $this->getMock('\OCP\IRequest');

		$uid = 'user123';
		$loginName = 'User123';
		$password = 'passme';

		$manager->expects($this->once())
			->method('get')
			->with($uid)
			->will($this->returnValue(null));
		
		$this->assertFalse($userSession->createSessionToken($request, $uid, $loginName, $password));
	}

	/**
	 * @expectedException \OC\User\LoginException
	 */
	public function testTryTokenLoginWithDisabledUser() {
		$manager = $this->getMockBuilder('\OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
		$session = new Memory('');
		$token = new \OC\Authentication\Token\DefaultToken();
		$token->setLoginName('fritz');
		$token->setUid('fritz0');
		$token->setLastCheck(100); // Needs check
		$user = $this->getMock('\OCP\IUser');
		$userSession = $this->getMockBuilder('\OC\User\Session')
			->setMethods(['logout'])
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config])
			->getMock();
		$request = $this->getMock('\OCP\IRequest');

		$request->expects($this->once())
			->method('getHeader')
			->with('Authorization')
			->will($this->returnValue('token xxxxx'));
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('xxxxx')
			->will($this->returnValue($token));
		$manager->expects($this->once())
			->method('get')
			->with('fritz0')
			->will($this->returnValue($user));
		$user->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(false));

		$userSession->tryTokenLogin($request);
	}

	public function testValidateSessionDisabledUser() {
		$userManager = $this->getMock('\OCP\IUserManager');
		$session = $this->getMock('\OCP\ISession');
		$timeFactory = $this->getMock('\OCP\AppFramework\Utility\ITimeFactory');
		$tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$userSession = $this->getMockBuilder('\OC\User\Session')
			->setConstructorArgs([$userManager, $session, $timeFactory, $tokenProvider, $this->config])
			->setMethods(['logout'])
			->getMock();

		$user = $this->getMock('\OCP\IUser');
		$token = new \OC\Authentication\Token\DefaultToken();
		$token->setLoginName('susan');
		$token->setLastCheck(20);

		$session->expects($this->once())
			->method('get')
			->with('app_password')
			->will($this->returnValue('APP-PASSWORD'));
		$tokenProvider->expects($this->once())
			->method('getToken')
			->with('APP-PASSWORD')
			->will($this->returnValue($token));
		$timeFactory->expects($this->once())
			->method('getTime')
			->will($this->returnValue(1000)); // more than 5min since last check
		$tokenProvider->expects($this->once())
			->method('getPassword')
			->with($token, 'APP-PASSWORD')
			->will($this->returnValue('123456'));
		$userManager->expects($this->once())
			->method('checkPassword')
			->with('susan', '123456')
			->will($this->returnValue(true));
		$user->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(false));
		$tokenProvider->expects($this->once())
			->method('invalidateToken')
			->with('APP-PASSWORD');
		$userSession->expects($this->once())
			->method('logout');

		$userSession->setUser($user);
		$this->invokePrivate($userSession, 'validateSession');
	}

	public function testValidateSessionNoPassword() {
		$userManager = $this->getMock('\OCP\IUserManager');
		$session = $this->getMock('\OCP\ISession');
		$timeFactory = $this->getMock('\OCP\AppFramework\Utility\ITimeFactory');
		$tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$userSession = $this->getMockBuilder('\OC\User\Session')
			->setConstructorArgs([$userManager, $session, $timeFactory, $tokenProvider, $this->config])
			->setMethods(['logout'])
			->getMock();

		$user = $this->getMock('\OCP\IUser');
		$token = new \OC\Authentication\Token\DefaultToken();
		$token->setLastCheck(20);

		$session->expects($this->once())
			->method('get')
			->with('app_password')
			->will($this->returnValue('APP-PASSWORD'));
		$tokenProvider->expects($this->once())
			->method('getToken')
			->with('APP-PASSWORD')
			->will($this->returnValue($token));
		$timeFactory->expects($this->once())
			->method('getTime')
			->will($this->returnValue(1000)); // more than 5min since last check
		$tokenProvider->expects($this->once())
			->method('getPassword')
			->with($token, 'APP-PASSWORD')
			->will($this->throwException(new \OC\Authentication\Exceptions\PasswordlessTokenException()));
		$tokenProvider->expects($this->once())
			->method('updateToken')
			->with($token);

		$this->invokePrivate($userSession, 'validateSession', [$user]);

		$this->assertEquals(1000, $token->getLastCheck());
	}

	public function testUpdateSessionTokenPassword() {
		$userManager = $this->getMock('\OCP\IUserManager');
		$session = $this->getMock('\OCP\ISession');
		$timeFactory = $this->getMock('\OCP\AppFramework\Utility\ITimeFactory');
		$tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$userSession = new \OC\User\Session($userManager, $session, $timeFactory, $tokenProvider, $this->config);

		$password = '123456';
		$sessionId ='session1234';
		$token = new \OC\Authentication\Token\DefaultToken();

		$session->expects($this->once())
			->method('getId')
			->will($this->returnValue($sessionId));
		$tokenProvider->expects($this->once())
			->method('getToken')
			->with($sessionId)
			->will($this->returnValue($token));
		$tokenProvider->expects($this->once())
			->method('setPassword')
			->with($token, $sessionId, $password);

		$userSession->updateSessionTokenPassword($password);
	}

	public function testUpdateSessionTokenPasswordNoSessionAvailable() {
		$userManager = $this->getMock('\OCP\IUserManager');
		$session = $this->getMock('\OCP\ISession');
		$timeFactory = $this->getMock('\OCP\AppFramework\Utility\ITimeFactory');
		$tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$userSession = new \OC\User\Session($userManager, $session, $timeFactory, $tokenProvider, $this->config);

		$session->expects($this->once())
			->method('getId')
			->will($this->throwException(new \OCP\Session\Exceptions\SessionNotAvailableException()));

		$userSession->updateSessionTokenPassword('1234');
	}

	public function testUpdateSessionTokenPasswordInvalidTokenException() {
		$userManager = $this->getMock('\OCP\IUserManager');
		$session = $this->getMock('\OCP\ISession');
		$timeFactory = $this->getMock('\OCP\AppFramework\Utility\ITimeFactory');
		$tokenProvider = $this->getMock('\OC\Authentication\Token\IProvider');
		$userSession = new \OC\User\Session($userManager, $session, $timeFactory, $tokenProvider, $this->config);

		$password = '123456';
		$sessionId ='session1234';
		$token = new \OC\Authentication\Token\DefaultToken();

		$session->expects($this->once())
			->method('getId')
			->will($this->returnValue($sessionId));
		$tokenProvider->expects($this->once())
			->method('getToken')
			->with($sessionId)
			->will($this->returnValue($token));
		$tokenProvider->expects($this->once())
			->method('setPassword')
			->with($token, $sessionId, $password)
			->will($this->throwException(new \OC\Authentication\Exceptions\InvalidTokenException()));

		$userSession->updateSessionTokenPassword($password);
	}

}
