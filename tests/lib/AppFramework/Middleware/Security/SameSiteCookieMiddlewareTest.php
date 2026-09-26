<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Http\Request;
use OC\AppFramework\Middleware\Security\Exceptions\LaxSameSiteCookieFailedException;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\AppFramework\Middleware\Security\SameSiteCookieMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoSameSiteCookieRequired;
use OCP\AppFramework\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class HasAnnotationController extends Controller {
	#[NoSameSiteCookieRequired]
	public function foo(): Response {
		return new Response();
	}
}

class NoAnnotationController extends Controller {
	public function foo(): Response {
		return new Response();
	}
}

class SameSiteCookieMiddlewareTest extends TestCase {
	private SameSiteCookieMiddleware $middleware;
	private LoggerInterface&MockObject $logger;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->middleware = $this->createInstanceWithMocks(SameSiteCookieMiddleware::class);
	}

	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testBeforeControllerNoIndex(): void {
		$this->mocks[Request::class]->method('getScriptName')
			->willReturn('/ocs/v2.php');

		$this->middleware->beforeController(new NoAnnotationController('foo', $this->mocks[Request::class]), 'foo');
	}

	public function testBeforeControllerIndexHasAnnotation(): void {
		$this->mocks[Request::class]->method('getScriptName')
			->willReturn('/index.php');

		$this->mocks[ControllerMethodReflector::class]->expects(self::once())
			->method('hasAnnotationOrAttribute')
			->with('NoSameSiteCookieRequired', NoSameSiteCookieRequired::class)
			->willReturn(true);

		$this->middleware->beforeController(new HasAnnotationController('foo', $this->mocks[Request::class]), 'foo');
	}

	public function testBeforeControllerIndexNoAnnotationPassingCheck(): void {
		$this->mocks[Request::class]->method('getScriptName')
			->willReturn('/index.php');

		$this->mocks[ControllerMethodReflector::class]->expects(self::once())
			->method('hasAnnotationOrAttribute')
			->with('NoSameSiteCookieRequired', NoSameSiteCookieRequired::class)
			->willReturn(false);

		$this->mocks[Request::class]->method('passesLaxCookieCheck')
			->willReturn(true);

		$this->middleware->beforeController(new NoAnnotationController('foo', $this->mocks[Request::class]), 'foo');
	}

	public function testBeforeControllerIndexNoAnnotationFailingCheck(): void {
		$this->expectException(LaxSameSiteCookieFailedException::class);

		$this->mocks[Request::class]->method('getScriptName')
			->willReturn('/index.php');

		$this->mocks[ControllerMethodReflector::class]->expects(self::once())
			->method('hasAnnotationOrAttribute')
			->with('NoSameSiteCookieRequired', NoSameSiteCookieRequired::class)
			->willReturn(false);

		$this->mocks[Request::class]->method('passesLaxCookieCheck')
			->willReturn(false);

		$this->middleware->beforeController(new NoAnnotationController('foo', $this->mocks[Request::class]), 'foo');
	}

	public function testAfterExceptionNoLaxCookie(): void {
		$ex = new SecurityException();

		try {
			$this->middleware->afterException(new NoAnnotationController('foo', $this->mocks[Request::class]), 'foo', $ex);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertSame($ex, $e);
		}
	}

	public function testAfterExceptionLaxCookie(): void {
		$ex = new LaxSameSiteCookieFailedException();

		$this->mocks[Request::class]->method('getRequestUri')
			->willReturn('/myrequri');

		$middleware = $this->getMockBuilder(SameSiteCookieMiddleware::class)
			->setConstructorArgs([$this->mocks[Request::class], $this->mocks[ControllerMethodReflector::class]])
			->onlyMethods(['setSameSiteCookie'])
			->getMock();

		$middleware->expects($this->once())
			->method('setSameSiteCookie');

		$resp = $middleware->afterException(new NoAnnotationController('foo', $this->mocks[Request::class]), 'foo', $ex);

		$this->assertSame(Http::STATUS_FOUND, $resp->getStatus());

		$headers = $resp->getHeaders();
		$this->assertSame('/myrequri', $headers['Location']);
	}
}
