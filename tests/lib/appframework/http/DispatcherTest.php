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


namespace OC\AppFramework\Http;

use OC\AppFramework\Core\API;
use OC\AppFramework\Middleware\MiddlewareDispatcher;
use OCP\AppFramework\Http;
//require_once(__DIR__ . "/../classloader.php");


class DispatcherTest extends \PHPUnit_Framework_TestCase {


	private $middlewareDispatcher;
	private $dispatcher;
	private $controllerMethod;
	private $response;
	private $lastModified;
	private $etag;
	private $http;

	protected function setUp() {
		$this->controllerMethod = 'test';

		$app = $this->getMockBuilder(
			'OC\AppFramework\DependencyInjection\DIContainer')
			->disableOriginalConstructor()
			->getMock();
		$request = $this->getMockBuilder(
			'\OC\AppFramework\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->http = $this->getMockBuilder(
			'\OC\AppFramework\Http')
			->disableOriginalConstructor()
			->getMock();

		$this->middlewareDispatcher = $this->getMockBuilder(
			'\OC\AppFramework\Middleware\MiddlewareDispatcher')
			->disableOriginalConstructor()
			->getMock();
		$this->controller = $this->getMock(
			'\OCP\AppFramework\Controller',
			array($this->controllerMethod), array($app, $request));
		
		$this->dispatcher = new Dispatcher(
			$this->http, $this->middlewareDispatcher);
		
		$this->response = $this->getMockBuilder(
			'\OCP\AppFramework\Http\Response')
			->disableOriginalConstructor()
			->getMock();

		$this->lastModified = new \DateTime(null, new \DateTimeZone('GMT'));
		$this->etag = 'hi';
	}


	private function setMiddlewareExpections($out=null, 
		$httpHeaders=null, $responseHeaders=array(),
		$ex=false, $catchEx=true) {

		if($ex) {
			$exception = new \Exception();
			$this->middlewareDispatcher->expects($this->once())
				->method('beforeController')
				->with($this->equalTo($this->controller), 
					$this->equalTo($this->controllerMethod))
				->will($this->throwException($exception));
			if($catchEx) {
				$this->middlewareDispatcher->expects($this->once())
					->method('afterException')
					->with($this->equalTo($this->controller), 
						$this->equalTo($this->controllerMethod),
						$this->equalTo($exception))
					->will($this->returnValue($this->response));
			} else {
				$this->middlewareDispatcher->expects($this->once())
					->method('afterException')
					->with($this->equalTo($this->controller), 
						$this->equalTo($this->controllerMethod),
						$this->equalTo($exception))
					->will($this->returnValue(null));
				return;
			}
		} else {
			$this->middlewareDispatcher->expects($this->once())
				->method('beforeController')
				->with($this->equalTo($this->controller), 
					$this->equalTo($this->controllerMethod));
			$this->controller->expects($this->once())
				->method($this->controllerMethod)
				->will($this->returnValue($this->response));
		}

		$this->response->expects($this->once())
			->method('render')
			->will($this->returnValue($out));
		$this->response->expects($this->once())
			->method('getStatus')
			->will($this->returnValue(Http::STATUS_OK));
		$this->response->expects($this->once())
			->method('getLastModified')
			->will($this->returnValue($this->lastModified));
		$this->response->expects($this->once())
			->method('getETag')
			->will($this->returnValue($this->etag));
		$this->response->expects($this->once())
			->method('getHeaders')
			->will($this->returnValue($responseHeaders));
		$this->http->expects($this->once())
			->method('getStatusHeader')
			->with($this->equalTo(Http::STATUS_OK), 
				$this->equalTo($this->lastModified),
				$this->equalTo($this->etag))
			->will($this->returnValue($httpHeaders));
		
		$this->middlewareDispatcher->expects($this->once())
			->method('afterController')
			->with($this->equalTo($this->controller), 
				$this->equalTo($this->controllerMethod),
				$this->equalTo($this->response))
			->will($this->returnValue($this->response));

		$this->middlewareDispatcher->expects($this->once())
			->method('afterController')
			->with($this->equalTo($this->controller), 
				$this->equalTo($this->controllerMethod),
				$this->equalTo($this->response))
			->will($this->returnValue($this->response));

		$this->middlewareDispatcher->expects($this->once())
			->method('beforeOutput')
			->with($this->equalTo($this->controller), 
				$this->equalTo($this->controllerMethod),
				$this->equalTo($out))
			->will($this->returnValue($out));

		
	}


	public function testDispatcherReturnsArrayWith2Entries() {
		$this->setMiddlewareExpections();

		$response = $this->dispatcher->dispatch($this->controller, 
			$this->controllerMethod);
		$this->assertNull($response[0]);
		$this->assertEquals(array(), $response[1]);
		$this->assertNull($response[2]);
	}


	public function testHeadersAndOutputAreReturned(){
		$out = 'yo';
		$httpHeaders = 'Http';
		$responseHeaders = array('hell' => 'yeah');
		$this->setMiddlewareExpections($out, $httpHeaders, $responseHeaders);

		$response = $this->dispatcher->dispatch($this->controller, 
			$this->controllerMethod);

		$this->assertEquals($httpHeaders, $response[0]);
		$this->assertEquals($responseHeaders, $response[1]);
		$this->assertEquals($out, $response[2]);
	}


	public function testExceptionCallsAfterException() {
		$out = 'yo';
		$httpHeaders = 'Http';
		$responseHeaders = array('hell' => 'yeah');
		$this->setMiddlewareExpections($out, $httpHeaders, $responseHeaders, true);		

		$response = $this->dispatcher->dispatch($this->controller, 
			$this->controllerMethod);

		$this->assertEquals($httpHeaders, $response[0]);
		$this->assertEquals($responseHeaders, $response[1]);
		$this->assertEquals($out, $response[2]);
	}


	public function testExceptionThrowsIfCanNotBeHandledByAfterException() {
		$out = 'yo';
		$httpHeaders = 'Http';
		$responseHeaders = array('hell' => 'yeah');
		$this->setMiddlewareExpections($out, $httpHeaders, $responseHeaders, true, false);

		$this->setExpectedException('\Exception');
		$response = $this->dispatcher->dispatch($this->controller, 
			$this->controllerMethod);

	}

}
