<?php

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

class ChildMiddleware extends Middleware {
};


class MiddlewareTest extends \Test\TestCase {
	/**
	 * @var Middleware
	 */
	private $middleware;
	private $controller;
	private $exception;
	private $api;
	/** @var Response */
	private $response;

	protected function setUp(): void {
		parent::setUp();

		$this->middleware = new ChildMiddleware();

		$this->api = $this->getMockBuilder(DIContainer::class)
			->disableOriginalConstructor()
			->getMock();

		$this->controller = $this->getMockBuilder(Controller::class)
			->setMethods([])
			->setConstructorArgs([
				$this->api,
				new Request(
					[],
					$this->createMock(IRequestId::class),
					$this->createMock(IConfig::class)
				)
			])->getMock();
		$this->exception = new \Exception();
		$this->response = $this->getMockBuilder(Response::class)->getMock();
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
