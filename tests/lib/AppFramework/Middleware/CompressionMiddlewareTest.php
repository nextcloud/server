<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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

	public function testGzipOCSV1() {
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
		$this->middleWare->afterController($this->controller,'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, gzdecode($result));
	}

	public function testGzipOCSV2() {
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
		$this->middleWare->afterController($this->controller,'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, gzdecode($result));
	}

	public function testGzipJSONResponse() {
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
		$this->middleWare->afterController($this->controller,'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, gzdecode($result));
	}

	public function testNoGzipDataResponse() {
		$this->request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('gzip');

		$response = $this->createMock(DataResponse::class);
		$response->expects($this->never())
			->method('addHeader');

		$response->method('getStatus')
			->willReturn(Http::STATUS_OK);
		$this->middleWare->beforeController($this->controller, 'myMethod');
		$this->middleWare->afterController($this->controller,'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, $result);
	}

	public function testNoGzipNo200() {
		$this->request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('gzip');

		$response = $this->createMock(JSONResponse::class);
		$response->expects($this->never())
			->method('addHeader');

		$response->method('getStatus')
			->willReturn(Http::STATUS_NOT_FOUND);

		$this->middleWare->beforeController($this->controller, 'myMethod');
		$this->middleWare->afterController($this->controller,'myMethod', $response);

		$output = 'myoutput';
		$result = $this->middleWare->beforeOutput($this->controller, 'myMethod', $output);

		$this->assertSame($output, $result);
	}
}
