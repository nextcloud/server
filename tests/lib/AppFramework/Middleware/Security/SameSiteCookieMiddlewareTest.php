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
	private Request&MockObject $request;
	private ControllerMethodReflector&MockObject $reflector;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(Request::class);
		$this->reflector = $this->createMock(ControllerMethodReflector::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->middleware = new SameSiteCookieMiddleware($this->request, $this->reflector, $this->logger);
	}

	public function testBeforeControllerNoIndex(): void {
		$this->request->method('getScriptName')
			->willReturn('/ocs/v2.php');

		$this->middleware->beforeController(new NoAnnotationController('foo', $this->request), 'foo');
		$this->addToAssertionCount(1);
	}

	public function testBeforeControllerIndexHasAnnotation(): void {
		$this->request->method('getScriptName')
			->willReturn('/index.php');

		$this->reflector->method('hasAnnotation')
			->with('NoSameSiteCookieRequired')
			->willReturn(true);

		$this->middleware->beforeController(new HasAnnotationController('foo', $this->request), 'foo');
		$this->addToAssertionCount(1);
	}

	public function testBeforeControllerIndexNoAnnotationPassingCheck(): void {
		$this->request->method('getScriptName')
			->willReturn('/index.php');

		$this->reflector->method('hasAnnotation')
			->with('NoSameSiteCookieRequired')
			->willReturn(false);

		$this->request->method('passesLaxCookieCheck')
			->willReturn(true);

		$this->middleware->beforeController(new NoAnnotationController('foo', $this->request), 'foo');
		$this->addToAssertionCount(1);
	}

	public function testBeforeControllerIndexNoAnnotationFailingCheck(): void {
		$this->expectException(LaxSameSiteCookieFailedException::class);

		$this->request->method('getScriptName')
			->willReturn('/index.php');

		$this->reflector->method('hasAnnotation')
			->with('NoSameSiteCookieRequired')
			->willReturn(false);

		$this->request->method('passesLaxCookieCheck')
			->willReturn(false);

		$this->middleware->beforeController(new NoAnnotationController('foo', $this->request), 'foo');
	}

	public function testAfterExceptionNoLaxCookie(): void {
		$ex = new SecurityException();

		try {
			$this->middleware->afterException(new NoAnnotationController('foo', $this->request), 'foo', $ex);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertSame($ex, $e);
		}
	}

	public function testAfterExceptionLaxCookie(): void {
		$ex = new LaxSameSiteCookieFailedException();

		$this->request->method('getRequestUri')
			->willReturn('/myrequri');

		$middleware = $this->getMockBuilder(SameSiteCookieMiddleware::class)
			->setConstructorArgs([$this->request, $this->reflector, $this->logger])
			->onlyMethods(['setSameSiteCookie'])
			->getMock();

		$middleware->expects($this->once())
			->method('setSameSiteCookie');

		$resp = $middleware->afterException(new NoAnnotationController('foo', $this->request), 'foo', $ex);

		$this->assertSame(Http::STATUS_FOUND, $resp->getStatus());

		$headers = $resp->getHeaders();
		$this->assertSame('/myrequri', $headers['Location']);
	}
}
