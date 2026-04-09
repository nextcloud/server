<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware;

use OC\AppFramework\Middleware\CompressionMiddleware;
use OC\AppFramework\OCS\V1Response;
use OC\AppFramework\OCS\V2Response;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class CompressionMiddlewareTest extends \Test\TestCase {
	/** @var IRequest */
	private $request;
	/** @var Controller */
	private $controller;
	/** @var CompressionMiddleware */
	private $middleWare;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->middleWare = new CompressionMiddleware(
			$this->request
		);

		$this->controller = $this->createMock(Controller::class);
	}

	public function testGzipOCSV1(): void {
		$this->request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('gzip');

		$response = $this->createMock(V1Response::class);
		$response->expects($this->once())
			->method('addHeader')
			->with('Content-Encoding', 'gzip');

		$response->method('getStatus')
			->willReturn(Http::STATUS_OK);

		$this->middleWare->beforeController($this->controller, 'myMethod');
		$this->middleWare->afterController($this->controller, 'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, gzdecode($result));
	}

	public function testGzipOCSV2(): void {
		$this->request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('gzip');

		$response = $this->createMock(V2Response::class);
		$response->expects($this->once())
			->method('addHeader')
			->with('Content-Encoding', 'gzip');

		$response->method('getStatus')
			->willReturn(Http::STATUS_OK);

		$this->middleWare->beforeController($this->controller, 'myMethod');
		$this->middleWare->afterController($this->controller, 'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, gzdecode($result));
	}

	public function testGzipJSONResponse(): void {
		$this->request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('gzip');

		$response = $this->createMock(JSONResponse::class);
		$response->expects($this->once())
			->method('addHeader')
			->with('Content-Encoding', 'gzip');

		$response->method('getStatus')
			->willReturn(Http::STATUS_OK);

		$this->middleWare->beforeController($this->controller, 'myMethod');
		$this->middleWare->afterController($this->controller, 'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, gzdecode($result));
	}

	public function testNoGzipDataResponse(): void {
		$this->request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('gzip');

		$response = $this->createMock(DataResponse::class);
		$response->expects($this->never())
			->method('addHeader');

		$response->method('getStatus')
			->willReturn(Http::STATUS_OK);
		$this->middleWare->beforeController($this->controller, 'myMethod');
		$this->middleWare->afterController($this->controller, 'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, $result);
	}

	public function testNoGzipNo200(): void {
		$this->request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('gzip');

		$response = $this->createMock(JSONResponse::class);
		$response->expects($this->never())
			->method('addHeader');

		$response->method('getStatus')
			->willReturn(Http::STATUS_NOT_FOUND);

		$this->middleWare->beforeController($this->controller, 'myMethod');
		$this->middleWare->afterController($this->controller, 'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, $result);
	}
}
