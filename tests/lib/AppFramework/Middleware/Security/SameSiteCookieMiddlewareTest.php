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
use Test\TestCase;

class SameSiteCookieMiddlewareTest extends TestCase {
	/** @var SameSiteCookieMiddleware */
	private $middleware;

	/** @var Request|\PHPUnit\Framework\MockObject\MockObject */
	private $request;

	/** @var ControllerMethodReflector|\PHPUnit\Framework\MockObject\MockObject */
	private $reflector;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(Request::class);
		$this->reflector = $this->createMock(ControllerMethodReflector::class);
		$this->middleware = new SameSiteCookieMiddleware($this->request, $this->reflector);
	}

	public function testBeforeControllerNoIndex(): void {
		$this->request->method('getScriptName')
			->willReturn('/ocs/v2.php');

		$this->middleware->beforeController($this->createMock(Controller::class), 'foo');
		$this->addToAssertionCount(1);
	}

	public function testBeforeControllerIndexHasAnnotation(): void {
		$this->request->method('getScriptName')
			->willReturn('/index.php');

		$this->reflector->method('hasAnnotation')
			->with('NoSameSiteCookieRequired')
			->willReturn(true);

		$this->middleware->beforeController($this->createMock(Controller::class), 'foo');
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

		$this->middleware->beforeController($this->createMock(Controller::class), 'foo');
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

		$this->middleware->beforeController($this->createMock(Controller::class), 'foo');
	}

	public function testAfterExceptionNoLaxCookie(): void {
		$ex = new SecurityException();

		try {
			$this->middleware->afterException($this->createMock(Controller::class), 'foo', $ex);
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
			->setConstructorArgs([$this->request, $this->reflector])
			->setMethods(['setSameSiteCookie'])
			->getMock();

		$middleware->expects($this->once())
			->method('setSameSiteCookie');

		$resp = $middleware->afterException($this->createMock(Controller::class), 'foo', $ex);

		$this->assertSame(Http::STATUS_FOUND, $resp->getStatus());

		$headers = $resp->getHeaders();
		$this->assertSame('/myrequri', $headers['Location']);
	}
}
