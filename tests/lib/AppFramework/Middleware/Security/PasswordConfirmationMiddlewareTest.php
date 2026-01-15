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

	protected function setUp(): void {
		$this->reflector = new ControllerMethodReflector();
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataProvider')]
	public function testAnnotation($backend, $lastConfirm, $currentTime, $exception): void {
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataProvider')]
	public function testAttribute($backend, $lastConfirm, $currentTime, $exception): void {
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



	public static function dataProvider(): array {
		return [
			['foo', 2000, 4000, true],
			['foo', 2000, 3000, false],
			['user_saml', 2000, 4000, false],
			['user_saml', 2000, 3000, false],
			['foo', 2000, 3815, false],
			['foo', 2000, 3816, true],
		];
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

	//
	// Additional strict-mode / edge-case tests integrated below.
	// These reuse the existing test fixtures (reflector, session, request, tokenProvider, etc.)
	//

	public function testStrictMissingHeaderThrows(): void {
		$this->reflector->reflect($this->controller, 'testStrictAction');

		// Request returns no header
		$this->request->method('getHeader')->with('Authorization')->willReturn(null);

		// token retrieval should succeed
		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->session->method('getId')->willReturn('sid');
		$this->tokenProvider->method('getToken')->willReturn($token);

		$this->expectException(NotConfirmedException::class);
		$this->middleware->beforeController($this->controller, 'testStrictAction');
	}

	public function testStrictMalformedHeaderThrows(): void {
		$this->reflector->reflect($this->controller, 'testStrictAction');

		// malformed forms like "Basic:" should be rejected by the parser
		$this->request->method('getHeader')->with('Authorization')->willReturn('Basic: abc');

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->session->method('getId')->willReturn('sid');
		$this->tokenProvider->method('getToken')->willReturn($token);

		$this->expectException(NotConfirmedException::class);
		$this->middleware->beforeController($this->controller, 'testStrictAction');
	}

	public function testStrictInvalidBase64Throws(): void {
		$this->reflector->reflect($this->controller, 'testStrictAction');

		// token contains invalid base64 characters
		$this->request->method('getHeader')->with('Authorization')->willReturn('Basic !!not-base64!!');

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->session->method('getId')->willReturn('sid');
		$this->tokenProvider->method('getToken')->willReturn($token);

		$this->expectException(NotConfirmedException::class);
		$this->middleware->beforeController($this->controller, 'testStrictAction');
	}

	public function testStrictEmptyDecodedThrows(): void {
		$this->reflector->reflect($this->controller, 'testStrictAction');

		// header with an "empty" base64 payload -> parser will treat as malformed or invalid
		$this->request->method('getHeader')->with('Authorization')->willReturn('Basic ' . base64_encode(''));

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->session->method('getId')->willReturn('sid');
		$this->tokenProvider->method('getToken')->willReturn($token);

		$this->expectException(NotConfirmedException::class);
		$this->middleware->beforeController($this->controller, 'testStrictAction');
	}

	public function testStrictTooLongTokenThrows(): void {
		$this->reflector->reflect($this->controller, 'testStrictAction');

		$long = str_repeat('A', 5000); // valid base64 chars but exceeds 4096 limit
		$this->request->method('getHeader')->with('Authorization')->willReturn('Basic ' . $long);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->session->method('getId')->willReturn('sid');
		$this->tokenProvider->method('getToken')->willReturn($token);

		$this->expectException(NotConfirmedException::class);
		$this->middleware->beforeController($this->controller, 'testStrictAction');
	}

	public function testStrictUrlSafeBase64Rejected(): void {
		$this->reflector->reflect($this->controller, 'testStrictAction');

		// create a standard base64 string and then convert to URL-safe variant
		$normal = base64_encode('alice:secret');
		$urlsafe = strtr($normal, '+/', '-_'); // produce '-' or '_' which the regex rejects

		$this->request->method('getHeader')->with('Authorization')->willReturn('Basic ' . $urlsafe);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->session->method('getId')->willReturn('sid');
		$this->tokenProvider->method('getToken')->willReturn($token);

		$this->expectException(NotConfirmedException::class);
		$this->middleware->beforeController($this->controller, 'testStrictAction');
	}

	public function testNonUtf8DetectionLogsAndContinues(): void {
		$this->reflector->reflect($this->controller, 'testStrictAction');

		// Construct a decoded payload that contains invalid UTF-8 bytes,
		// but still contains a colon separating username and password.
		$decoded = "\x80" . 'alice:secret'; // leading 0x80 byte makes this invalid UTF-8
		$b64 = base64_encode($decoded);
		$this->request->method('getHeader')->with('Authorization')->willReturn('Basic ' . $b64);

		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')->willReturn([]);
		$this->session->method('getId')->willReturn('sid');
		$this->tokenProvider->method('getToken')->willReturn($token);

		// Provide a loginname in session and accept the password check.
		$this->session->method('get')->with('loginname')->willReturn('alice');
		$this->userManager->method('checkPassword')->with('alice', 'secret')->willReturn(true);

		// Expect logger->info to be called with the non-UTF-8 message.
		$this->logger->expects($this->once())->method('info')->with($this->stringContains('Non-UTF-8 Authorization Basic payload detected'));

		// Expect session->set called to record last-password-confirm (timeFactory returns known time)
		$this->timeFactory->method('getTime')->willReturn(12345);
		$this->session->expects($this->once())->method('set')->with('last-password-confirm', 12345);

		$this->middleware->beforeController($this->controller, 'testStrictAction');
	}
}
