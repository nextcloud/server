<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Http\Dispatcher;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Middleware\MiddlewareDispatcher;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\ParameterOutOfRangeException;
use OCP\AppFramework\Http\Response;
use OCP\Diagnostics\IEventLogger;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IRequestId;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class TestController extends Controller {
	/**
	 * @param string $appName
	 * @param \OCP\IRequest $request
	 */
	public function __construct($appName, $request) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param int $int
	 * @param bool $bool
	 * @param double $foo
	 * @param int $test
	 * @param integer $test2
	 * @return array
	 */
	public function exec($int, $bool, $foo, $test = 4, $test2 = 1) {
		$this->registerResponder('text', function ($in) {
			return new JSONResponse(['text' => $in]);
		});
		return [$int, $bool, $test, $test2];
	}


	/**
	 * @param int $int
	 * @param bool $bool
	 * @param int $test
	 * @param int $test2
	 * @return DataResponse
	 */
	public function execDataResponse($int, $bool, $test = 4, $test2 = 1) {
		return new DataResponse([
			'text' => [$int, $bool, $test, $test2]
		]);
	}

	public function test(): Response {
		return new DataResponse();
	}
}

/**
 * Class DispatcherTest
 *
 * @package Test\AppFramework\Http
 * @group DB
 */
class DispatcherTest extends \Test\TestCase {
	/** @var MiddlewareDispatcher */
	private $middlewareDispatcher;
	/** @var Dispatcher */
	private $dispatcher;
	private $controllerMethod;
	/** @var Controller|MockObject */
	private $controller;
	private $response;
	/** @var IRequest|MockObject */
	private $request;
	private $lastModified;
	private $etag;
	/** @var Http|MockObject */
	private $http;
	private $reflector;
	/** @var IConfig|MockObject */
	private $config;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var IEventLogger|MockObject */
	private $eventLogger;
	/** @var ContainerInterface|MockObject */
	private $container;

	protected function setUp(): void {
		parent::setUp();
		$this->controllerMethod = 'test';

		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->eventLogger = $this->createMock(IEventLogger::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$app = $this->createMock(DIContainer::class);
		$request = $this->createMock(Request::class);
		$this->http = $this->createMock(\OC\AppFramework\Http::class);

		$this->middlewareDispatcher = $this->createMock(MiddlewareDispatcher::class);
		$this->controller = $this->getMockBuilder(TestController::class)
			->onlyMethods([$this->controllerMethod])
			->setConstructorArgs([$app, $request])
			->getMock();

		$this->request = $this->createMock(Request::class);

		$this->reflector = new ControllerMethodReflector();

		$this->dispatcher = new Dispatcher(
			$this->http,
			$this->middlewareDispatcher,
			$this->reflector,
			$this->request,
			$this->config,
			Server::get(IDBConnection::class),
			$this->logger,
			$this->eventLogger,
			$this->container,
		);

		$this->response = $this->createMock(Response::class);

		$this->lastModified = new \DateTime('now', new \DateTimeZone('GMT'));
		$this->etag = 'hi';
	}


	/**
	 * @param string $out
	 * @param string $httpHeaders
	 */
	private function setMiddlewareExpectations($out = null,
		$httpHeaders = null, $responseHeaders = [],
		$ex = false, $catchEx = true) {
		if ($ex) {
			$exception = new \Exception();
			$this->middlewareDispatcher->expects($this->once())
				->method('beforeController')
				->with($this->equalTo($this->controller),
					$this->equalTo($this->controllerMethod))
				->willThrowException($exception);
			if ($catchEx) {
				$this->middlewareDispatcher->expects($this->once())
					->method('afterException')
					->with($this->equalTo($this->controller),
						$this->equalTo($this->controllerMethod),
						$this->equalTo($exception))
					->willReturn($this->response);
			} else {
				$this->middlewareDispatcher->expects($this->once())
					->method('afterException')
					->with($this->equalTo($this->controller),
						$this->equalTo($this->controllerMethod),
						$this->equalTo($exception))
					->willThrowException($exception);
				return;
			}
		} else {
			$this->middlewareDispatcher->expects($this->once())
				->method('beforeController')
				->with($this->equalTo($this->controller),
					$this->equalTo($this->controllerMethod));
			$this->controller->expects($this->once())
				->method($this->controllerMethod)
				->willReturn($this->response);
		}

		$this->response->expects($this->once())
			->method('render')
			->willReturn($out);
		$this->response->expects($this->once())
			->method('getStatus')
			->willReturn(Http::STATUS_OK);
		$this->response->expects($this->once())
			->method('getHeaders')
			->willReturn($responseHeaders);
		$this->http->expects($this->once())
			->method('getStatusHeader')
			->with($this->equalTo(Http::STATUS_OK))
			->willReturn($httpHeaders);

		$this->middlewareDispatcher->expects($this->once())
			->method('afterController')
			->with($this->equalTo($this->controller),
				$this->equalTo($this->controllerMethod),
				$this->equalTo($this->response))
			->willReturn($this->response);

		$this->middlewareDispatcher->expects($this->once())
			->method('afterController')
			->with($this->equalTo($this->controller),
				$this->equalTo($this->controllerMethod),
				$this->equalTo($this->response))
			->willReturn($this->response);

		$this->middlewareDispatcher->expects($this->once())
			->method('beforeOutput')
			->with($this->equalTo($this->controller),
				$this->equalTo($this->controllerMethod),
				$this->equalTo($out))
			->willReturn($out);
	}


	public function testDispatcherReturnsArrayWith2Entries(): void {
		$this->setMiddlewareExpectations('');

		$response = $this->dispatcher->dispatch($this->controller, $this->controllerMethod);
		$this->assertNull($response[0]);
		$this->assertEquals([], $response[1]);
		$this->assertNull($response[2]);
	}


	public function testHeadersAndOutputAreReturned(): void {
		$out = 'yo';
		$httpHeaders = 'Http';
		$responseHeaders = ['hell' => 'yeah'];
		$this->setMiddlewareExpectations($out, $httpHeaders, $responseHeaders);

		$response = $this->dispatcher->dispatch($this->controller,
			$this->controllerMethod);

		$this->assertEquals($httpHeaders, $response[0]);
		$this->assertEquals($responseHeaders, $response[1]);
		$this->assertEquals($out, $response[3]);
	}


	public function testExceptionCallsAfterException(): void {
		$out = 'yo';
		$httpHeaders = 'Http';
		$responseHeaders = ['hell' => 'yeah'];
		$this->setMiddlewareExpectations($out, $httpHeaders, $responseHeaders, true);

		$response = $this->dispatcher->dispatch($this->controller,
			$this->controllerMethod);

		$this->assertEquals($httpHeaders, $response[0]);
		$this->assertEquals($responseHeaders, $response[1]);
		$this->assertEquals($out, $response[3]);
	}


	public function testExceptionThrowsIfCanNotBeHandledByAfterException(): void {
		$out = 'yo';
		$httpHeaders = 'Http';
		$responseHeaders = ['hell' => 'yeah'];
		$this->setMiddlewareExpectations($out, $httpHeaders, $responseHeaders, true, false);

		$this->expectException(\Exception::class);
		$this->dispatcher->dispatch(
			$this->controller,
			$this->controllerMethod
		);
	}


	private function dispatcherPassthrough() {
		$this->middlewareDispatcher->expects($this->once())
			->method('beforeController');
		$this->middlewareDispatcher->expects($this->once())
			->method('afterController')
			->willReturnCallback(function ($a, $b, $in) {
				return $in;
			});
		$this->middlewareDispatcher->expects($this->once())
			->method('beforeOutput')
			->willReturnCallback(function ($a, $b, $in) {
				return $in;
			});
	}


	public function testControllerParametersInjected(): void {
		$this->request = new Request(
			[
				'post' => [
					'int' => '3',
					'bool' => 'false',
					'double' => 1.2,
				],
				'method' => 'POST'
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->dispatcher = new Dispatcher(
			$this->http, $this->middlewareDispatcher, $this->reflector,
			$this->request,
			$this->config,
			Server::get(IDBConnection::class),
			$this->logger,
			$this->eventLogger,
			$this->container
		);
		$controller = new TestController('app', $this->request);

		// reflector is supposed to be called once
		$this->dispatcherPassthrough();
		$response = $this->dispatcher->dispatch($controller, 'exec');

		$this->assertEquals('[3,false,4,1]', $response[3]);
	}


	public function testControllerParametersInjectedDefaultOverwritten(): void {
		$this->request = new Request(
			[
				'post' => [
					'int' => '3',
					'bool' => 'false',
					'double' => 1.2,
					'test2' => 7
				],
				'method' => 'POST',
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->dispatcher = new Dispatcher(
			$this->http, $this->middlewareDispatcher, $this->reflector,
			$this->request,
			$this->config,
			Server::get(IDBConnection::class),
			$this->logger,
			$this->eventLogger,
			$this->container
		);
		$controller = new TestController('app', $this->request);

		// reflector is supposed to be called once
		$this->dispatcherPassthrough();
		$response = $this->dispatcher->dispatch($controller, 'exec');

		$this->assertEquals('[3,false,4,7]', $response[3]);
	}



	public function testResponseTransformedByUrlFormat(): void {
		$this->request = new Request(
			[
				'post' => [
					'int' => '3',
					'bool' => 'false',
					'double' => 1.2,
				],
				'urlParams' => [
					'format' => 'text'
				],
				'method' => 'GET'
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->dispatcher = new Dispatcher(
			$this->http, $this->middlewareDispatcher, $this->reflector,
			$this->request,
			$this->config,
			Server::get(IDBConnection::class),
			$this->logger,
			$this->eventLogger,
			$this->container
		);
		$controller = new TestController('app', $this->request);

		// reflector is supposed to be called once
		$this->dispatcherPassthrough();
		$response = $this->dispatcher->dispatch($controller, 'exec');

		$this->assertEquals('{"text":[3,false,4,1]}', $response[3]);
	}


	public function testResponseTransformsDataResponse(): void {
		$this->request = new Request(
			[
				'post' => [
					'int' => '3',
					'bool' => 'false',
					'double' => 1.2,
				],
				'urlParams' => [
					'format' => 'json'
				],
				'method' => 'GET'
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->dispatcher = new Dispatcher(
			$this->http, $this->middlewareDispatcher, $this->reflector,
			$this->request,
			$this->config,
			Server::get(IDBConnection::class),
			$this->logger,
			$this->eventLogger,
			$this->container
		);
		$controller = new TestController('app', $this->request);

		// reflector is supposed to be called once
		$this->dispatcherPassthrough();
		$response = $this->dispatcher->dispatch($controller, 'execDataResponse');

		$this->assertEquals('{"text":[3,false,4,1]}', $response[3]);
	}


	public function testResponseTransformedByAcceptHeader(): void {
		$this->request = new Request(
			[
				'post' => [
					'int' => '3',
					'bool' => 'false',
					'double' => 1.2,
				],
				'server' => [
					'HTTP_ACCEPT' => 'application/text, test',
					'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded'
				],
				'method' => 'PUT'
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->dispatcher = new Dispatcher(
			$this->http, $this->middlewareDispatcher, $this->reflector,
			$this->request,
			$this->config,
			Server::get(IDBConnection::class),
			$this->logger,
			$this->eventLogger,
			$this->container
		);
		$controller = new TestController('app', $this->request);

		// reflector is supposed to be called once
		$this->dispatcherPassthrough();
		$response = $this->dispatcher->dispatch($controller, 'exec');

		$this->assertEquals('{"text":[3,false,4,1]}', $response[3]);
	}

	public function testResponseTransformedBySendingMultipartFormData(): void {
		$this->request = new Request(
			[
				'post' => [
					'int' => '3',
					'bool' => 'false',
					'double' => 1.2,
				],
				'server' => [
					'HTTP_ACCEPT' => 'application/text, test',
					'HTTP_CONTENT_TYPE' => 'multipart/form-data'
				],
				'method' => 'POST'
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->dispatcher = new Dispatcher(
			$this->http, $this->middlewareDispatcher, $this->reflector,
			$this->request,
			$this->config,
			Server::get(IDBConnection::class),
			$this->logger,
			$this->eventLogger,
			$this->container
		);
		$controller = new TestController('app', $this->request);

		// reflector is supposed to be called once
		$this->dispatcherPassthrough();
		$response = $this->dispatcher->dispatch($controller, 'exec');

		$this->assertEquals('{"text":[3,false,4,1]}', $response[3]);
	}


	public function testResponsePrimarilyTransformedByParameterFormat(): void {
		$this->request = new Request(
			[
				'post' => [
					'int' => '3',
					'bool' => 'false',
					'double' => 1.2,
				],
				'get' => [
					'format' => 'text'
				],
				'server' => [
					'HTTP_ACCEPT' => 'application/json, test'
				],
				'method' => 'POST'
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$this->dispatcher = new Dispatcher(
			$this->http, $this->middlewareDispatcher, $this->reflector,
			$this->request,
			$this->config,
			Server::get(IDBConnection::class),
			$this->logger,
			$this->eventLogger,
			$this->container
		);
		$controller = new TestController('app', $this->request);

		// reflector is supposed to be called once
		$this->dispatcherPassthrough();
		$response = $this->dispatcher->dispatch($controller, 'exec');

		$this->assertEquals('{"text":[3,false,4,1]}', $response[3]);
	}


	public static function rangeDataProvider(): array {
		return [
			[PHP_INT_MIN, PHP_INT_MAX, 42, false],
			[0, 12, -5, true],
			[-12, 0, 5, true],
			[7, 14, 5, true],
			[7, 14, 10, false],
			[-14, -7, -10, false],
		];
	}

	/**
	 * @dataProvider rangeDataProvider
	 */
	public function testEnsureParameterValueSatisfiesRange(int $min, int $max, int $input, bool $throw): void {
		$this->reflector = $this->createMock(ControllerMethodReflector::class);
		$this->reflector->expects($this->any())
			->method('getRange')
			->willReturn([
				'min' => $min,
				'max' => $max,
			]);

		$this->dispatcher = new Dispatcher(
			$this->http,
			$this->middlewareDispatcher,
			$this->reflector,
			$this->request,
			$this->config,
			Server::get(IDBConnection::class),
			$this->logger,
			$this->eventLogger,
			$this->container,
		);

		if ($throw) {
			$this->expectException(ParameterOutOfRangeException::class);
		}

		$this->invokePrivate($this->dispatcher, 'ensureParameterValueSatisfiesRange', ['myArgument', $input]);
		if (!$throw) {
			// do not mark this test risky
			$this->assertTrue(true);
		}
	}
}
