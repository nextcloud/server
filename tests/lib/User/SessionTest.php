<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User;

use OC\AppFramework\Http\Request;
use OC\Authentication\Events\LoginFailed;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Exceptions\PasswordLoginForbiddenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Authentication\Token\PublicKeyToken;
use OC\Security\CSRF\CsrfTokenManager;
use OC\Session\Memory;
use OC\User\LoginException;
use OC\User\Manager;
use OC\User\Session;
use OC\User\User;
use OCA\DAV\Connector\Sabre\Auth;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IRequestId;
use OCP\ISession;
use OCP\IUser;
use OCP\Lockdown\ILockdownManager;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ISecureRandom;
use OCP\User\Events\PostLoginEvent;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function array_diff;
use function get_class_methods;

/**
 * @group DB
 * @package Test\User
 */
class SessionTest extends \Test\TestCase {
	/** @var ITimeFactory|MockObject */
	private $timeFactory;
	/** @var IProvider|MockObject */
	private $tokenProvider;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IThrottler|MockObject */
	private $throttler;
	/** @var ISecureRandom|MockObject */
	private $random;
	/** @var Manager|MockObject */
	private $manager;
	/** @var ISession|MockObject */
	private $session;
	/** @var Session|MockObject */
	private $userSession;
	/** @var ILockdownManager|MockObject */
	private $lockdownManager;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var IEventDispatcher|MockObject */
	private $dispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(10000);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->config = $this->createMock(IConfig::class);
		$this->throttler = $this->createMock(IThrottler::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->manager = $this->createMock(Manager::class);
		$this->session = $this->createMock(ISession::class);
		$this->lockdownManager = $this->createMock(ILockdownManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([
				$this->manager,
				$this->session,
				$this->timeFactory,
				$this->tokenProvider,
				$this->config,
				$this->random,
				$this->lockdownManager,
				$this->logger,
				$this->dispatcher
			])
			->onlyMethods([
				'setMagicInCookie',
			])
			->getMock();

		\OC_User::setIncognitoMode(false);
	}

	public static function isLoggedInData(): array {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider isLoggedInData
	 */
	public function testIsLoggedIn($isLoggedIn): void {
		$session = $this->createMock(Memory::class);

		$manager = $this->createMock(Manager::class);

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->onlyMethods([
				'getUser'
			])
			->getMock();
		$user = new User('sepp', null, $this->createMock(IEventDispatcher::class));
		$userSession->expects($this->once())
			->method('getUser')
			->willReturn($isLoggedIn ? $user : null);
		$this->assertEquals($isLoggedIn, $userSession->isLoggedIn());
	}

	public function testSetUser(): void {
		$session = $this->createMock(Memory::class);
		$session->expects($this->once())
			->method('set')
			->with('user_id', 'foo');

		$manager = $this->createMock(Manager::class);

		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('foo');

		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);
		$userSession->setUser($user);
	}

	public function testLoginValidPasswordEnabled(): void {
		$session = $this->createMock(Memory::class);
		$session->expects($this->once())
			->method('regenerateId');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('bar')
			->willThrowException(new InvalidTokenException());
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

		$managerMethods = get_class_methods(Manager::class);
		//keep following methods intact in order to ensure hooks are working
		$mockedManagerMethods = array_diff($managerMethods, ['__construct', 'emit', 'listen']);
		$manager = $this->getMockBuilder(Manager::class)
			->onlyMethods($mockedManagerMethods)
			->setConstructorArgs([
				$this->config,
				$this->createMock(ICacheFactory::class),
				$this->createMock(IEventDispatcher::class),
				$this->createMock(LoggerInterface::class),
			])
			->getMock();

		$backend = $this->createMock(\Test\Util\User\Dummy::class);

		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('foo');
		$user->expects($this->once())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('checkPasswordNoLogging')
			->with('foo', 'bar')
			->willReturn($user);

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->onlyMethods([
				'prepareUserLogin'
			])
			->getMock();
		$userSession->expects($this->once())
			->method('prepareUserLogin');

		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->callback(function (PostLoginEvent $e) {
					return $e->getUser()->getUID() === 'foo' &&
						$e->getPassword() === 'bar' &&
						$e->isTokenLogin() === false;
				})
			);

		$userSession->login('foo', 'bar');
		$this->assertEquals($user, $userSession->getUser());
	}


	public function testLoginValidPasswordDisabled(): void {
		$this->expectException(LoginException::class);

		$session = $this->createMock(Memory::class);
		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
			->method('regenerateId');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('bar')
			->willThrowException(new InvalidTokenException());

		$managerMethods = get_class_methods(Manager::class);
		//keep following methods intact in order to ensure hooks are working
		$mockedManagerMethods = array_diff($managerMethods, ['__construct', 'emit', 'listen']);
		$manager = $this->getMockBuilder(Manager::class)
			->onlyMethods($mockedManagerMethods)
			->setConstructorArgs([
				$this->config,
				$this->createMock(ICacheFactory::class),
				$this->createMock(IEventDispatcher::class),
				$this->createMock(LoggerInterface::class),
			])
			->getMock();

		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('isEnabled')
			->willReturn(false);
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('checkPasswordNoLogging')
			->with('foo', 'bar')
			->willReturn($user);

		$this->dispatcher->expects($this->never())
			->method('dispatch');

		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);
		$userSession->login('foo', 'bar');
	}

	public function testLoginInvalidPassword(): void {
		$session = $this->createMock(Memory::class);
		$managerMethods = get_class_methods(Manager::class);
		//keep following methods intact in order to ensure hooks are working
		$mockedManagerMethods = array_diff($managerMethods, ['__construct', 'emit', 'listen']);
		$manager = $this->getMockBuilder(Manager::class)
			->onlyMethods($mockedManagerMethods)
			->setConstructorArgs([
				$this->config,
				$this->createMock(ICacheFactory::class),
				$this->createMock(IEventDispatcher::class),
				$this->createMock(LoggerInterface::class),
			])
			->getMock();
		$backend = $this->createMock(\Test\Util\User\Dummy::class);
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);

		$user = $this->createMock(IUser::class);

		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
			->method('regenerateId');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('bar')
			->willThrowException(new InvalidTokenException());

		$user->expects($this->never())
			->method('isEnabled');
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');

		$manager->expects($this->once())
			->method('checkPasswordNoLogging')
			->with('foo', 'bar')
			->willReturn(false);

		$this->dispatcher->expects($this->never())
			->method('dispatch');

		$userSession->login('foo', 'bar');
	}

	public function testPasswordlessLoginNoLastCheckUpdate(): void {
		$session = $this->createMock(Memory::class);
		$managerMethods = get_class_methods(Manager::class);
		// Keep following methods intact in order to ensure hooks are working
		$mockedManagerMethods = array_diff($managerMethods, ['__construct', 'emit', 'listen']);
		$manager = $this->getMockBuilder(Manager::class)
			->onlyMethods($mockedManagerMethods)
			->setConstructorArgs([
				$this->config,
				$this->createMock(ICacheFactory::class),
				$this->createMock(IEventDispatcher::class),
				$this->createMock(LoggerInterface::class),
			])
			->getMock();
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);

		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
			->method('regenerateId');
		$token = new PublicKeyToken();
		$token->setLoginName('foo');
		$token->setLastCheck(0); // Never
		$token->setUid('foo');
		$this->tokenProvider
			->method('getPassword')
			->with($token)
			->willThrowException(new PasswordlessTokenException());
		$this->tokenProvider
			->method('getToken')
			->with('app-password')
			->willReturn($token);
		$this->tokenProvider->expects(self::never())
			->method('updateToken');

		$userSession->login('foo', 'app-password');
	}

	public function testLoginLastCheckUpdate(): void {
		$session = $this->createMock(Memory::class);
		$managerMethods = get_class_methods(Manager::class);
		// Keep following methods intact in order to ensure hooks are working
		$mockedManagerMethods = array_diff($managerMethods, ['__construct', 'emit', 'listen']);
		$manager = $this->getMockBuilder(Manager::class)
			->onlyMethods($mockedManagerMethods)
			->setConstructorArgs([
				$this->config,
				$this->createMock(ICacheFactory::class),
				$this->createMock(IEventDispatcher::class),
				$this->createMock(LoggerInterface::class),
			])
			->getMock();
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);

		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
			->method('regenerateId');
		$token = new PublicKeyToken();
		$token->setLoginName('foo');
		$token->setLastCheck(0); // Never
		$token->setUid('foo');
		$this->tokenProvider
			->method('getPassword')
			->with($token)
			->willReturn('secret');
		$this->tokenProvider
			->method('getToken')
			->with('app-password')
			->willReturn($token);
		$this->tokenProvider->expects(self::once())
			->method('updateToken');

		$userSession->login('foo', 'app-password');
	}

	public function testLoginNonExisting(): void {
		$session = $this->createMock(Memory::class);
		$manager = $this->createMock(Manager::class);
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);

		$session->expects($this->never())
			->method('set');
		$session->expects($this->once())
			->method('regenerateId');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('bar')
			->willThrowException(new InvalidTokenException());

		$manager->expects($this->once())
			->method('checkPasswordNoLogging')
			->with('foo', 'bar')
			->willReturn(false);

		$userSession->login('foo', 'bar');
	}

	public function testLogClientInNoTokenPasswordWith2fa(): void {
		$this->expectException(PasswordLoginForbiddenException::class);

		$manager = $this->createMock(Manager::class);
		$session = $this->createMock(ISession::class);
		$request = $this->createMock(IRequest::class);

		/** @var Session $userSession */
		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->onlyMethods(['login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('doe')
			->willThrowException(new InvalidTokenException());
		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('token_auth_enforced', false)
			->willReturn(true);
		$request
			->expects($this->any())
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelayOrThrowOnMax')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->any())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(0);

		$userSession->logClientIn('john', 'doe', $request, $this->throttler);
	}

	public function testLogClientInUnexist(): void {
		$manager = $this->createMock(Manager::class);
		$session = $this->createMock(ISession::class);
		$request = $this->createMock(IRequest::class);

		/** @var Session $userSession */
		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->onlyMethods(['login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('doe')
			->willThrowException(new InvalidTokenException());
		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('token_auth_enforced', false)
			->willReturn(false);
		$manager->method('getByEmail')
			->with('unexist')
			->willReturn([]);

		$this->assertFalse($userSession->logClientIn('unexist', 'doe', $request, $this->throttler));
	}

	public function testLogClientInWithTokenPassword(): void {
		$manager = $this->createMock(Manager::class);
		$session = $this->createMock(ISession::class);
		$request = $this->createMock(IRequest::class);

		/** @var Session $userSession */
		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->onlyMethods(['isTokenPassword', 'login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$userSession->expects($this->once())
			->method('isTokenPassword')
			->willReturn(true);
		$userSession->expects($this->once())
			->method('login')
			->with('john', 'I-AM-AN-APP-PASSWORD')
			->willReturn(true);

		$session->expects($this->once())
			->method('set')
			->with('app_password', 'I-AM-AN-APP-PASSWORD');
		$request
			->expects($this->any())
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelayOrThrowOnMax')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->any())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(0);

		$this->assertTrue($userSession->logClientIn('john', 'I-AM-AN-APP-PASSWORD', $request, $this->throttler));
	}


	public function testLogClientInNoTokenPasswordNo2fa(): void {
		$this->expectException(PasswordLoginForbiddenException::class);

		$manager = $this->createMock(Manager::class);
		$session = $this->createMock(ISession::class);
		$request = $this->createMock(IRequest::class);

		/** @var Session $userSession */
		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->onlyMethods(['login', 'isTwoFactorEnforced'])
			->getMock();

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('doe')
			->willThrowException(new InvalidTokenException());
		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('token_auth_enforced', false)
			->willReturn(false);

		$userSession->expects($this->once())
			->method('isTwoFactorEnforced')
			->with('john')
			->willReturn(true);

		$request
			->expects($this->any())
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelayOrThrowOnMax')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->any())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(0);

		$userSession->logClientIn('john', 'doe', $request, $this->throttler);
	}

	public function testTryTokenLoginNoHeaderNoSessionCookie(): void {
		$request = $this->createMock(IRequest::class);
		$this->config->expects(self::once())
			->method('getSystemValueString')
			->with('instanceid')
			->willReturn('abc123');
		$request->method('getHeader')->with('Authorization')->willReturn('');
		$request->method('getCookie')->with('abc123')->willReturn(null);
		$this->tokenProvider->expects(self::never())
			->method('getToken');

		$loginResult = $this->userSession->tryTokenLogin($request);

		self::assertFalse($loginResult);
	}

	public function testTryTokenLoginAuthorizationHeaderTokenNotFound(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('getHeader')->with('Authorization')->willReturn('Bearer abcde-12345');
		$this->tokenProvider->expects(self::once())
			->method('getToken')
			->with('abcde-12345')
			->willThrowException(new InvalidTokenException());

		$loginResult = $this->userSession->tryTokenLogin($request);

		self::assertFalse($loginResult);
	}

	public function testTryTokenLoginSessionIdTokenNotFound(): void {
		$request = $this->createMock(IRequest::class);
		$this->config->expects(self::once())
			->method('getSystemValueString')
			->with('instanceid')
			->willReturn('abc123');
		$request->method('getHeader')->with('Authorization')->willReturn('');
		$request->method('getCookie')->with('abc123')->willReturn('abcde12345');
		$this->session->expects(self::once())
			->method('getId')
			->willReturn('abcde12345');
		$this->tokenProvider->expects(self::once())
			->method('getToken')
			->with('abcde12345')
			->willThrowException(new InvalidTokenException());

		$loginResult = $this->userSession->tryTokenLogin($request);

		self::assertFalse($loginResult);
	}

	public function testTryTokenLoginNotAnAppPassword(): void {
		$request = $this->createMock(IRequest::class);
		$this->config->expects(self::once())
			->method('getSystemValueString')
			->with('instanceid')
			->willReturn('abc123');
		$request->method('getHeader')->with('Authorization')->willReturn('');
		$request->method('getCookie')->with('abc123')->willReturn('abcde12345');
		$this->session->expects(self::once())
			->method('getId')
			->willReturn('abcde12345');
		$dbToken = new PublicKeyToken();
		$dbToken->setId(42);
		$dbToken->setUid('johnny');
		$dbToken->setLoginName('johnny');
		$dbToken->setLastCheck(0);
		$dbToken->setType(IToken::TEMPORARY_TOKEN);
		$dbToken->setRemember(IToken::REMEMBER);
		$this->tokenProvider->expects(self::any())
			->method('getToken')
			->with('abcde12345')
			->willReturn($dbToken);
		$this->session->method('set')
			->willReturnCallback(function ($key, $value): void {
				if ($key === 'app_password') {
					throw new ExpectationFailedException('app_password should not be set in session');
				}
			});
		$user = $this->createMock(IUser::class);
		$user->method('isEnabled')->willReturn(true);
		$this->manager->method('get')
			->with('johnny')
			->willReturn($user);

		$loginResult = $this->userSession->tryTokenLogin($request);

		self::assertTrue($loginResult);
	}

	public function testRememberLoginValidToken(): void {
		$session = $this->createMock(Memory::class);
		$managerMethods = get_class_methods(Manager::class);
		//keep following methods intact in order to ensure hooks are working
		$mockedManagerMethods = array_diff($managerMethods, ['__construct', 'emit', 'listen']);
		$manager = $this->getMockBuilder(Manager::class)
			->onlyMethods($mockedManagerMethods)
			->setConstructorArgs([
				$this->config,
				$this->createMock(ICacheFactory::class),
				$this->createMock(IEventDispatcher::class),
				$this->createMock(LoggerInterface::class),
			])
			->getMock();
		$userSession = $this->getMockBuilder(Session::class)
			//override, otherwise tests will fail because of setcookie()
			->onlyMethods(['setMagicInCookie', 'setLoginName'])
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->getMock();

		$user = $this->createMock(IUser::class);
		$token = 'goodToken';
		$oldSessionId = 'sess321';
		$sessionId = 'sess123';

		$session->expects($this->once())
			->method('regenerateId');
		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($user);
		$this->config->expects($this->once())
			->method('getUserKeys')
			->with('foo', 'login_token')
			->willReturn([$token]);
		$this->config->expects($this->once())
			->method('deleteUserValue')
			->with('foo', 'login_token', $token);
		$this->random->expects($this->once())
			->method('generate')
			->with(32)
			->willReturn('abcdefg123456');
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('foo', 'login_token', 'abcdefg123456', 10000);

		$tokenObject = $this->createMock(IToken::class);
		$tokenObject->expects($this->once())
			->method('getLoginName')
			->willReturn('foobar');
		$tokenObject->method('getId')
			->willReturn(42);

		$session->expects($this->once())
			->method('getId')
			->willReturn($sessionId);
		$this->tokenProvider->expects($this->once())
			->method('renewSessionToken')
			->with($oldSessionId, $sessionId)
			->willReturn($tokenObject);

		$this->tokenProvider->expects($this->never())
			->method('getToken');

		$user->expects($this->any())
			->method('getUID')
			->willReturn('foo');
		$userSession->expects($this->once())
			->method('setMagicInCookie');
		$user->expects($this->once())
			->method('updateLastLoginTimestamp');
		$setUID = false;
		$session
			->method('set')
			->willReturnCallback(function ($k, $v) use (&$setUID): void {
				if ($k === 'user_id' && $v === 'foo') {
					$setUID = true;
				}
			});
		$userSession->expects($this->once())
			->method('setLoginName')
			->willReturn('foobar');

		$granted = $userSession->loginWithCookie('foo', $token, $oldSessionId);

		$this->assertTrue($setUID);

		$this->assertTrue($granted);
	}

	public function testRememberLoginInvalidSessionToken(): void {
		$session = $this->createMock(Memory::class);
		$managerMethods = get_class_methods(Manager::class);
		//keep following methods intact in order to ensure hooks are working
		$mockedManagerMethods = array_diff($managerMethods, ['__construct', 'emit', 'listen']);
		$manager = $this->getMockBuilder(Manager::class)
			->onlyMethods($mockedManagerMethods)
			->setConstructorArgs([
				$this->config,
				$this->createMock(ICacheFactory::class),
				$this->createMock(IEventDispatcher::class),
				$this->createMock(LoggerInterface::class),
			])
			->getMock();
		$userSession = $this->getMockBuilder(Session::class)
			//override, otherwise tests will fail because of setcookie()
			->onlyMethods(['setMagicInCookie'])
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->getMock();

		$user = $this->createMock(IUser::class);
		$token = 'goodToken';
		$oldSessionId = 'sess321';
		$sessionId = 'sess123';

		$session->expects($this->once())
			->method('regenerateId');
		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($user);
		$this->config->expects($this->once())
			->method('getUserKeys')
			->with('foo', 'login_token')
			->willReturn([$token]);
		$this->config->expects($this->once())
			->method('deleteUserValue')
			->with('foo', 'login_token', $token);
		$this->config->expects($this->once())
			->method('setUserValue'); // TODO: mock new random value

		$session->expects($this->once())
			->method('getId')
			->willReturn($sessionId);
		$this->tokenProvider->expects($this->once())
			->method('renewSessionToken')
			->with($oldSessionId, $sessionId)
			->willThrowException(new InvalidTokenException());

		$user->expects($this->never())
			->method('getUID')
			->willReturn('foo');
		$userSession->expects($this->never())
			->method('setMagicInCookie');
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');
		$session->expects($this->never())
			->method('set')
			->with('user_id', 'foo');

		$granted = $userSession->loginWithCookie('foo', $token, $oldSessionId);

		$this->assertFalse($granted);
	}

	public function testRememberLoginInvalidToken(): void {
		$session = $this->createMock(Memory::class);
		$managerMethods = get_class_methods(Manager::class);
		//keep following methods intact in order to ensure hooks are working
		$mockedManagerMethods = array_diff($managerMethods, ['__construct', 'emit', 'listen']);
		$manager = $this->getMockBuilder(Manager::class)
			->onlyMethods($mockedManagerMethods)
			->setConstructorArgs([
				$this->config,
				$this->createMock(ICacheFactory::class),
				$this->createMock(IEventDispatcher::class),
				$this->createMock(LoggerInterface::class),
			])
			->getMock();
		$userSession = $this->getMockBuilder(Session::class)
			//override, otherwise tests will fail because of setcookie()
			->onlyMethods(['setMagicInCookie'])
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->getMock();

		$user = $this->createMock(IUser::class);
		$token = 'goodToken';
		$oldSessionId = 'sess321';

		$session->expects($this->once())
			->method('regenerateId');
		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($user);
		$this->config->expects($this->once())
			->method('getUserKeys')
			->with('foo', 'login_token')
			->willReturn(['anothertoken']);
		$this->config->expects($this->never())
			->method('deleteUserValue')
			->with('foo', 'login_token', $token);

		$this->tokenProvider->expects($this->never())
			->method('renewSessionToken');
		$userSession->expects($this->never())
			->method('setMagicInCookie');
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');
		$session->expects($this->never())
			->method('set')
			->with('user_id', 'foo');

		$granted = $userSession->loginWithCookie('foo', $token, $oldSessionId);

		$this->assertFalse($granted);
	}

	public function testRememberLoginInvalidUser(): void {
		$session = $this->createMock(Memory::class);
		$managerMethods = get_class_methods(Manager::class);
		//keep following methods intact in order to ensure hooks are working
		$mockedManagerMethods = array_diff($managerMethods, ['__construct', 'emit', 'listen']);
		$manager = $this->getMockBuilder(Manager::class)
			->onlyMethods($mockedManagerMethods)
			->setConstructorArgs([
				$this->config,
				$this->createMock(ICacheFactory::class),
				$this->createMock(IEventDispatcher::class),
				$this->createMock(LoggerInterface::class),
			])
			->getMock();
		$userSession = $this->getMockBuilder(Session::class)
			//override, otherwise tests will fail because of setcookie()
			->onlyMethods(['setMagicInCookie'])
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->getMock();
		$token = 'goodToken';
		$oldSessionId = 'sess321';

		$session->expects($this->once())
			->method('regenerateId');
		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn(null);
		$this->config->expects($this->never())
			->method('getUserKeys')
			->with('foo', 'login_token')
			->willReturn(['anothertoken']);

		$this->tokenProvider->expects($this->never())
			->method('renewSessionToken');
		$userSession->expects($this->never())
			->method('setMagicInCookie');
		$session->expects($this->never())
			->method('set')
			->with('user_id', 'foo');

		$granted = $userSession->loginWithCookie('foo', $token, $oldSessionId);

		$this->assertFalse($granted);
	}

	public function testActiveUserAfterSetSession(): void {
		$users = [
			'foo' => new User('foo', null, $this->createMock(IEventDispatcher::class)),
			'bar' => new User('bar', null, $this->createMock(IEventDispatcher::class))
		];

		$manager = $this->getMockBuilder(Manager::class)
			->disableOriginalConstructor()
			->getMock();

		$manager->expects($this->any())
			->method('get')
			->willReturnCallback(function ($uid) use ($users) {
				return $users[$uid];
			});

		$session = new Memory();
		$session->set('user_id', 'foo');
		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->onlyMethods([
				'validateSession'
			])
			->getMock();
		$userSession->expects($this->any())
			->method('validateSession');

		$this->assertEquals($users['foo'], $userSession->getUser());

		$session2 = new Memory();
		$session2->set('user_id', 'bar');
		$userSession->setSession($session2);
		$this->assertEquals($users['bar'], $userSession->getUser());
	}

	public function testCreateSessionToken(): void {
		$manager = $this->createMock(Manager::class);
		$session = $this->createMock(ISession::class);
		$user = $this->createMock(IUser::class);
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);

		$requestId = $this->createMock(IRequestId::class);
		$config = $this->createMock(IConfig::class);
		$csrf = $this->getMockBuilder(CsrfTokenManager::class)
			->disableOriginalConstructor()
			->getMock();
		$request = new Request([
			'server' => [
				'HTTP_USER_AGENT' => 'Firefox',
			]
		], $requestId, $config, $csrf);

		$uid = 'user123';
		$loginName = 'User123';
		$password = 'passme';
		$sessionId = 'abcxyz';

		$manager->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn($user);
		$session->expects($this->once())
			->method('getId')
			->willReturn($sessionId);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($password)
			->willThrowException(new InvalidTokenException());

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with($sessionId, $uid, $loginName, $password, 'Firefox', IToken::TEMPORARY_TOKEN, IToken::DO_NOT_REMEMBER);

		$this->assertTrue($userSession->createSessionToken($request, $uid, $loginName, $password));
	}

	public function testCreateRememberedSessionToken(): void {
		$manager = $this->createMock(Manager::class);
		$session = $this->createMock(ISession::class);
		$user = $this->createMock(IUser::class);
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);

		$requestId = $this->createMock(IRequestId::class);
		$config = $this->createMock(IConfig::class);
		$csrf = $this->getMockBuilder(CsrfTokenManager::class)
			->disableOriginalConstructor()
			->getMock();
		$request = new Request([
			'server' => [
				'HTTP_USER_AGENT' => 'Firefox',
			]
		], $requestId, $config, $csrf);

		$uid = 'user123';
		$loginName = 'User123';
		$password = 'passme';
		$sessionId = 'abcxyz';

		$manager->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn($user);
		$session->expects($this->once())
			->method('getId')
			->willReturn($sessionId);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($password)
			->willThrowException(new InvalidTokenException());

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with($sessionId, $uid, $loginName, $password, 'Firefox', IToken::TEMPORARY_TOKEN, IToken::REMEMBER);

		$this->assertTrue($userSession->createSessionToken($request, $uid, $loginName, $password, true));
	}

	public function testCreateSessionTokenWithTokenPassword(): void {
		$manager = $this->getMockBuilder(Manager::class)
			->disableOriginalConstructor()
			->getMock();
		$session = $this->createMock(ISession::class);
		$token = $this->createMock(IToken::class);
		$user = $this->createMock(IUser::class);
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);

		$requestId = $this->createMock(IRequestId::class);
		$config = $this->createMock(IConfig::class);
		$csrf = $this->getMockBuilder(CsrfTokenManager::class)
			->disableOriginalConstructor()
			->getMock();
		$request = new Request([
			'server' => [
				'HTTP_USER_AGENT' => 'Firefox',
			]
		], $requestId, $config, $csrf);

		$uid = 'user123';
		$loginName = 'User123';
		$password = 'iamatoken';
		$realPassword = 'passme';
		$sessionId = 'abcxyz';

		$manager->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn($user);
		$session->expects($this->once())
			->method('getId')
			->willReturn($sessionId);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($password)
			->willReturn($token);
		$this->tokenProvider->expects($this->once())
			->method('getPassword')
			->with($token, $password)
			->willReturn($realPassword);

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with($sessionId, $uid, $loginName, $realPassword, 'Firefox', IToken::TEMPORARY_TOKEN, IToken::DO_NOT_REMEMBER);

		$this->assertTrue($userSession->createSessionToken($request, $uid, $loginName, $password));
	}

	public function testCreateSessionTokenWithNonExistentUser(): void {
		$manager = $this->getMockBuilder(Manager::class)
			->disableOriginalConstructor()
			->getMock();
		$session = $this->createMock(ISession::class);
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher);
		$request = $this->createMock(IRequest::class);

		$uid = 'user123';
		$loginName = 'User123';
		$password = 'passme';

		$manager->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn(null);

		$this->assertFalse($userSession->createSessionToken($request, $uid, $loginName, $password));
	}

	public function testCreateRememberMeToken(): void {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->exactly(2))
			->method('getUID')
			->willReturn('UserUid');
		$this->random
			->expects($this->once())
			->method('generate')
			->with(32)
			->willReturn('LongRandomToken');
		$this->config
			->expects($this->once())
			->method('setUserValue')
			->with('UserUid', 'login_token', 'LongRandomToken', 10000);
		$this->userSession
			->expects($this->once())
			->method('setMagicInCookie')
			->with('UserUid', 'LongRandomToken');

		$this->userSession->createRememberMeToken($user);
	}

	public function testTryBasicAuthLoginValid(): void {
		$request = $this->createMock(Request::class);
		$request->method('__get')
			->willReturn([
				'PHP_AUTH_USER' => 'username',
				'PHP_AUTH_PW' => 'password',
			]);
		$request->method('__isset')
			->with('server')
			->willReturn(true);

		$davAuthenticatedSet = false;
		$lastPasswordConfirmSet = false;

		$this->session
			->method('set')
			->willReturnCallback(function ($k, $v) use (&$davAuthenticatedSet, &$lastPasswordConfirmSet): void {
				switch ($k) {
					case Auth::DAV_AUTHENTICATED:
						$davAuthenticatedSet = $v;
						return;
					case 'last-password-confirm':
						$lastPasswordConfirmSet = 1000;
						return;
					default:
						throw new \Exception();
				}
			});

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([
				$this->manager,
				$this->session,
				$this->timeFactory,
				$this->tokenProvider,
				$this->config,
				$this->random,
				$this->lockdownManager,
				$this->logger,
				$this->dispatcher
			])
			->onlyMethods([
				'logClientIn',
				'getUser',
			])
			->getMock();

		/** @var Session|MockObject */
		$userSession->expects($this->once())
			->method('logClientIn')
			->with(
				$this->equalTo('username'),
				$this->equalTo('password'),
				$this->equalTo($request),
				$this->equalTo($this->throttler)
			)->willReturn(true);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('username');

		$userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->assertTrue($userSession->tryBasicAuthLogin($request, $this->throttler));

		$this->assertSame('username', $davAuthenticatedSet);
		$this->assertSame(1000, $lastPasswordConfirmSet);
	}

	public function testTryBasicAuthLoginNoLogin(): void {
		$request = $this->createMock(Request::class);
		$request->method('__get')
			->willReturn([]);
		$request->method('__isset')
			->with('server')
			->willReturn(true);

		$this->session->expects($this->never())
			->method($this->anything());

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([
				$this->manager,
				$this->session,
				$this->timeFactory,
				$this->tokenProvider,
				$this->config,
				$this->random,
				$this->lockdownManager,
				$this->logger,
				$this->dispatcher
			])
			->onlyMethods([
				'logClientIn',
			])
			->getMock();

		/** @var Session|MockObject */
		$userSession->expects($this->never())
			->method('logClientIn');

		$this->assertFalse($userSession->tryBasicAuthLogin($request, $this->throttler));
	}

	public function testUpdateTokens(): void {
		$this->tokenProvider->expects($this->once())
			->method('updatePasswords')
			->with('uid', 'pass');

		$this->userSession->updateTokens('uid', 'pass');
	}

	public function testLogClientInThrottlerUsername(): void {
		$manager = $this->createMock(Manager::class);
		$session = $this->createMock(ISession::class);
		$request = $this->createMock(IRequest::class);

		/** @var Session $userSession */
		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->onlyMethods(['isTokenPassword', 'login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$userSession->expects($this->once())
			->method('isTokenPassword')
			->willReturn(true);
		$userSession->expects($this->once())
			->method('login')
			->with('john', 'I-AM-AN-PASSWORD')
			->willReturn(false);

		$session->expects($this->never())
			->method('set');
		$request
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->throttler
			->expects($this->exactly(2))
			->method('sleepDelayOrThrowOnMax')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->any())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(0);

		$this->throttler
			->expects($this->once())
			->method('registerAttempt')
			->with('login', '192.168.0.1', ['user' => 'john']);
		$this->dispatcher
			->expects($this->once())
			->method('dispatchTyped')
			->with(new LoginFailed('john', 'I-AM-AN-PASSWORD'));

		$this->assertFalse($userSession->logClientIn('john', 'I-AM-AN-PASSWORD', $request, $this->throttler));
	}

	public function testLogClientInThrottlerEmail(): void {
		$manager = $this->createMock(Manager::class);
		$session = $this->createMock(ISession::class);
		$request = $this->createMock(IRequest::class);

		/** @var Session $userSession */
		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher])
			->onlyMethods(['isTokenPassword', 'login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$userSession->expects($this->once())
			->method('isTokenPassword')
			->willReturn(false);
		$userSession->expects($this->once())
			->method('login')
			->with('john@foo.bar', 'I-AM-AN-PASSWORD')
			->willReturn(false);
		$manager
			->method('getByEmail')
			->with('john@foo.bar')
			->willReturn([]);

		$session->expects($this->never())
			->method('set');
		$request
			->method('getRemoteAddress')
			->willReturn('192.168.0.1');
		$this->throttler
			->expects($this->exactly(2))
			->method('sleepDelayOrThrowOnMax')
			->with('192.168.0.1');
		$this->throttler
			->expects($this->any())
			->method('getDelay')
			->with('192.168.0.1')
			->willReturn(0);

		$this->throttler
			->expects($this->once())
			->method('registerAttempt')
			->with('login', '192.168.0.1', ['user' => 'john@foo.bar']);
		$this->dispatcher
			->expects($this->once())
			->method('dispatchTyped')
			->with(new LoginFailed('john@foo.bar', 'I-AM-AN-PASSWORD'));

		$this->assertFalse($userSession->logClientIn('john@foo.bar', 'I-AM-AN-PASSWORD', $request, $this->throttler));
	}
}
