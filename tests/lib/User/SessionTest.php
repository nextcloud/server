<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User;

use OC\AppFramework\Http\Request;
use OC\Authentication\Events\LoginFailed;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Exceptions\PasswordLoginForbiddenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\RememberMeTokenGrace;
use OC\Security\CSRF\CsrfTokenManager;
use OC\Session\Memory;
use OC\User\LoginException;
use OC\User\Manager;
use OC\User\Session;
use OC\User\User;
use OCA\DAV\Connector\Sabre\Auth;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Token\IToken;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\Event;
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
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\PostLoginEvent;
use OCP\User\Events\UserLoggedInEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use function array_diff;
use function get_class_methods;

#[Group(name: 'DB')]
class SessionTest extends TestCase {
	private ITimeFactory&MockObject $timeFactory;
	private IProvider&MockObject $tokenProvider;
	private IConfig&MockObject $config;
	private IThrottler&MockObject $throttler;
	private ISecureRandom&MockObject $random;
	private Manager&MockObject $manager;
	private ISession&MockObject $session;
	private Session&MockObject $userSession;
	private ILockdownManager&MockObject $lockdownManager;
	private LoggerInterface&MockObject $logger;
	private IEventDispatcher&MockObject $dispatcher;
	private RememberMeTokenGrace&MockObject $rememberMeTokenGrace;
	private IUserConfig&MockObject $userConfig;

	#[\Override]
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
		$this->manager
			->method('getUserNameFromLoginName')
			->willReturnArgument(0);
		$this->session = $this->createMock(ISession::class);
		$this->lockdownManager = $this->createMock(ILockdownManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->rememberMeTokenGrace = $this->createMock(RememberMeTokenGrace::class);
		$this->userConfig = $this->createMock(IUserConfig::class);
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
				$this->dispatcher,
				$this->rememberMeTokenGrace,
				$this->userConfig,
			])
			->onlyMethods([
				'setMagicInCookie',
			])
			->getMock();
	}

	public static function isLoggedInData(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider(methodName: 'isLoggedInData')]
	public function testIsLoggedIn($isLoggedIn): void {
		$session = $this->createMock(Memory::class);

		$manager = $this->createMock(Manager::class);

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
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

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('foo');

		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);
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
			->with($this->callback(function (string $key): bool {
				return $key === 'user_id' || $key === 'loginname';
			}));

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
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
			->onlyMethods([
				'prepareUserLogin'
			])
			->getMock();
		$userSession->expects($this->once())
			->method('prepareUserLogin');

		$this->dispatcher->expects($this->exactly(2))
			->method('dispatchTyped')
			->with(
				$this->callback(function (PostLoginEvent|UserLoggedInEvent $e): bool {
					return $e->getUser()->getUID() === 'foo'
						&& $e->getPassword() === 'bar'
						&& $e->isTokenLogin() === false;
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

		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);
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
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);

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
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('foo');
		$user->method('isEnabled')->willReturn(true);
		$manager->method('get')
			->with('foo')
			->willReturn($user);

		$session->expects($this->once())
			->method('regenerateId');
		$token = new PublicKeyToken();
		$token->setId(1);
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
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('foo');
		$user->method('isEnabled')->willReturn(true);
		$manager->method('get')
			->with('foo')
			->willReturn($user);

		$session->expects($this->once())
			->method('regenerateId');
		$token = new PublicKeyToken();
		$token->setId(1);
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
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);

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

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
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
			->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(
				function (Event $event): void {
					if ($event instanceof LoginFailed) {
						$this->assertEquals($event, new LoginFailed('john', 'doe'));
					} elseif ($event instanceof BeforeUserLoggedInEvent) {
						$this->assertEquals($event, new BeforeUserLoggedInEvent('john', 'doe'));
					} else {
						$this->fail('Unexpected event');
					}
				}
			);

		$userSession->logClientIn('john', 'doe', $request, $this->throttler);
	}

	public function testLogClientInUnexist(): void {
		$manager = $this->createMock(Manager::class);
		$manager
			->method('getUserNameFromLoginName')
			->willReturnArgument(0);
		$session = $this->createMock(ISession::class);
		$request = $this->createMock(IRequest::class);

		/** @var Session $userSession */
		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
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

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
			->onlyMethods(['login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('I-AM-AN-APP-PASSWORD')
			->willReturn($this->createMock(IToken::class));
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

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
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
			->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(
				function (Event $event): void {
					if ($event instanceof LoginFailed) {
						$this->assertEquals($event, new LoginFailed('john', 'doe'));
					} elseif ($event instanceof BeforeUserLoggedInEvent) {
						$this->assertEquals($event, new BeforeUserLoggedInEvent('john', 'doe'));
					} else {
						$this->fail('Unexpected event');
					}
				}
			);

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

	public function testTryTokenLoginOcmAccessTokenRejectedFromBearerByDefault(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('getHeader')->with('Authorization')->willReturn('Bearer ocm-access-token');
		$dbToken = new PublicKeyToken();
		$dbToken->setId(42);
		$dbToken->setUid('alice');
		$dbToken->setLoginName('alice');
		$dbToken->setLastCheck(0);
		$dbToken->setType(IToken::TEMPORARY_TOKEN);
		$dbToken->setName(IToken::OCM_ACCESS_TOKEN_NAME);
		$this->tokenProvider->expects(self::once())
			->method('getToken')
			->with('ocm-access-token')
			->willReturn($dbToken);
		// The guard must reject before any login is attempted.
		$this->manager->expects(self::never())
			->method('get');

		$loginResult = $this->userSession->tryTokenLogin($request);

		self::assertFalse($loginResult);
	}

	public function testTryTokenLoginOcmAccessTokenAllowedFromBearerWhenPermitted(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('getHeader')->with('Authorization')->willReturn('Bearer ocm-access-token');
		$dbToken = new PublicKeyToken();
		$dbToken->setId(42);
		$dbToken->setUid('alice');
		$dbToken->setLoginName('alice');
		$dbToken->setLastCheck(0);
		$dbToken->setType(IToken::TEMPORARY_TOKEN);
		$dbToken->setName(IToken::OCM_ACCESS_TOKEN_NAME);
		$this->tokenProvider->method('getToken')
			->with('ocm-access-token')
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
			->with('alice')
			->willReturn($user);

		$loginResult = $this->userSession->tryTokenLogin($request, true);

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
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
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
		$this->userConfig->expects($this->once())
			->method('getKeys')
			->with('foo', 'login_token')
			->willReturn([$token]);
		$this->userConfig->expects($this->once())
			->method('deleteUserConfig')
			->with('foo', 'login_token', $token);
		$this->random->expects($this->once())
			->method('generate')
			->with(32)
			->willReturn('abcdefg123456');
		$this->userConfig->expects($this->once())
			->method('setValueString')
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

		$oldTokenObject = $this->createMock(IToken::class);
		$oldTokenObject->expects($this->once())
			->method('getUID')
			->willReturn('foo');

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->willReturn($oldTokenObject);

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

	public function testRememberLoginRemembersTheRotation(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		$this->userConfig->method('getKeys')->willReturn(['goodToken']);
		$this->random->method('generate')->with(32)->willReturn('newToken');
		$this->session->method('getId')->willReturn('sess123');
		$this->tokenProvider->method('getToken')->willReturn($this->createTokenOfFoo());

		$calls = [];
		$this->session->method('regenerateId')
			->willReturnCallback(function () use (&$calls): void {
				$calls[] = 'regenerateId';
			});
		$this->tokenProvider->method('renewSessionToken')
			->willReturnCallback(function () use (&$calls) {
				$calls[] = 'renewSessionToken';
				return $this->createTokenOfFoo();
			});
		$this->rememberMeTokenGrace->expects($this->once())
			->method('remember')
			->with('goodToken', 'sess321', 'newToken', 'sess123')
			->willReturnCallback(function () use (&$calls): void {
				$calls[] = 'remember';
			});
		$this->userConfig->method('setValueString')
			->willReturnCallback(function () use (&$calls): bool {
				$calls[] = 'setValueString';
				return true;
			});
		$this->userConfig->method('deleteUserConfig')
			->willReturnCallback(function () use (&$calls): void {
				$calls[] = 'deleteUserConfig';
			});

		$this->assertTrue($userSession->loginWithCookie('foo', 'goodToken', 'sess321'));
		// Only the request that renewed the session token publishes, once the successor is stored and before the
		// old token is gone, so a concurrent request always finds one of the two
		$this->assertSame(['regenerateId', 'renewSessionToken', 'setValueString', 'remember', 'deleteUserConfig'], $calls);
	}

	public function testRememberLoginUsesTheUserIdForTheTokens(): void {
		// The user manager resolves the username of the cookie to the user id
		$userSession = $this->createUserSessionForCookieLogin('FOO');
		$this->random->method('generate')->with(32)->willReturn('newToken');
		$this->session->method('getId')->willReturn('sess123');
		$this->tokenProvider->method('getToken')->willReturn($this->createTokenOfFoo());
		$this->tokenProvider->method('renewSessionToken')->willReturn($this->createTokenOfFoo());

		$this->userConfig->expects($this->once())
			->method('getKeys')
			->with('foo', 'login_token')
			->willReturn(['goodToken']);
		$this->userConfig->expects($this->once())
			->method('setValueString')
			->with('foo', 'login_token', 'newToken', $this->anything());
		$this->userConfig->expects($this->once())
			->method('deleteUserConfig')
			->with('foo', 'login_token', 'goodToken');

		$this->assertTrue($userSession->loginWithCookie('FOO', 'goodToken', 'sess321'));
	}

	public static function sessionTokenOfAnotherUserData(): array {
		return [
			'username of the user id' => ['cookieUsername' => 'foo'],
			'username resolved by the user manager' => ['cookieUsername' => 'FOO'],
		];
	}

	#[DataProvider(methodName: 'sessionTokenOfAnotherUserData')]
	public function testRememberLoginWithTheSessionTokenOfAnotherUserFails(string $cookieUsername): void {
		$userSession = $this->createUserSessionForCookieLogin($cookieUsername);
		$this->userConfig->method('getKeys')->with('foo', 'login_token')->willReturn(['goodToken']);
		$tokenOfBar = $this->createMock(PublicKeyToken::class);
		$tokenOfBar->method('getUID')->willReturn('bar');
		$this->tokenProvider->method('getToken')->with('sess321')->willReturn($tokenOfBar);

		$this->session->expects($this->never())
			->method('regenerateId');
		$this->tokenProvider->expects($this->never())
			->method('renewSessionToken');
		$this->tokenProvider->expects($this->never())
			->method('copySessionToken');
		$this->userConfig->expects($this->never())
			->method('setValueString');
		$this->userConfig->expects($this->never())
			->method('deleteUserConfig');
		$userSession->expects($this->never())
			->method('setMagicInCookie');
		$this->logger->expects($this->once())
			->method('warning')
			->with('Tried to renew a session token belonging to a different user', $this->anything());

		$this->assertFalse($userSession->loginWithCookie($cookieUsername, 'goodToken', 'sess321'));
	}

	public function testRememberLoginWithinGracePeriodUsesTheSuccessor(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		// The token was rotated away by a concurrent request
		$this->userConfig->method('getKeys')->willReturn(['newToken']);
		$this->rememberMeTokenGrace->method('resolve')
			->with('rotatedToken', 'sess321')
			->willReturn(['token' => 'newToken', 'sessionId' => 'sessOfTheConcurrentRequest']);
		$this->session->method('getId')->willReturn('sess123');
		$this->tokenProvider->method('getToken')
			->with('sessOfTheConcurrentRequest')
			->willReturn($this->createTokenOfFoo());

		// The session token of the concurrent request stays valid, only this session gets one of its own
		$this->tokenProvider->expects($this->never())
			->method('renewSessionToken');
		$calls = [];
		$this->session->method('regenerateId')
			->willReturnCallback(function () use (&$calls): void {
				$calls[] = 'regenerateId';
			});
		$this->tokenProvider->expects($this->once())
			->method('copySessionToken')
			->with('sessOfTheConcurrentRequest', 'sess123')
			->willReturnCallback(function () use (&$calls): IToken {
				$calls[] = 'copySessionToken';
				return $this->createTokenOfFoo();
			});
		// The successor is not rotated again, both requests hand the browser the same cookies
		$this->userConfig->expects($this->never())
			->method('deleteUserConfig');
		$this->userConfig->expects($this->never())
			->method('setValueString');
		$this->rememberMeTokenGrace->expects($this->never())
			->method('remember');
		$userSession->expects($this->once())
			->method('setMagicInCookie')
			->with('foo', 'newToken');

		$this->assertTrue($userSession->loginWithCookie('foo', 'rotatedToken', 'sess321'));
		$this->assertSame(['regenerateId', 'copySessionToken'], $calls);
	}

	public function testRememberLoginUsesTheSuccessorWhenTheSessionTokenIsGone(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		// The remember-me token is still stored, but a concurrent request renewed the session token and stored its
		// successor after this request read the keys
		$this->userConfig->method('getKeys')->willReturnOnConsecutiveCalls(['goodToken'], ['goodToken', 'newToken']);
		$this->userConfig->expects($this->once())
			->method('clearCache')
			->with('foo');
		$this->session->method('getId')->willReturn('sess123');
		$this->tokenProvider->method('getToken')
			->willReturnCallback(function (string $sessionId) {
				if ($sessionId !== 'sessOfTheConcurrentRequest') {
					throw new InvalidTokenException();
				}
				return $this->createTokenOfFoo();
			});
		$this->rememberMeTokenGrace->method('resolve')
			->with('goodToken', 'sess321')
			->willReturn(['token' => 'newToken', 'sessionId' => 'sessOfTheConcurrentRequest']);

		$this->tokenProvider->expects($this->never())
			->method('renewSessionToken');
		$this->tokenProvider->expects($this->once())
			->method('copySessionToken')
			->with('sessOfTheConcurrentRequest', 'sess123')
			->willReturn($this->createTokenOfFoo());
		$userSession->expects($this->once())
			->method('setMagicInCookie')
			->with('foo', 'newToken');

		$this->assertTrue($userSession->loginWithCookie('foo', 'goodToken', 'sess321'));
	}

	public function testRememberLoginAdoptsTheSuccessorWhenTheRenewalLosesTheRace(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		$this->userConfig->method('getKeys')->willReturn(['goodToken', 'newToken']);
		$this->random->method('generate')->with(32)->willReturn('ownToken');
		$this->session->method('getId')->willReturn('sess123');
		$this->tokenProvider->method('getToken')->willReturn($this->createTokenOfFoo());
		// A concurrent request renewed the session token first
		$this->tokenProvider->method('renewSessionToken')->willThrowException(new InvalidTokenException());
		$this->rememberMeTokenGrace->method('resolve')
			->with('goodToken', 'sess321')
			->willReturn(['token' => 'newToken', 'sessionId' => 'sessOfTheConcurrentRequest']);
		$deleted = [];
		$this->userConfig->method('deleteUserConfig')
			->willReturnCallback(function (string $uid, string $app, string $key) use (&$deleted): void {
				$deleted[] = $key;
			});

		$this->tokenProvider->expects($this->once())
			->method('copySessionToken')
			->with('sessOfTheConcurrentRequest', 'sess123')
			->willReturn($this->createTokenOfFoo());
		$userSession->expects($this->once())
			->method('setMagicInCookie')
			->with('foo', 'newToken');

		$this->assertTrue($userSession->loginWithCookie('foo', 'goodToken', 'sess321'));
		// A request that loses the race rotates nothing, so it leaves no token behind
		$this->assertSame([], $deleted);
	}

	public function testRememberLoginWithoutTheSuccessorSessionFails(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		$this->userConfig->method('getKeys')->willReturn(['newToken']);
		$this->rememberMeTokenGrace->method('resolve')
			->with('rotatedToken', 'sess321')
			->willReturn(['token' => 'newToken', 'sessionId' => 'sessOfTheConcurrentRequest']);
		// The session token of the concurrent request was revoked, for example by a remote wipe
		$this->tokenProvider->method('getToken')->willThrowException(new InvalidTokenException());

		$this->logger->expects($this->once())
			->method('info')
			->with('Tried to log in but could not verify token', $this->anything());

		$this->assertFalse($userSession->loginWithCookie('foo', 'rotatedToken', 'sess321'));
	}

	public function testRememberLoginWithASuccessorDeletedByALogoutFails(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		$this->userConfig->method('getKeys')->willReturn([]);
		$this->rememberMeTokenGrace->method('resolve')
			->with('rotatedToken', 'sess321')
			->willReturn(['token' => 'newToken', 'sessionId' => 'sessOfTheConcurrentRequest']);
		// The session token of the request that rotated is still valid, the browser logged out with a copy of it
		$this->tokenProvider->method('getToken')->willReturn($this->createTokenOfFoo());

		$this->tokenProvider->expects($this->never())
			->method('copySessionToken');
		$this->logger->expects($this->once())
			->method('info')
			->with('Tried to log in but could not verify token', $this->anything());

		$this->assertFalse($userSession->loginWithCookie('foo', 'rotatedToken', 'sess321'));
	}

	public function testRememberLoginWithASuccessorThatIsNotRememberedFails(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		$this->userConfig->method('getKeys')->willReturn(['newToken']);
		$this->rememberMeTokenGrace->method('resolve')
			->with('rotatedToken', 'sess321')
			->willReturn(['token' => 'newToken', 'sessionId' => 'sessOfTheConcurrentRequest']);
		$this->tokenProvider->method('getToken')->willReturn($this->createTokenOfFoo(IToken::DO_NOT_REMEMBER));

		$this->tokenProvider->expects($this->never())
			->method('copySessionToken');
		$this->logger->expects($this->once())
			->method('info')
			->with('Tried to log in but could not verify token', $this->anything());

		$this->assertFalse($userSession->loginWithCookie('foo', 'rotatedToken', 'sess321'));
	}

	public function testRememberLoginFailsWhenTheSuccessorCannotBeCopied(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		$this->userConfig->method('getKeys')->willReturn(['newToken']);
		$this->rememberMeTokenGrace->method('resolve')
			->with('rotatedToken', 'sess321')
			->willReturn(['token' => 'newToken', 'sessionId' => 'sessOfTheConcurrentRequest']);
		$this->session->method('getId')->willReturn('sess123');
		$this->tokenProvider->method('getToken')->willReturn($this->createTokenOfFoo());
		// The session token of the concurrent request was revoked after it was checked
		$this->tokenProvider->method('copySessionToken')->willThrowException(new InvalidTokenException());

		$userSession->expects($this->never())
			->method('setMagicInCookie');
		$this->logger->expects($this->once())
			->method('error')
			->with($this->stringStartsWith('Renewing session token failed'), $this->anything());

		$this->assertFalse($userSession->loginWithCookie('foo', 'rotatedToken', 'sess321'));
		$this->assertNull($userSession->getUser());
	}

	public function testRememberLoginWithASuccessorOfAnotherUserFails(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		$this->userConfig->method('getKeys')->willReturn(['newToken']);
		$this->rememberMeTokenGrace->method('resolve')
			->with('rotatedToken', 'sess321')
			->willReturn(['token' => 'newToken', 'sessionId' => 'sessOfAnotherUser']);
		$foreignToken = $this->createMock(PublicKeyToken::class);
		$foreignToken->method('getUID')->willReturn('bar');
		$this->tokenProvider->method('getToken')->willReturn($foreignToken);

		$this->tokenProvider->expects($this->never())
			->method('copySessionToken');
		$this->logger->expects($this->once())
			->method('info')
			->with('Tried to log in but could not verify token', $this->anything());

		$this->assertFalse($userSession->loginWithCookie('foo', 'rotatedToken', 'sess321'));
	}

	public function testRememberLoginAfterTheGracePeriodFails(): void {
		$userSession = $this->createUserSessionForCookieLogin();
		$this->userConfig->method('getKeys')->willReturn(['newToken']);
		$this->rememberMeTokenGrace->method('resolve')
			->with('rotatedToken', 'sess321')
			->willReturn(null);

		$this->tokenProvider->expects($this->never())
			->method('getToken');
		$this->logger->expects($this->once())
			->method('info')
			->with('Tried to log in but could not verify token', $this->anything());

		$this->assertFalse($userSession->loginWithCookie('foo', 'rotatedToken', 'sess321'));
	}

	/**
	 * A user session of user foo, whose username in the cookie is $cookieUsername, with the cookie handling mocked
	 */
	private function createUserSessionForCookieLogin(string $cookieUsername = 'foo'): Session&MockObject {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('foo');
		$this->manager->method('get')->with($cookieUsername)->willReturn($user);

		return $this->getMockBuilder(Session::class)
			->onlyMethods(['setMagicInCookie'])
			->setConstructorArgs([$this->manager, $this->session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
			->getMock();
	}

	private function createTokenOfFoo(int $remember = IToken::REMEMBER): PublicKeyToken&MockObject {
		$token = $this->createMock(PublicKeyToken::class);
		$token->method('getUID')->willReturn('foo');
		$token->method('getType')->willReturn(IToken::TEMPORARY_TOKEN);
		$token->method('getRemember')->willReturn($remember);

		return $token;
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
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
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
		$this->userConfig->expects($this->once())
			->method('getKeys')
			->with('foo', 'login_token')
			->willReturn([$token]);
		// A login that cannot renew the session token keeps the remember-me token it was given
		$this->userConfig->expects($this->never())
			->method('deleteUserConfig');
		$this->userConfig->expects($this->never())
			->method('setValueString');

		$session->expects($this->once())
			->method('getId')
			->willReturn($sessionId);
		$this->tokenProvider->expects($this->once())
			->method('renewSessionToken')
			->with($oldSessionId, $sessionId)
			->willThrowException(new InvalidTokenException());

		$oldTokenObject = $this->createMock(IToken::class);
		$oldTokenObject->expects($this->once())
			->method('getUID')
			->willReturn('foo');

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->willReturn($oldTokenObject);

		$user->expects($this->once())
			->method('getUID')
			->willReturn('foo');
		$userSession->expects($this->never())
			->method('setMagicInCookie');
		$user->expects($this->never())
			->method('updateLastLoginTimestamp');
		$session->expects($this->never())
			->method('set')
			->with('user_id', 'foo');

		$this->logger->expects($this->once())
			->method('error')
			->with($this->stringStartsWith('Renewing session token failed'), $this->anything());

		$granted = $userSession->loginWithCookie('foo', $token, $oldSessionId);

		$this->assertFalse($granted);
	}

	public function testRememberLoginMissingSessionToken(): void {
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
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
			->getMock();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('foo');
		$token = 'goodToken';
		$oldSessionId = 'sess321';

		$session->expects($this->never())
			->method('regenerateId');
		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($user);
		$this->userConfig->expects($this->once())
			->method('getKeys')
			->with('foo', 'login_token')
			->willReturn([$token]);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($oldSessionId)
			->willThrowException(new InvalidTokenException());

		$this->userConfig->expects($this->never())
			->method('deleteUserConfig');
		$this->tokenProvider->expects($this->never())
			->method('renewSessionToken');
		$userSession->expects($this->never())
			->method('setMagicInCookie');
		$session->expects($this->never())
			->method('set')
			->with('user_id', 'foo');

		$this->logger->expects($this->once())
			->method('error')
			->with('Could not find the session token to renew', $this->anything());

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
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
			->getMock();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('foo');
		$token = 'goodToken';
		$oldSessionId = 'sess321';

		$session->expects($this->never())
			->method('regenerateId');
		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($user);
		$this->userConfig->expects($this->once())
			->method('getKeys')
			->with('foo', 'login_token')
			->willReturn(['anothertoken']);
		$this->userConfig->expects($this->never())
			->method('deleteUserConfig')
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
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
			->getMock();
		$token = 'goodToken';
		$oldSessionId = 'sess321';

		$session->expects($this->never())
			->method('regenerateId');
		$manager->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn(null);
		$this->userConfig->expects($this->never())
			->method('getKeys')
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
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
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
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);

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
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);

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

		$this->assertTrue($userSession->createSessionToken($request, $uid, $loginName, $password, IToken::REMEMBER));
	}

	public function testCreateSessionTokenWithTokenPassword(): void {
		$manager = $this->getMockBuilder(Manager::class)
			->disableOriginalConstructor()
			->getMock();
		$session = $this->createMock(ISession::class);
		$token = $this->createMock(IToken::class);
		$user = $this->createMock(IUser::class);
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);

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
		$userSession = new Session($manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig);
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
		$this->userConfig
			->expects($this->once())
			->method('setValueString')
			->with('UserUid', 'login_token', 'LongRandomToken', 10000);
		$this->userSession
			->expects($this->once())
			->method('setMagicInCookie')
			->with('UserUid', 'LongRandomToken');

		$this->userSession->createRememberMeToken($user);
	}

	public static function renewMagicSessionIdData(): array {
		$cookies = ['nc_username' => 'u', 'nc_token' => 't', 'nc_session_id' => 'old'];
		return [
			'cookie holds the old session id' => ['cookies' => $cookies, 'expectRenewal' => true, 'expectPublish' => true],
			'cookie holds another session id' => ['cookies' => ['nc_session_id' => 'other'] + $cookies, 'expectRenewal' => false, 'expectPublish' => false],
			'no remember-me cookies' => ['cookies' => [], 'expectRenewal' => false, 'expectPublish' => false],
			'remember-me token cookie is an array' => ['cookies' => ['nc_token' => ['t']] + $cookies, 'expectRenewal' => false, 'expectPublish' => false],
			'remember-me token is not stored' => ['cookies' => ['nc_token' => 'stale'] + $cookies, 'expectRenewal' => true, 'expectPublish' => false],
			'username of another user' => ['cookies' => ['nc_username' => 'v'] + $cookies, 'expectRenewal' => true, 'expectPublish' => false],
			'session token was not renewed' => ['cookies' => $cookies, 'expectRenewal' => true, 'expectPublish' => false, 'renewed' => false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'renewMagicSessionIdData')]
	public function testRenewMagicSessionId(array $cookies, bool $expectRenewal, bool $expectPublish, bool $renewed = true): void {
		$this->userConfig->method('getKeys')
			->willReturnCallback(fn (string $uid, string $app) => $uid === 'u' && $app === 'login_token' ? ['t'] : []);
		$token = new PublicKeyToken();
		$token->setUid('u');
		$token->setRemember(IToken::REMEMBER);
		$token->setType(IToken::TEMPORARY_TOKEN);
		$this->tokenProvider->method('getToken')
			->willReturnCallback(fn (string $sessionId): PublicKeyToken => $renewed && $sessionId === 'new' ? $token : throw new InvalidTokenException());
		$this->session->method('getId')->willReturn('new');
		$this->userSession->expects($expectRenewal ? $this->once() : $this->never())
			->method('setMagicInCookie')
			->with($cookies['nc_username'] ?? null, $cookies['nc_token'] ?? null);
		// Requests that still send the old session id continue with the renewed one
		$this->rememberMeTokenGrace->expects($expectPublish ? $this->once() : $this->never())
			->method('remember')
			->with('t', 'old', 't', 'new');

		$request = $this->createMock(IRequest::class);
		$request->method('getCookie')->willReturnCallback(fn (string $key) => $cookies[$key] ?? null);
		$this->overwriteService(IRequest::class, $request);

		$this->userSession->renewMagicSessionId('old');
	}

	public function testRenewMagicSessionIdWithoutASessionIdStillRenewsTheCookie(): void {
		$this->session->method('getId')->willThrowException(new SessionNotAvailableException());
		$request = $this->createMock(IRequest::class);
		$request->method('getCookie')
			->willReturnCallback(fn (string $key) => ['nc_username' => 'u', 'nc_token' => 't', 'nc_session_id' => 'old'][$key] ?? null);
		$this->overwriteService(IRequest::class, $request);

		$this->rememberMeTokenGrace->expects($this->never())
			->method('remember');
		$this->userSession->expects($this->once())
			->method('setMagicInCookie')
			->with('u', 't');

		$this->userSession->renewMagicSessionId('old');
	}

	public static function endInactiveSessionData(): array {
		$cookies = ['nc_username' => 'foo', 'nc_token' => 'fooToken', 'nc_session_id' => 'sess123'];
		return [
			'remembered login' => [
				'cookies' => $cookies,
				'remember' => IToken::REMEMBER,
				'keepsSessionToken' => true,
			],
			'username in another case' => [
				'cookies' => ['nc_username' => 'FOO'] + $cookies,
				'remember' => IToken::REMEMBER,
				'keepsSessionToken' => false,
			],
			'login without remember me' => [
				'cookies' => $cookies,
				'remember' => IToken::DO_NOT_REMEMBER,
				'keepsSessionToken' => false,
			],
			'no remember-me cookies' => [
				'cookies' => [],
				'remember' => IToken::REMEMBER,
				'keepsSessionToken' => false,
			],
			'cookies point at another session' => [
				'cookies' => ['nc_session_id' => 'other'] + $cookies,
				'remember' => IToken::REMEMBER,
				'keepsSessionToken' => false,
			],
			'cookies of another user' => [
				'cookies' => ['nc_username' => 'bar', 'nc_token' => 'barToken'] + $cookies,
				'remember' => IToken::REMEMBER,
				'keepsSessionToken' => false,
			],
			'remember-me token of another user' => [
				'cookies' => ['nc_token' => 'barToken'] + $cookies,
				'remember' => IToken::REMEMBER,
				'keepsSessionToken' => false,
			],
			'remember-me token is not stored' => [
				'cookies' => ['nc_token' => 'guessed'] + $cookies,
				'remember' => IToken::REMEMBER,
				'keepsSessionToken' => false,
			],
			'remember-me token cookie is an array' => [
				'cookies' => ['nc_token' => ['fooToken']] + $cookies,
				'remember' => IToken::REMEMBER,
				'keepsSessionToken' => false,
			],
			'session token is an app password' => [
				'cookies' => $cookies,
				'remember' => IToken::REMEMBER,
				'keepsSessionToken' => false,
				'type' => IToken::PERMANENT_TOKEN,
			],
			'session token missing' => [
				'cookies' => $cookies,
				'remember' => null,
				'keepsSessionToken' => false,
			],
		];
	}

	#[DataProvider(methodName: 'endInactiveSessionData')]
	public function testEndInactiveSession(array $cookies, ?int $remember, bool $keepsSessionToken, int $type = IToken::TEMPORARY_TOKEN): void {
		$userSession = $this->createUserSessionForTimeout($this->tokenProvider, ['getUser']);
		$userSession->method('getUser')->willReturn($this->createMock(IUser::class));
		$sessionId = 'sess123';
		$this->session->method('getId')->willReturnCallback(function () use (&$sessionId): string {
			return $sessionId;
		});
		$this->session->expects($this->once())
			->method('clear')
			->willReturnCallback(function () use (&$sessionId): void {
				$sessionId = 'sess456';
			});
		$this->mockRememberedLogin($cookies, $remember, $type);

		$this->tokenProvider->expects($keepsSessionToken ? $this->once() : $this->never())
			->method('renewSessionToken')
			->with('sess123', 'sess456');
		$userSession->expects($keepsSessionToken ? $this->once() : $this->never())
			->method('setMagicInCookie')
			->with($cookies['nc_username'] ?? null, 'fooToken');
		$this->tokenProvider->expects($keepsSessionToken ? $this->never() : $this->once())
			->method('invalidateToken')
			->with('sess123');
		$userSession->expects($keepsSessionToken ? $this->never() : $this->once())
			->method('unsetMagicInCookie');
		$this->dispatcher->expects($keepsSessionToken ? $this->never() : $this->atLeastOnce())
			->method('dispatchTyped');

		$userSession->endInactiveSession();
	}

	public static function endInactiveSessionWithoutTokenLookupData(): array {
		return [
			'no token provider' => ['hasTokenProvider' => false, 'hasSessionId' => true],
			'no session id' => ['hasTokenProvider' => true, 'hasSessionId' => false],
		];
	}

	#[DataProvider(methodName: 'endInactiveSessionWithoutTokenLookupData')]
	public function testEndInactiveSessionWithoutTokenLookupLogsOut(bool $hasTokenProvider, bool $hasSessionId): void {
		$userSession = $this->createUserSessionForTimeout($hasTokenProvider ? $this->tokenProvider : null, ['getUser']);
		$userSession->method('getUser')->willReturn(null);
		if ($hasSessionId) {
			$this->session->method('getId')->willReturn('sess123');
		} else {
			$this->session->method('getId')->willThrowException(new SessionNotAvailableException());
		}
		$this->mockRememberedLogin(['nc_username' => 'foo', 'nc_token' => 'fooToken', 'nc_session_id' => 'sess123'], IToken::REMEMBER);

		$userSession->expects($this->once())
			->method('unsetMagicInCookie');
		$this->session->expects($this->once())
			->method('clear');

		$userSession->endInactiveSession();
	}

	public function testEndInactiveSessionForgetsTheActiveUser(): void {
		$user = $this->createMock(IUser::class);
		$this->manager->method('get')->with('foo')->willReturn($user);
		$sessionData = ['user_id' => 'foo'];
		$this->session->method('get')->willReturnCallback(function (string $key) use (&$sessionData) {
			return $sessionData[$key] ?? null;
		});
		$this->session->method('clear')->willReturnCallback(function () use (&$sessionData): void {
			$sessionData = [];
		});
		$this->session->method('getId')->willReturn('sess123');
		$this->mockRememberedLogin(['nc_username' => 'foo', 'nc_token' => 'fooToken', 'nc_session_id' => 'sess123'], IToken::REMEMBER);
		$userSession = $this->createUserSessionForTimeout($this->tokenProvider, ['validateSession']);
		$this->assertSame($user, $userSession->getUser());

		$userSession->endInactiveSession();

		$this->assertNull($userSession->getUser());
	}

	public function testEndInactiveSessionPublishesTheMovedSessionToken(): void {
		$userSession = $this->createUserSessionForTimeout($this->tokenProvider, ['getUser']);
		$userSession->method('getUser')->willReturn($this->createMock(IUser::class));
		$sessionId = 'sess123';
		$this->session->method('getId')->willReturnCallback(function () use (&$sessionId): string {
			return $sessionId;
		});
		$this->session->method('clear')->willReturnCallback(function () use (&$sessionId): void {
			$sessionId = 'sess456';
		});
		// The session token is looked up at sess123 before and at sess456 after it moved
		$this->mockRememberedLogin(['nc_username' => 'foo', 'nc_token' => 'fooToken', 'nc_session_id' => 'sess123'], IToken::REMEMBER, sessionTokenIds: ['sess123', 'sess456']);

		// Requests that still send the old session id continue with the moved session token
		$this->rememberMeTokenGrace->expects($this->once())
			->method('remember')
			->with('fooToken', 'sess123', 'fooToken', 'sess456');

		$userSession->endInactiveSession();
	}

	public function testEndInactiveSessionWithAVanishedSessionToken(): void {
		$userSession = $this->createUserSessionForTimeout($this->tokenProvider, ['getUser']);
		$userSession->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->session->method('getId')->willReturn('sess123');
		$this->mockRememberedLogin(['nc_username' => 'foo', 'nc_token' => 'fooToken', 'nc_session_id' => 'sess123'], IToken::REMEMBER);
		$this->tokenProvider->method('renewSessionToken')->willThrowException(new InvalidTokenException());

		$userSession->expects($this->never())
			->method('setMagicInCookie');

		$userSession->endInactiveSession();
	}

	public function testEndInactiveSessionWithoutANewSessionId(): void {
		$userSession = $this->createUserSessionForTimeout($this->tokenProvider, ['getUser']);
		$userSession->method('getUser')->willReturn($this->createMock(IUser::class));
		$lookups = 0;
		$this->session->method('getId')->willReturnCallback(function () use (&$lookups): string {
			// The cleared session has no id to move the token to
			if (++$lookups > 2) {
				throw new SessionNotAvailableException();
			}
			return 'sess123';
		});
		$this->mockRememberedLogin(['nc_username' => 'foo', 'nc_token' => 'fooToken', 'nc_session_id' => 'sess123'], IToken::REMEMBER);

		$this->tokenProvider->expects($this->never())
			->method('renewSessionToken');
		$userSession->expects($this->never())
			->method('setMagicInCookie');

		$userSession->endInactiveSession();
	}

	public static function endInactiveSessionAfterAConcurrentTimeoutData(): array {
		return [
			'successor is a remembered session' => ['successor' => ['token' => 'fooToken', 'sessionId' => 'sess789'], 'keepsLogin' => true],
			'successor session token is gone' => ['successor' => ['token' => 'fooToken', 'sessionId' => 'sess000'], 'keepsLogin' => false],
			'successor remember-me token is gone' => ['successor' => ['token' => 'loggedOut', 'sessionId' => 'sess789'], 'keepsLogin' => false],
			'no successor' => ['successor' => null, 'keepsLogin' => false],
		];
	}

	#[DataProvider(methodName: 'endInactiveSessionAfterAConcurrentTimeoutData')]
	public function testEndInactiveSessionAfterAConcurrentTimeout(?array $successor, bool $keepsLogin): void {
		$userSession = $this->createUserSessionForTimeout($this->tokenProvider, ['getUser']);
		$userSession->method('getUser')->willReturn($this->createMock(IUser::class));
		$sessionId = 'sess123';
		$this->session->method('getId')->willReturnCallback(function () use (&$sessionId): string {
			return $sessionId;
		});
		$this->session->expects($this->once())
			->method('clear')
			->willReturnCallback(function () use (&$sessionId): void {
				$sessionId = 'sess456';
			});
		// The concurrent request already moved the session token from sess123 to sess789
		$this->mockRememberedLogin(['nc_username' => 'foo', 'nc_token' => 'fooToken', 'nc_session_id' => 'sess123'], IToken::REMEMBER, sessionTokenIds: ['sess789']);
		$this->rememberMeTokenGrace->method('resolve')
			->with('fooToken', 'sess123')
			->willReturn($successor);
		$this->tokenProvider->method('renewSessionToken')->willThrowException(new InvalidTokenException());

		// The cookies of the concurrent request stay, so the remember-me login continues with its successor
		$userSession->expects($keepsLogin ? $this->never() : $this->once())
			->method('unsetMagicInCookie');
		$userSession->expects($this->never())
			->method('setMagicInCookie');

		$userSession->endInactiveSession();
	}

	/**
	 * A user session whose cookie handling and $mockedMethods are mocked
	 */
	private function createUserSessionForTimeout(?IProvider $tokenProvider, array $mockedMethods): Session&MockObject {
		return $this->getMockBuilder(Session::class)
			->setConstructorArgs([$this->manager, $this->session, $this->timeFactory, $tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
			->onlyMethods(['unsetMagicInCookie', 'setMagicInCookie', ...$mockedMethods])
			->getMock();
	}

	/**
	 * Remember-me tokens fooToken for user foo and barToken for user bar, a session token of foo at each of
	 * $sessionTokenIds with the remember flag $remember (missing if null) and the type $type, and a request with
	 * $cookies. The user manager knows no user, as no user backend is set up yet when a session times out.
	 */
	private function mockRememberedLogin(array $cookies, ?int $remember, int $type = IToken::TEMPORARY_TOKEN, array $sessionTokenIds = ['sess123']): void {
		$request = $this->createMock(IRequest::class);
		$request->method('getCookie')->willReturnCallback(fn (string $key) => $cookies[$key] ?? null);
		$this->overwriteService(IRequest::class, $request);
		$storedTokens = ['foo' => ['fooToken'], 'bar' => ['barToken']];
		$this->userConfig->method('getKeys')
			->willReturnCallback(fn (string $uid, string $app) => $app === 'login_token' ? ($storedTokens[$uid] ?? []) : []);
		if ($remember === null) {
			$this->tokenProvider->method('getToken')->willThrowException(new InvalidTokenException());
			return;
		}
		$token = new PublicKeyToken();
		$token->setUid('foo');
		$token->setRemember($remember);
		$token->setType($type);
		$this->tokenProvider->method('getToken')
			->willReturnCallback(fn (string $sessionId): PublicKeyToken => in_array($sessionId, $sessionTokenIds, true) ? $token : throw new InvalidTokenException());
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

		$this->session
			->method('set')
			->willReturnCallback(function (string $k, $v) use (&$davAuthenticatedSet): void {
				switch ($k) {
					case Auth::DAV_AUTHENTICATED:
						$davAuthenticatedSet = $v;
						return;
					default:
						throw new \Exception();
				}
			});

		/** @var Session&MockObject $userSession */
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
				$this->dispatcher,
				$this->rememberMeTokenGrace,
				$this->userConfig,
			])
			->onlyMethods([
				'logClientIn',
				'getUser',
			])
			->getMock();

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

		/** @var string|false $davAuthenticatedSet */
		$this->assertSame('username', $davAuthenticatedSet);
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
				$this->dispatcher,
				$this->rememberMeTokenGrace,
				$this->userConfig,
			])
			->onlyMethods([
				'logClientIn',
			])
			->getMock();

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

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
			->onlyMethods(['login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('I-AM-A-PASSWORD')
			->willReturn($this->createMock(IToken::class));
		$userSession->expects($this->once())
			->method('login')
			->with('john', 'I-AM-A-PASSWORD')
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
			->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(
				function (Event $event): void {
					if ($event instanceof LoginFailed) {
						$this->assertEquals($event, new LoginFailed('john', 'I-AM-A-PASSWORD'));
					} elseif ($event instanceof BeforeUserLoggedInEvent) {
						$this->assertEquals($event, new BeforeUserLoggedInEvent('john', 'I-AM-A-PASSWORD'));
					} else {
						$this->fail('Unexpected event');
					}
				}
			);

		$this->assertFalse($userSession->logClientIn('john', 'I-AM-A-PASSWORD', $request, $this->throttler));
	}

	public function testLogClientInThrottlerEmail(): void {
		$manager = $this->createMock(Manager::class);
		$manager
			->method('getUserNameFromLoginName')
			->willReturnArgument(0);
		$session = $this->createMock(ISession::class);
		$request = $this->createMock(IRequest::class);

		$userSession = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$manager, $session, $this->timeFactory, $this->tokenProvider, $this->config, $this->random, $this->lockdownManager, $this->logger, $this->dispatcher, $this->rememberMeTokenGrace, $this->userConfig])
			->onlyMethods(['login', 'supportsCookies', 'createSessionToken', 'getUser'])
			->getMock();

		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('I-AM-A-PASSWORD')
			->willThrowException(new InvalidTokenException());
		$userSession->expects($this->once())
			->method('login')
			->with('john@foo.bar', 'I-AM-A-PASSWORD')
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
			->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(
				function (Event $event): void {
					if ($event instanceof LoginFailed) {
						$this->assertEquals($event, new LoginFailed('john@foo.bar', 'I-AM-A-PASSWORD'));
					} elseif ($event instanceof BeforeUserLoggedInEvent) {
						$this->assertEquals($event, new BeforeUserLoggedInEvent('john@foo.bar', 'I-AM-A-PASSWORD'));
					} else {
						$this->fail('Unexpected event');
					}
				}
			);

		$this->assertFalse($userSession->logClientIn('john@foo.bar', 'I-AM-A-PASSWORD', $request, $this->throttler));
	}

}
