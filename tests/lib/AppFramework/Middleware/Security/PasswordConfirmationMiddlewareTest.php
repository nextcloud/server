<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\NotConfirmedException;
use OC\AppFramework\Middleware\Security\PasswordConfirmationMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Authentication\Token\IProvider;
use OC\User\Manager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Token\IToken;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;
use OCP\User\Backend\IPasswordConfirmationBackend;
use Psr\Log\LoggerInterface;
use Test\AppFramework\Middleware\Security\Mock\PasswordConfirmationMiddlewareController;
use Test\TestCase;

class PasswordConfirmationMiddlewareTest extends TestCase {
	/** @var ControllerMethodReflector */
	private $reflector;
	/** @var ISession&\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var IUserSession&\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var IUser&\PHPUnit\Framework\MockObject\MockObject */
	private $user;
	/** @var PasswordConfirmationMiddleware */
	private $middleware;
	/** @var PasswordConfirmationMiddlewareController */
	private $controller;
	/** @var ITimeFactory&\PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;
	private IProvider&\PHPUnit\Framework\MockObject\MockObject $tokenProvider;
	private LoggerInterface $logger;
	/** @var IRequest&\PHPUnit\Framework\MockObject\MockObject */
	private IRequest $request;
	/** @var Manager&\PHPUnit\Framework\MockObject\MockObject */
	private Manager $userManager;

	#[\Override]
	protected function setUp(): void {
		$this->reflector = new ControllerMethodReflector(Server::get(LoggerInterface::class));
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->user = $this->createMock(IUser::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->userManager = $this->createMock(Manager::class);
		$this->controller = new PasswordConfirmationMiddlewareController(
			'test',
			$this->createMock(IRequest::class)
		);

		$this->middleware = new PasswordConfirmationMiddleware(
			$this->reflector,
			$this->session,
			$this->userSession,
			$this->timeFactory,
			$this->tokenProvider,
			$this->logger,
			$this->request,
			$this->userManager,
		);
	}

	public function testNoAnnotationNorAttribute(): void {
		$this->reflector->reflect($this->controller, __FUNCTION__);
		$this->session->expects($this->never())
			->method($this->anything());
		$this->userSession->expects($this->never())
			->method($this->anything());

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public function testDifferentAnnotation(): void {
		$this->reflector->reflect($this->controller, __FUNCTION__);
		$this->session->expects($this->never())
			->method($this->anything());
		$this->userSession->expects($this->never())
			->method($this->anything());

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataProviderNonExemptBackend')]
	public function testAnnotation(string $backend, int $lastConfirm, int $currentTime, bool $exception): void {
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')
			->willReturn($backend);
		$this->userSession->method('getUser')
			->willReturn($this->user);

		$this->session->method('get')
			->with('last-password-confirm')
			->willReturn($lastConfirm);

		$this->timeFactory->method('getTime')
			->willReturn($currentTime);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')
			->willReturn([]);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->willReturn($token);

		$thrown = false;
		try {
			$this->middleware->beforeController($this->controller, __FUNCTION__);
		} catch (NotConfirmedException $e) {
			$thrown = true;
		}

		$this->assertSame($exception, $thrown);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataProviderNonExemptBackend')]
	public function testAttribute(string $backend, int $lastConfirm, int $currentTime, bool $exception): void {
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')
			->willReturn($backend);
		$this->userSession->method('getUser')
			->willReturn($this->user);

		$this->session->method('get')
			->with('last-password-confirm')
			->willReturn($lastConfirm);

		$this->timeFactory->method('getTime')
			->willReturn($currentTime);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')
			->willReturn([]);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->willReturn($token);

		$thrown = false;
		try {
			$this->middleware->beforeController($this->controller, __FUNCTION__);
		} catch (NotConfirmedException $e) {
			$thrown = true;
		}

		$this->assertSame($exception, $thrown);
	}

	public static function dataProviderNonExemptBackend(): array {
		return [
			['foo', 2000, 4000, true],
			['foo', 2000, 3000, false],
			['foo', 2000, 3815, false],
			['foo', 2000, 3816, true],
		];
	}

	/**
	 * @dataProvider dataProviderLegacyExemptBackends
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataProviderLegacyExemptBackends')]
	public function testLegacyBackendExempt(string $backend): void {
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')
			->willReturn($backend);
		$this->userSession->method('getUser')
			->willReturn($this->user);

		// Backend is exempt — getToken() must never be called
		$this->tokenProvider->expects($this->never())
			->method('getToken');

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public static function dataProviderLegacyExemptBackends(): array {
		return [
			['user_saml'],
			['user_globalsiteselector'],
		];
	}

	public function testIPasswordConfirmationBackendExempt(): void {
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$backend = $this->createMock(IPasswordConfirmationBackend::class);
		$backend->method('canConfirmPassword')->with('uid')->willReturn(false);

		$this->user->method('getBackend')->willReturn($backend);
		$this->user->method('getUID')->willReturn('uid');
		$this->userSession->method('getUser')->willReturn($this->user);

		// Exempt before token check
		$this->tokenProvider->expects($this->never())->method('getToken');

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public function testIPasswordConfirmationBackendNotExempt(): void {
		static $sessionId = 'mySession1d';
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$backend = $this->createMock(IPasswordConfirmationBackend::class);
		$backend->method('canConfirmPassword')->with('uid')->willReturn(true);

		$this->user->method('getBackend')->willReturn($backend);
		$this->user->method('getUID')->willReturn('uid');
		$this->user->method('getBackendClassName')->willReturn('capable_backend');
		$this->userSession->method('getUser')->willReturn($this->user);

		$this->session->method('getId')->willReturn($sessionId);
		$this->session->method('get')->with('last-password-confirm')->willReturn(2000);
		$this->timeFactory->method('getTime')->willReturn(3000); // within window — no exception

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($sessionId)
			->willReturn($token);

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public function testNullUser(): void {
		static $sessionId = 'mySession1d';
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->userSession->method('getUser')->willReturn(null);

		$this->session->method('getId')->willReturn($sessionId);
		$this->session->method('get')->with('last-password-confirm')->willReturn(2000);
		$this->timeFactory->method('getTime')->willReturn(3000); // within window — no exception

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($sessionId)
			->willReturn($token);

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public function testStrictModeValidCredentials(): void {
		static $sessionId = 'mySession1d';
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')->willReturn('foo');
		$this->userSession->method('getUser')->willReturn($this->user);
		$this->session->method('getId')->willReturn($sessionId);
		$this->timeFactory->method('getTime')->willReturn(9999);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($sessionId)
			->willReturn($token);

		$this->request->method('getHeader')
			->with('Authorization')
			->willReturn('Basic ' . base64_encode('user:correctpassword'));

		$this->session->method('get')
			->with('loginname')
			->willReturn('user');

		$loginUser = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with('user', 'correctpassword')
			->willReturn($loginUser);

		// Timestamp must be written after successful strict confirmation
		$this->session->expects($this->once())
			->method('set')
			->with('last-password-confirm', 9999);

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public function testStrictModeMissingAuthHeader(): void {
		static $sessionId = 'mySession1d';
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')->willReturn('foo');
		$this->userSession->method('getUser')->willReturn($this->user);
		$this->session->method('getId')->willReturn($sessionId);
		$this->timeFactory->method('getTime')->willReturn(9999);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->tokenProvider->method('getToken')->willReturn($token);

		$this->request->method('getHeader')
			->with('Authorization')
			->willReturn('');  // no header

		$this->session->expects($this->never())->method('set');
		$this->userManager->expects($this->never())->method('checkPassword');
		$this->expectException(NotConfirmedException::class);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	/**
	 * @dataProvider dataProviderMalformedAuthHeaders
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataProviderMalformedAuthHeaders')]
	public function testStrictModeMalformedBase64(string $headerValue): void {
		static $sessionId = 'mySession1d';
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')->willReturn('foo');
		$this->userSession->method('getUser')->willReturn($this->user);
		$this->session->method('getId')->willReturn($sessionId);
		$this->timeFactory->method('getTime')->willReturn(9999);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->tokenProvider->method('getToken')->willReturn($token);

		$this->request->method('getHeader')
			->with('Authorization')
			->willReturn($headerValue);

		$this->session->expects($this->never())->method('set');
		$this->userManager->expects($this->never())->method('checkPassword');
		$this->expectException(NotConfirmedException::class);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public static function dataProviderMalformedAuthHeaders(): array {
		return [
			'invalid base64'       => ['Basic !!!notbase64!!!'],
			'no colon in decoded'  => ['Basic ' . base64_encode('nodivider')],
		];
	}

	public function testStrictModeWrongPassword(): void {
		static $sessionId = 'mySession1d';
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')->willReturn('foo');
		$this->userSession->method('getUser')->willReturn($this->user);
		$this->session->method('getId')->willReturn($sessionId);
		$this->timeFactory->method('getTime')->willReturn(9999);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->tokenProvider->method('getToken')->willReturn($token);

		$this->request->method('getHeader')
			->with('Authorization')
			->willReturn('Basic ' . base64_encode('user:wrongpassword'));

		$this->session->method('get')
			->with('loginname')
			->willReturn('user');

		$this->userManager->method('checkPassword')
			->with('user', 'wrongpassword')
			->willReturn(false);

		$this->session->expects($this->never())->method('set');

		$this->expectException(NotConfirmedException::class);
		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public function testStrictModeLegacyBackendExempt(): void {
		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')->willReturn('user_saml');
		$this->userSession->method('getUser')->willReturn($this->user);

		// Must exit before reaching the auth header or token checks
		$this->tokenProvider->expects($this->never())->method('getToken');
		$this->request->expects($this->never())->method('getHeader');
		$this->userManager->expects($this->never())->method('checkPassword');

		$this->middleware->beforeController($this->controller, __FUNCTION__);
	}

	public function testSSO(): void {
		static $sessionId = 'mySession1d';

		$this->reflector->reflect($this->controller, __FUNCTION__);

		$this->user->method('getBackendClassName')
			->willReturn('fictional_backend');
		$this->userSession->method('getUser')
			->willReturn($this->user);

		$this->session->method('get')
			->with('last-password-confirm')
			->willReturn(0);
		$this->session->method('getId')
			->willReturn($sessionId);

		$this->timeFactory->method('getTime')
			->willReturn(9876);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')
			->willReturn([IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true]);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with($sessionId)
			->willReturn($token);

		$thrown = false;
		try {
			$this->middleware->beforeController($this->controller, __FUNCTION__);
		} catch (NotConfirmedException) {
			$thrown = true;
		}

		$this->assertSame(false, $thrown);
	}
}
