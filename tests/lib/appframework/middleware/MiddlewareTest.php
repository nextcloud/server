<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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


namespace OC\AppFramework;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Middleware;


class ChildMiddleware extends Middleware {};


class MiddlewareTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Middleware
	 */
	private $middleware;
	private $controller;
	private $exception;
	private $api;

	protected function setUp(){
		$this->middleware = new ChildMiddleware();

		$this->api = $this->getMock('OC\AppFramework\DependencyInjection\DIContainer',
					array(), array('test'));

		$this->controller = $this->getMock('OCP\AppFramework\Controller',
				array(), array($this->api, new Request()));
		$this->exception = new \Exception();
		$this->response = $this->getMock('OCP\AppFramework\Http\Response');
	}


	public function testBeforeController() {
		$this->middleware->beforeController($this->controller, null);
		$this->assertNull(null);
	}


	public function testAfterExceptionRaiseAgainWhenUnhandled() {
		$this->setExpectedException('Exception');
		$afterEx = $this->middleware->afterException($this->controller, null, $this->exception);
	}


	public function testAfterControllerReturnResponseWhenUnhandled() {
		$response = $this->middleware->afterController($this->controller, null, $this->response);

		$this->assertEquals($this->response, $response);
	}


	public function testBeforeOutputReturnOutputhenUnhandled() {
		$output = $this->middleware->beforeOutput($this->controller, null, 'test');

		$this->assertEquals('test', $output);
	}


}
