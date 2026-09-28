<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\PropfindCompressionPlugin;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class PropfindCompressionPluginTest extends TestCase {
	private PropfindCompressionPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->plugin = new PropfindCompressionPlugin();
	}

	public function testNoHeader(): void {
		$request = $this->createMock(Request::class);
		$response = $this->createMock(Response::class);

		$request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn(null);

		$response->expects($this->never())
			->method($this->anything());

		$result = $this->plugin->compressResponse($request, $response);
		$this->assertSame($response, $result);
	}

	public function testHeaderButNoGzip(): void {
		$request = $this->createMock(Request::class);
		$response = $this->createMock(Response::class);

		$request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('deflate');

		$response->expects($this->never())
			->method($this->anything());

		$result = $this->plugin->compressResponse($request, $response);
		$this->assertSame($response, $result);
	}

	public function testHeaderGzipButNoStringBody(): void {
		$request = $this->createMock(Request::class);
		$response = $this->createMock(Response::class);

		$request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('deflate');

		$response->method('getBody')
			->willReturn(5);

		$result = $this->plugin->compressResponse($request, $response);
		$this->assertSame($response, $result);
	}


	public function testProperGzip(): void {
		$request = $this->createMock(Request::class);
		$response = $this->createMock(Response::class);

		$request->method('getHeader')
			->with('Accept-Encoding')
			->willReturn('gzip, deflate');

		$response->method('getBody')
			->willReturn('my gzip test');

		$response->expects($this->once())
			->method('setHeader')
			->with(
				$this->equalTo('Content-Encoding'),
				$this->equalTo('gzip')
			);
		$response->expects($this->once())
			->method('setBody')
			->with($this->callback(function ($data) {
				$orig = gzdecode($data);
				return $orig === 'my gzip test';
			}));

		$result = $this->plugin->compressResponse($request, $response);
		$this->assertSame($response, $result);
	}
}
