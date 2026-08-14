<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\PublicShare;

use OC\AppFramework\Middleware\PublicShare\Exceptions\NeedAuthenticationException;
use OC\AppFramework\Middleware\PublicShare\PublicShareMiddleware;
use OCP\AppFramework\AuthPublicShareController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\PublicShareController;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\MockObject\MockObject;

class PublicShareMiddlewareTest extends \Test\TestCase {
	private IRequest&MockObject $request;
	private ISession&MockObject $session;
	private IAppConfig&MockObject $appConfig;
	private IThrottler&MockObject $throttler;
	private PublicShareMiddleware $middleware;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->throttler = $this->createMock(IThrottler::class);

		$this->middleware = new PublicShareMiddleware(
			$this->request,
			$this->session,
			$this->appConfig,
			$this->throttler
		);
	}

	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testBeforeControllerNoPublicShareController(): void {
		$controller = $this->createMock(Controller::class);

		$this->middleware->beforeController($controller, 'method');
	}

	public static function dataShareApi(): array {
		return [
			[false, false],
			[false, true],
			[true, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataShareApi')]
	public function testBeforeControllerShareApiDisabled(bool $shareApi, bool $shareLinks): void {
		$controller = $this->createMock(PublicShareController::class);

		$this->appConfig->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, $shareApi],
				['core', 'shareapi_allow_links', true, $shareLinks],
			]);

		$this->expectException(NotFoundException::class);
		$this->middleware->beforeController($controller, 'mehod');
	}

	public function testBeforeControllerNoTokenParam(): void {
		$controller = $this->createMock(PublicShareController::class);

		$this->appConfig->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->expectException(NotFoundException::class);
		$this->middleware->beforeController($controller, 'mehod');
	}

	public function testBeforeControllerInvalidToken(): void {
		$controller = $this->createMock(PublicShareController::class);

		$this->appConfig->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->request->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->method('isValidToken')
			->willReturn(false);
		$controller->expects($this->once())
			->method('shareNotFound');

		$this->expectException(NotFoundException::class);
		$this->middleware->beforeController($controller, 'mehod');
	}

	public function testBeforeControllerValidTokenNotAuthenticated(): void {
		$controller = $this->getMockBuilder(PublicShareController::class)
			->setConstructorArgs(['app', $this->request, $this->session])
			->getMock();

		$this->appConfig->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->request->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->method('isValidToken')
			->willReturn(true);

		$controller->method('isPasswordProtected')
			->willReturn(true);

		$this->expectException(NotFoundException::class);
		$this->middleware->beforeController($controller, 'mehod');
	}

	public function testBeforeControllerValidTokenAuthenticateMethod(): void {
		$controller = $this->getMockBuilder(PublicShareController::class)
			->setConstructorArgs(['app', $this->request, $this->session])
			->getMock();

		$this->appConfig->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->request->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->expects($this->once())
			->method('isValidToken')
			->willReturn(true);

		$this->middleware->beforeController($controller, 'authenticate');
	}

	public function testBeforeControllerValidTokenShowAuthenticateMethod(): void {
		$controller = $this->getMockBuilder(PublicShareController::class)
			->setConstructorArgs(['app', $this->request, $this->session])
			->getMock();

		$this->appConfig->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->request->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->expects($this->once())
			->method('isValidToken')
			->willReturn(true);

		$this->middleware->beforeController($controller, 'showAuthenticate');
	}

	public function testBeforeControllerAuthPublicShareController(): void {
		$controller = $this->getMockBuilder(AuthPublicShareController::class)
			->setConstructorArgs(['app', $this->request, $this->session, $this->createMock(IURLGenerator::class)])
			->getMock();

		$this->appConfig->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->request->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->method('isValidToken')
			->willReturn(true);

		$controller->method('isPasswordProtected')
			->willReturn(true);

		$this->session->expects($this->once())
			->method('set')
			->with('public_link_authenticate_redirect', '[]');

		$this->expectException(NeedAuthenticationException::class);
		$this->middleware->beforeController($controller, 'method');
	}

	public function testAfterExceptionNoPublicShareController(): void {
		$controller = $this->createMock(Controller::class);
		$exception = new \Exception();

		try {
			$this->middleware->afterException($controller, 'method', $exception);
		} catch (\Exception $e) {
			$this->assertEquals($exception, $e);
		}
	}

	public function testAfterExceptionPublicShareControllerNotFoundException(): void {
		$controller = $this->createMock(PublicShareController::class);
		$exception = new NotFoundException();

		$result = $this->middleware->afterException($controller, 'method', $exception);
		$this->assertInstanceOf(TemplateResponse::class, $result);
		$this->assertEquals($result->getStatus(), Http::STATUS_NOT_FOUND);
	}

	public function testAfterExceptionPublicShareController(): void {
		$controller = $this->createMock(PublicShareController::class);
		$exception = new \Exception();

		try {
			$this->middleware->afterException($controller, 'method', $exception);
		} catch (\Exception $e) {
			$this->assertEquals($exception, $e);
		}
	}

	public function testAfterExceptionAuthPublicShareController(): void {
		$controller = $this->getMockBuilder(AuthPublicShareController::class)
			->setConstructorArgs([
				'app',
				$this->request,
				$this->session,
				$this->createMock(IURLGenerator::class),
			])->getMock();
		$controller->setToken('token');

		$exception = new NeedAuthenticationException();

		$this->request->method('getParam')
			->with('_route')
			->willReturn('my.route');

		$result = $this->middleware->afterException($controller, 'method', $exception);
		$this->assertInstanceOf(RedirectResponse::class, $result);
	}
}
