<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Controller;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Http\Request;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IRequestId;

class ChildController extends Controller {
	public function __construct($appName, $request) {
		parent::__construct($appName, $request);
		$this->registerResponder('tom', function ($respone) {
			return 'hi';
		});
	}

	public function custom($in) {
		$this->registerResponder('json', function ($response) {
			return new JSONResponse([strlen($response)]);
		});

		return $in;
	}

	public function customDataResponse($in) {
		$response = new DataResponse($in, 300);
		$response->addHeader('test', 'something');
		return $response;
	}
};

class ControllerTest extends \Test\TestCase {
	/**
	 * @var Controller
	 */
	private $controller;
	private $app;
	private $request;

	protected function setUp(): void {
		parent::setUp();

		$request = new Request(
			[
				'get' => ['name' => 'John Q. Public', 'nickname' => 'Joey'],
				'post' => ['name' => 'Jane Doe', 'nickname' => 'Janey'],
				'urlParams' => ['name' => 'Johnny Weissmüller'],
				'files' => ['file' => 'filevalue'],
				'env' => ['PATH' => 'daheim'],
				'session' => ['sezession' => 'kein'],
				'method' => 'hi',
			],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);

		$this->app = $this->getMockBuilder(DIContainer::class)
			->setMethods(['getAppName'])
			->setConstructorArgs(['test'])
			->getMock();
		$this->app->expects($this->any())
			->method('getAppName')
			->willReturn('apptemplate_advanced');

		$this->controller = new ChildController($this->app, $request);
		$this->overwriteService(IRequest::class, $request);
		$this->request = $request;
	}


	public function testFormatResonseInvalidFormat(): void {
		$this->expectException(\DomainException::class);

		$this->controller->buildResponse(null, 'test');
	}


	public function testFormat(): void {
		$response = $this->controller->buildResponse(['hi'], 'json');

		$this->assertEquals(['hi'], $response->getData());
	}


	public function testFormatDataResponseJSON(): void {
		$expectedHeaders = [
			'test' => 'something',
			'Cache-Control' => 'no-cache, no-store, must-revalidate',
			'Content-Type' => 'application/json; charset=utf-8',
			'Content-Security-Policy' => "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'",
			'Feature-Policy' => "autoplay 'none';camera 'none';fullscreen 'none';geolocation 'none';microphone 'none';payment 'none'",
			'X-Request-Id' => $this->request->getId(),
			'X-Robots-Tag' => 'noindex, nofollow',
		];

		$response = $this->controller->customDataResponse(['hi']);
		$response = $this->controller->buildResponse($response, 'json');

		$this->assertEquals(['hi'], $response->getData());
		$this->assertEquals(300, $response->getStatus());
		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}


	public function testCustomFormatter(): void {
		$response = $this->controller->custom('hi');
		$response = $this->controller->buildResponse($response, 'json');

		$this->assertEquals([2], $response->getData());
	}


	public function testDefaultResponderToJSON(): void {
		$responder = $this->controller->getResponderByHTTPHeader('*/*');

		$this->assertEquals('json', $responder);
	}


	public function testResponderAcceptHeaderParsed(): void {
		$responder = $this->controller->getResponderByHTTPHeader(
			'*/*, application/tom, application/json'
		);

		$this->assertEquals('tom', $responder);
	}


	public function testResponderAcceptHeaderParsedUpperCase(): void {
		$responder = $this->controller->getResponderByHTTPHeader(
			'*/*, apPlication/ToM, application/json'
		);

		$this->assertEquals('tom', $responder);
	}
}
