<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Http\Request;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IConfig;
use OCP\IRequestId;
use PHPUnit\Framework\MockObject\MockObject;

class ChildMiddleware extends Middleware {
};

class MiddlewareTest extends \Test\TestCase {
	private ChildMiddleware $middleware;
	private Controller&MockObject $controller;
	private \Exception $exception;
	private DIContainer&MockObject $api;
	private Response&MockObject $response;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->middleware = new ChildMiddleware();

		$this->api = $this->createMock(DIContainer::class);

		$this->controller = $this->getMockBuilder(Controller::class)
			->setConstructorArgs([
				$this->api,
				new Request(
					[],
					$this->createMock(IRequestId::class),
					$this->createMock(IConfig::class)
				)
			])->getMock();
		$this->exception = new \Exception();
		$this->response = $this->createMock(Response::class);
	}

	public function testBeforeController(): void {
		$this->middleware->beforeController($this->controller, '');
		$this->assertNull(null);
	}

	public function testAfterExceptionRaiseAgainWhenUnhandled(): void {
		$this->expectException(\Exception::class);
		$this->middleware->afterException($this->controller, '', $this->exception);
	}

	public function testAfterControllerReturnResponseWhenUnhandled(): void {
		$response = $this->middleware->afterController($this->controller, '', $this->response);

		$this->assertEquals($this->response, $response);
	}

	public function testBeforeOutputReturnOutputhenUnhandled(): void {
		$output = $this->middleware->beforeOutput($this->controller, '', 'test');

		$this->assertEquals('test', $output);
	}
}
