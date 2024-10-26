<?php
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
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Security\Bruteforce\IThrottler;

class PublicShareMiddlewareTest extends \Test\TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IThrottler|\PHPUnit\Framework\MockObject\MockObject */
	private $throttler;

	/** @var PublicShareMiddleware */
	private $middleware;


	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->throttler = $this->createMock(IThrottler::class);

		$this->middleware = new PublicShareMiddleware(
			$this->request,
			$this->session,
			$this->config,
			$this->throttler
		);
	}

	public function testBeforeControllerNoPublicShareController(): void {
		$controller = $this->createMock(Controller::class);

		$this->middleware->beforeController($controller, 'method');
		$this->assertTrue(true);
	}

	public function dataShareApi() {
		return [
			['no', 'no',],
			['no', 'yes',],
			['yes', 'no',],
		];
	}

	/**
	 * @dataProvider dataShareApi
	 */
	public function testBeforeControllerShareApiDisabled(string $shareApi, string $shareLinks): void {
		$controller = $this->createMock(PublicShareController::class);

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_enabled', 'yes', $shareApi],
				['core', 'shareapi_allow_links', 'yes', $shareLinks],
			]);

		$this->expectException(NotFoundException::class);
		$this->middleware->beforeController($controller, 'mehod');
	}

	public function testBeforeControllerNoTokenParam(): void {
		$controller = $this->createMock(PublicShareController::class);

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_allow_links', 'yes', 'yes'],
			]);

		$this->expectException(NotFoundException::class);
		$this->middleware->beforeController($controller, 'mehod');
	}

	public function testBeforeControllerInvalidToken(): void {
		$controller = $this->createMock(PublicShareController::class);

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_allow_links', 'yes', 'yes'],
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

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_allow_links', 'yes', 'yes'],
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

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_allow_links', 'yes', 'yes'],
			]);

		$this->request->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->method('isValidToken')
			->willReturn(true);

		$controller->method('isPasswordProtected')
			->willReturn(true);

		$this->middleware->beforeController($controller, 'authenticate');
		$this->assertTrue(true);
	}

	public function testBeforeControllerValidTokenShowAuthenticateMethod(): void {
		$controller = $this->getMockBuilder(PublicShareController::class)
			->setConstructorArgs(['app', $this->request, $this->session])
			->getMock();

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_allow_links', 'yes', 'yes'],
			]);

		$this->request->method('getParam')
			->with('token', null)
			->willReturn('myToken');

		$controller->method('isValidToken')
			->willReturn(true);

		$controller->method('isPasswordProtected')
			->willReturn(true);

		$this->middleware->beforeController($controller, 'showAuthenticate');
		$this->assertTrue(true);
	}

	public function testBeforeControllerAuthPublicShareController(): void {
		$controller = $this->getMockBuilder(AuthPublicShareController::class)
			->setConstructorArgs(['app', $this->request, $this->session, $this->createMock(IURLGenerator::class)])
			->getMock();

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_allow_links', 'yes', 'yes'],
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
