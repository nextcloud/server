<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\RateLimitingMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TestRateLimitController extends Controller {
	/**
	 * @UserRateThrottle(limit=20, period=200)
	 * @AnonRateThrottle(limit=10, period=100)
	 */
	public function testMethodWithAnnotation() {
	}

	/**
	 * @AnonRateThrottle(limit=10, period=100)
	 */
	public function testMethodWithAnnotationFallback() {
	}

	public function testMethodWithoutAnnotation() {
	}

	#[UserRateLimit(limit: 20, period: 200)]
	#[AnonRateLimit(limit: 10, period: 100)]
	public function testMethodWithAttributes() {
	}

	#[AnonRateLimit(limit: 10, period: 100)]
	public function testMethodWithAttributesFallback() {
	}
}

/**
 * @group DB
 */
class RateLimitingMiddlewareTest extends TestCase {
	private IRequest|MockObject $request;
	private IUserSession|MockObject $userSession;
	private ControllerMethodReflector $reflector;
	private Limiter|MockObject $limiter;
	private RateLimitingMiddleware $rateLimitingMiddleware;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->reflector = new ControllerMethodReflector();
		$this->limiter = $this->createMock(Limiter::class);

		$this->rateLimitingMiddleware = new RateLimitingMiddleware(
			$this->request,
			$this->userSession,
			$this->reflector,
			$this->limiter
		);
	}

	public function testBeforeControllerWithoutAnnotationForAnon(): void {
		$this->limiter
			->expects($this->never())
			->method('registerUserRequest');
		$this->limiter
			->expects($this->never())
			->method('registerAnonRequest');

		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);

		/** @var TestRateLimitController|MockObject $controller */
		$controller = $this->createMock(TestRateLimitController::class);
		$this->reflector->reflect($controller, 'testMethodWithoutAnnotation');
		$this->rateLimitingMiddleware->beforeController($controller, 'testMethodWithoutAnnotation');
	}

	public function testBeforeControllerWithoutAnnotationForLoggedIn(): void {
		$this->limiter
			->expects($this->never())
			->method('registerUserRequest');
		$this->limiter
			->expects($this->never())
			->method('registerAnonRequest');

		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);

		/** @var TestRateLimitController|MockObject $controller */
		$controller = $this->createMock(TestRateLimitController::class);
		$this->reflector->reflect($controller, 'testMethodWithoutAnnotation');
		$this->rateLimitingMiddleware->beforeController($controller, 'testMethodWithoutAnnotation');
	}

	public function testBeforeControllerForAnon(): void {
		$controller = new TestRateLimitController('test', $this->request);

		$this->request
			->method('getRemoteAddress')
			->willReturn('127.0.0.1');

		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);

		$this->limiter
			->expects($this->never())
			->method('registerUserRequest');
		$this->limiter
			->expects($this->once())
			->method('registerAnonRequest')
			->with(get_class($controller) . '::testMethodWithAnnotation', '10', '100', '127.0.0.1');

		$this->reflector->reflect($controller, 'testMethodWithAnnotation');
		$this->rateLimitingMiddleware->beforeController($controller, 'testMethodWithAnnotation');
	}

	public function testBeforeControllerForLoggedIn(): void {
		$controller = new TestRateLimitController('test', $this->request);
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);

		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->limiter
			->expects($this->never())
			->method('registerAnonRequest');
		$this->limiter
			->expects($this->once())
			->method('registerUserRequest')
			->with(get_class($controller) . '::testMethodWithAnnotation', '20', '200', $user);


		$this->reflector->reflect($controller, 'testMethodWithAnnotation');
		$this->rateLimitingMiddleware->beforeController($controller, 'testMethodWithAnnotation');
	}

	public function testBeforeControllerAnonWithFallback(): void {
		$controller = new TestRateLimitController('test', $this->request);
		$this->request
			->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('127.0.0.1');

		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);


		$this->limiter
			->expects($this->never())
			->method('registerUserRequest');
		$this->limiter
			->expects($this->once())
			->method('registerAnonRequest')
			->with(get_class($controller) . '::testMethodWithAnnotationFallback', '10', '100', '127.0.0.1');

		$this->reflector->reflect($controller, 'testMethodWithAnnotationFallback');
		$this->rateLimitingMiddleware->beforeController($controller, 'testMethodWithAnnotationFallback');
	}

	public function testBeforeControllerAttributesForAnon(): void {
		$controller = new TestRateLimitController('test', $this->request);

		$this->request
			->method('getRemoteAddress')
			->willReturn('127.0.0.1');

		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);

		$this->limiter
			->expects($this->never())
			->method('registerUserRequest');
		$this->limiter
			->expects($this->once())
			->method('registerAnonRequest')
			->with(get_class($controller) . '::testMethodWithAttributes', '10', '100', '127.0.0.1');

		$this->reflector->reflect($controller, 'testMethodWithAttributes');
		$this->rateLimitingMiddleware->beforeController($controller, 'testMethodWithAttributes');
	}

	public function testBeforeControllerAttributesForLoggedIn(): void {
		$controller = new TestRateLimitController('test', $this->request);
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);

		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->limiter
			->expects($this->never())
			->method('registerAnonRequest');
		$this->limiter
			->expects($this->once())
			->method('registerUserRequest')
			->with(get_class($controller) . '::testMethodWithAttributes', '20', '200', $user);


		$this->reflector->reflect($controller, 'testMethodWithAttributes');
		$this->rateLimitingMiddleware->beforeController($controller, 'testMethodWithAttributes');
	}

	public function testBeforeControllerAttributesAnonWithFallback(): void {
		$controller = new TestRateLimitController('test', $this->request);
		$this->request
			->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('127.0.0.1');

		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);


		$this->limiter
			->expects($this->never())
			->method('registerUserRequest');
		$this->limiter
			->expects($this->once())
			->method('registerAnonRequest')
			->with(get_class($controller) . '::testMethodWithAttributesFallback', '10', '100', '127.0.0.1');

		$this->reflector->reflect($controller, 'testMethodWithAttributesFallback');
		$this->rateLimitingMiddleware->beforeController($controller, 'testMethodWithAttributesFallback');
	}

	public function testAfterExceptionWithOtherException(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('My test exception');

		$controller = new TestRateLimitController('test', $this->request);

		$this->rateLimitingMiddleware->afterException($controller, 'testMethod', new \Exception('My test exception'));
	}

	public function testAfterExceptionWithJsonBody(): void {
		$controller = new TestRateLimitController('test', $this->request);
		$this->request
			->expects($this->once())
			->method('getHeader')
			->with('Accept')
			->willReturn('JSON');

		$result = $this->rateLimitingMiddleware->afterException($controller, 'testMethod', new RateLimitExceededException());
		$expected = new DataResponse([], 429
		);
		$this->assertEquals($expected, $result);
	}

	public function testAfterExceptionWithHtmlBody(): void {
		$controller = new TestRateLimitController('test', $this->request);
		$this->request
			->expects($this->once())
			->method('getHeader')
			->with('Accept')
			->willReturn('html');

		$result = $this->rateLimitingMiddleware->afterException($controller, 'testMethod', new RateLimitExceededException());
		$expected = new TemplateResponse(
			'core',
			'429',
			[],
			TemplateResponse::RENDER_AS_GUEST
		);
		$expected->setStatus(429);
		$this->assertEquals($expected, $result);
		$this->assertIsString($result->render());
	}
}
