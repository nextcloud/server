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
use OCP\Security\Ip\IRemoteAddress;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\AppFramework\Middleware\Security\Mock\PasswordConfirmationMiddlewareController;
use Test\TestCase;

class PasswordConfirmationMiddlewareTest extends TestCase {
	private ControllerMethodReflector $reflector;
	private ISession&MockObject $session;
	private IUserSession&MockObject $userSession;
	private IUser&MockObject $user;
	private PasswordConfirmationMiddleware $middleware;
	private PasswordConfirmationMiddlewareController $controller;
	private ITimeFactory&MockObject $timeFactory;
	private IProvider&MockObject $tokenProvider;
	private LoggerInterface&MockObject $logger;
	private IRequest&MockObject $request;
	private Manager&MockObject $userManager;
	private IRemoteAddress&MockObject $remoteAddress;

	protected function setUp(): void {
		$this->reflector = new ControllerMethodReflector(\OCP\Server::get(LoggerInterface::class));
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
		$this->remoteAddress = $this->createMock(IRemoteAddress::class);

		$this->middleware = new PasswordConfirmationMiddleware(
			$this->reflector,
			$this->session,
			$this->userSession,
			$this->timeFactory,
			$this->tokenProvider,
			$this->logger,
			$this->request,
			$this->userManager,
			$this->remoteAddress,
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
}
