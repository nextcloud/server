<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\BruteForceMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class TestController extends Controller {
	/**
	 * @BruteForceProtection(action=login)
	 */
	public function testMethodWithAnnotation() {
	}

	public function testMethodWithoutAnnotation() {
	}

	#[BruteForceProtection(action: 'single')]
	public function singleAttribute(): void {
	}

	#[BruteForceProtection(action: 'first')]
	#[BruteForceProtection(action: 'second')]
	public function multipleAttributes(): void {
	}
}

class BruteForceMiddlewareTest extends TestCase {
	/** @var ControllerMethodReflector */
	private $reflector;
	/** @var IThrottler|\PHPUnit\Framework\MockObject\MockObject */
	private $throttler;
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $logger;
	private BruteForceMiddleware $bruteForceMiddleware;

	protected function setUp(): void {
		parent::setUp();

		$this->reflector = new ControllerMethodReflector();
		$this->throttler = $this->createMock(IThrottler::class);
		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->bruteForceMiddleware = new BruteForceMiddleware(
			$this->reflector,
			$this->throttler,
			$this->request,
			$this->logger,
		);
	}

	public function testBeforeControllerWithAnnotation(): void {
		$this->request
			->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('127.0.0.1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelayOrThrowOnMax')
			->with('127.0.0.1', 'login');

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'testMethodWithAnnotation');
		$this->bruteForceMiddleware->beforeController($controller, 'testMethodWithAnnotation');
	}

	public function testBeforeControllerWithSingleAttribute(): void {
		$this->request
			->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('::1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelayOrThrowOnMax')
			->with('::1', 'single');

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'singleAttribute');
		$this->bruteForceMiddleware->beforeController($controller, 'singleAttribute');
	}

	public function testBeforeControllerWithMultipleAttributes(): void {
		$this->request
			->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('::1');

		$calls = [
			['::1', 'first'],
			['::1', 'second'],
		];
		$this->throttler
			->expects($this->exactly(2))
			->method('sleepDelayOrThrowOnMax')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
				return 0;
			});

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'multipleAttributes');
		$this->bruteForceMiddleware->beforeController($controller, 'multipleAttributes');
	}

	public function testBeforeControllerWithoutAnnotation(): void {
		$this->request
			->expects($this->never())
			->method('getRemoteAddress');
		$this->throttler
			->expects($this->never())
			->method('sleepDelayOrThrowOnMax');

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'testMethodWithoutAnnotation');
		$this->bruteForceMiddleware->beforeController($controller, 'testMethodWithoutAnnotation');
	}

	public function testAfterControllerWithAnnotationAndThrottledRequest(): void {
		/** @var Response|\PHPUnit\Framework\MockObject\MockObject $response */
		$response = $this->createMock(Response::class);
		$response
			->expects($this->once())
			->method('isThrottled')
			->willReturn(true);
		$response
			->expects($this->once())
			->method('getThrottleMetadata')
			->willReturn([]);
		$this->request
			->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('127.0.0.1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelayOrThrowOnMax')
			->with('127.0.0.1', 'login');
		$this->throttler
			->expects($this->once())
			->method('registerAttempt')
			->with('login', '127.0.0.1');

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'testMethodWithAnnotation');
		$this->bruteForceMiddleware->afterController($controller, 'testMethodWithAnnotation', $response);
	}

	public function testAfterControllerWithAnnotationAndNotThrottledRequest(): void {
		/** @var Response|\PHPUnit\Framework\MockObject\MockObject $response */
		$response = $this->createMock(Response::class);
		$response
			->expects($this->once())
			->method('isThrottled')
			->willReturn(false);
		$this->request
			->expects($this->never())
			->method('getRemoteAddress');
		$this->throttler
			->expects($this->never())
			->method('sleepDelayOrThrowOnMax');
		$this->throttler
			->expects($this->never())
			->method('registerAttempt');

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'testMethodWithAnnotation');
		$this->bruteForceMiddleware->afterController($controller, 'testMethodWithAnnotation', $response);
	}

	public function testAfterControllerWithSingleAttribute(): void {
		/** @var Response|\PHPUnit\Framework\MockObject\MockObject $response */
		$response = $this->createMock(Response::class);
		$response
			->expects($this->once())
			->method('isThrottled')
			->willReturn(true);
		$response
			->expects($this->once())
			->method('getThrottleMetadata')
			->willReturn([]);

		$this->request
			->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('::1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelayOrThrowOnMax')
			->with('::1', 'single');
		$this->throttler
			->expects($this->once())
			->method('registerAttempt')
			->with('single', '::1');

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'singleAttribute');
		$this->bruteForceMiddleware->afterController($controller, 'singleAttribute', $response);
	}

	public function testAfterControllerWithMultipleAttributesGeneralMatch(): void {
		/** @var Response|\PHPUnit\Framework\MockObject\MockObject $response */
		$response = $this->createMock(Response::class);
		$response
			->expects($this->once())
			->method('isThrottled')
			->willReturn(true);
		$response
			->expects($this->once())
			->method('getThrottleMetadata')
			->willReturn([]);

		$this->request
			->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('::1');

		$sleepCalls = [
			['::1', 'first'],
			['::1', 'second'],
		];
		$this->throttler
			->expects($this->exactly(2))
			->method('sleepDelayOrThrowOnMax')
			->willReturnCallback(function () use (&$sleepCalls) {
				$expected = array_shift($sleepCalls);
				$this->assertEquals($expected, func_get_args());
				return 0;
			});

		$attemptCalls = [
			['first', '::1', []],
			['second', '::1', []],
		];
		$this->throttler
			->expects($this->exactly(2))
			->method('registerAttempt')
			->willReturnCallback(function () use (&$attemptCalls): void {
				$expected = array_shift($attemptCalls);
				$this->assertEquals($expected, func_get_args());
			});

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'multipleAttributes');
		$this->bruteForceMiddleware->afterController($controller, 'multipleAttributes', $response);
	}

	public function testAfterControllerWithMultipleAttributesSpecificMatch(): void {
		/** @var Response|\PHPUnit\Framework\MockObject\MockObject $response */
		$response = $this->createMock(Response::class);
		$response
			->expects($this->once())
			->method('isThrottled')
			->willReturn(true);
		$response
			->expects($this->once())
			->method('getThrottleMetadata')
			->willReturn(['action' => 'second']);

		$this->request
			->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('::1');
		$this->throttler
			->expects($this->once())
			->method('sleepDelayOrThrowOnMax')
			->with('::1', 'second');
		$this->throttler
			->expects($this->once())
			->method('registerAttempt')
			->with('second', '::1');

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'multipleAttributes');
		$this->bruteForceMiddleware->afterController($controller, 'multipleAttributes', $response);
	}

	public function testAfterControllerWithoutAnnotation(): void {
		$this->request
			->expects($this->never())
			->method('getRemoteAddress');
		$this->throttler
			->expects($this->never())
			->method('sleepDelayOrThrowOnMax');

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'testMethodWithoutAnnotation');
		/** @var Response|\PHPUnit\Framework\MockObject\MockObject $response */
		$response = $this->createMock(Response::class);
		$this->bruteForceMiddleware->afterController($controller, 'testMethodWithoutAnnotation', $response);
	}

	public function testAfterControllerWithThrottledResponseButUnhandled(): void {
		$this->request
			->expects($this->never())
			->method('getRemoteAddress');
		$this->throttler
			->expects($this->never())
			->method('sleepDelayOrThrowOnMax');

		$controller = new TestController('test', $this->request);
		$this->reflector->reflect($controller, 'testMethodWithoutAnnotation');
		/** @var Response|\PHPUnit\Framework\MockObject\MockObject $response */
		$response = $this->createMock(Response::class);
		$response->method('isThrottled')
			->willReturn(true);

		$this->logger->expects($this->once())
			->method('debug')
			->with('Response for Test\AppFramework\Middleware\Security\TestController::testMethodWithoutAnnotation got bruteforce throttled but has no annotation nor attribute defined.');

		$this->bruteForceMiddleware->afterController($controller, 'testMethodWithoutAnnotation', $response);
	}
}
