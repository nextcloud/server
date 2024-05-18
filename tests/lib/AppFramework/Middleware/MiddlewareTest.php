<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
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
