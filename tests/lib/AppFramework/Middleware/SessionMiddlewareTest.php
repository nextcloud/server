<?php

declare(strict_types=1);

/**
 * ownCloud - App Framework
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @copyright Thomas Müller 2014
 */

namespace Test\AppFramework\Middleware;

use OC\AppFramework\Middleware\SessionMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\ISession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SessionMiddlewareTest extends TestCase {
	private ControllerMethodReflector|MockObject $reflector;
	private ISession|MockObject $session;
	private Controller $controller;
	private SessionMiddleware $middleware;

	protected function setUp(): void {
		parent::setUp();

		$this->reflector = $this->createMock(ControllerMethodReflector::class);
		$this->session = $this->createMock(ISession::class);
		$this->controller = new class('app', $this->createMock(IRequest::class)) extends Controller {
			/**
			 * @UseSession
			 */
			public function withAnnotation() {
			}
			#[UseSession]
			public function withAttribute() {
			}
			public function without() {
			}
		};
		$this->middleware = new SessionMiddleware(
			$this->reflector,
			$this->session,
		);
	}

	public function testSessionNotClosedOnBeforeController(): void {
		$this->configureSessionMock(0, 1);
		$this->reflector->expects(self::once())
			->method('hasAnnotation')
			->with('UseSession')
			->willReturn(true);

		$this->middleware->beforeController($this->controller, 'withAnnotation');
	}

	public function testSessionNotClosedOnBeforeControllerWithAttribute(): void {
		$this->configureSessionMock(0, 1);
		$this->reflector->expects(self::once())
			->method('hasAnnotation')
			->with('UseSession')
			->willReturn(false);

		$this->middleware->beforeController($this->controller, 'withAttribute');
	}

	public function testSessionClosedOnAfterController(): void {
		$this->configureSessionMock(1);
		$this->reflector->expects(self::once())
			->method('hasAnnotation')
			->with('UseSession')
			->willReturn(true);

		$this->middleware->afterController($this->controller, 'withAnnotation', new Response());
	}

	public function testSessionClosedOnAfterControllerWithAttribute(): void {
		$this->configureSessionMock(1);
		$this->reflector->expects(self::once())
			->method('hasAnnotation')
			->with('UseSession')
			->willReturn(true);

		$this->middleware->afterController($this->controller, 'withAttribute', new Response());
	}

	public function testSessionReopenedAndClosedOnBeforeController(): void {
		$this->configureSessionMock(1, 1);
		$this->reflector->expects(self::exactly(2))
			->method('hasAnnotation')
			->with('UseSession')
			->willReturn(true);

		$this->middleware->beforeController($this->controller, 'withAnnotation');
		$this->middleware->afterController($this->controller, 'withAnnotation', new Response());
	}

	public function testSessionReopenedAndClosedOnBeforeControllerWithAttribute(): void {
		$this->configureSessionMock(1, 1);
		$this->reflector->expects(self::exactly(2))
			->method('hasAnnotation')
			->with('UseSession')
			->willReturn(false);

		$this->middleware->beforeController($this->controller, 'withAttribute');
		$this->middleware->afterController($this->controller, 'withAttribute', new Response());
	}

	public function testSessionClosedOnBeforeController(): void {
		$this->configureSessionMock(0);
		$this->reflector->expects(self::once())
			->method('hasAnnotation')
			->with('UseSession')
			->willReturn(false);

		$this->middleware->beforeController($this->controller, 'without');
	}

	public function testSessionNotClosedOnAfterController(): void {
		$this->configureSessionMock(0);
		$this->reflector->expects(self::once())
			->method('hasAnnotation')
			->with('UseSession')
			->willReturn(false);

		$this->middleware->afterController($this->controller, 'without', new Response());
	}

	private function configureSessionMock(int $expectedCloseCount, int $expectedReopenCount = 0): void {
		$this->session->expects($this->exactly($expectedCloseCount))
			->method('close');
		$this->session->expects($this->exactly($expectedReopenCount))
			->method('reopen');
	}
}
