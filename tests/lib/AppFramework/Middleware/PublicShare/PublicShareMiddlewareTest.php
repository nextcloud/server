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

class PublicShareMiddlewareTest extends \Test\TestCase {
	private PublicShareMiddleware $middleware;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->middleware = $this->createInstanceWithMocks(PublicShareMiddleware::class);
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

		$this->mocks[IAppConfig::class]->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, $shareApi],
				['core', 'shareapi_allow_links', true, $shareLinks],
			]);

		$this->expectException(NotFoundException::class);
		$this->middleware->beforeController($controller, 'mehod');
	}

	public function testBeforeControllerNoTokenParam(): void {
		$controller = $this->createMock(PublicShareController::class);

		$this->mocks[IAppConfig::class]->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->expectException(NotFoundException::class);
		$this->middleware->beforeController($controller, 'mehod');
	}

	public function testBeforeControllerInvalidToken(): void {
		$controller = $this->createMock(PublicShareController::class);

		$this->mocks[IAppConfig::class]->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->mocks[IRequest::class]->method('getParam')
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
			->setConstructorArgs(['app', $this->mocks[IRequest::class], $this->mocks[ISession::class]])
			->getMock();

		$this->mocks[IAppConfig::class]->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->mocks[IRequest::class]->method('getParam')
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
			->setConstructorArgs(['app', $this->mocks[IRequest::class], $this->mocks[ISession::class]])
			->getMock();

		$this->mocks[IAppConfig::class]->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->mocks[IRequest::class]->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->expects($this->once())
			->method('isValidToken')
			->willReturn(true);

		$this->middleware->beforeController($controller, 'authenticate');
	}

	public function testBeforeControllerValidTokenShowAuthenticateMethod(): void {
		$controller = $this->getMockBuilder(PublicShareController::class)
			->setConstructorArgs(['app', $this->mocks[IRequest::class], $this->mocks[ISession::class]])
			->getMock();

		$this->mocks[IAppConfig::class]->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->mocks[IRequest::class]->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->expects($this->once())
			->method('isValidToken')
			->willReturn(true);

		$this->middleware->beforeController($controller, 'showAuthenticate');
	}

	public function testBeforeControllerAuthPublicShareController(): void {
		$controller = $this->getMockBuilder(AuthPublicShareController::class)
			->setConstructorArgs(['app', $this->mocks[IRequest::class], $this->mocks[ISession::class], $this->createMock(IURLGenerator::class)])
			->getMock();

		$this->mocks[IAppConfig::class]->method('getValueBool')
			->willReturnMap([
				['core', 'shareapi_enabled', true, true],
				['core', 'shareapi_allow_links', true, true],
			]);

		$this->mocks[IRequest::class]->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->method('isValidToken')
			->willReturn(true);

		$controller->method('isPasswordProtected')
			->willReturn(true);

		$this->mocks[ISession::class]->expects($this->once())
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
				$this->mocks[IRequest::class],
				$this->mocks[ISession::class],
				$this->createMock(IURLGenerator::class),
			])->getMock();
		$controller->setToken('token');

		$exception = new NeedAuthenticationException();

		$this->mocks[IRequest::class]->method('getParam')
			->with('_route')
			->willReturn('my.route');

		$result = $this->middleware->afterException($controller, 'method', $exception);
		$this->assertInstanceOf(RedirectResponse::class, $result);
	}
}
