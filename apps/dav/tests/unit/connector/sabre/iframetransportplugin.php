<?php

namespace OCA\DAV\Tests\Unit\Connector\Sabre;

/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class IframeTransportPlugin extends \Test\TestCase {

	/**
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \OCA\DAV\Connector\Sabre\IframeTransportPlugin
	 */
	private $plugin;

	public function setUp() {
		parent::setUp();
		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();

		$this->plugin = new \OCA\DAV\Connector\Sabre\IframeTransportPlugin();
		$this->plugin->initialize($this->server);
	}

	public function tearDown() {
		$_FILES = null;
		unset($_SERVER['CONTENT_LENGTH']);
	}

	public function testPutConversion() {
		$request = $this->getMock('Sabre\HTTP\RequestInterface');
		$response = $this->getMock('Sabre\HTTP\ResponseInterface');

		$request->expects($this->once())
			->method('getQueryParameters')
			->will($this->returnValue(['_method' => 'PUT']));

		$postData = [
			'headers' => json_encode([
				'If-None-Match' => '*',
				'Disallowed-Header' => 'test',
			]),
		];

		$request->expects($this->once())
			->method('getPostData')
			->will($this->returnValue($postData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('multipart/form-data'));

		$tmpFileName = tempnam(sys_get_temp_dir(), 'tmpfile');
		$fh = fopen($tmpFileName, 'w');
		fwrite($fh, 'hello');
		fclose($fh);

		$_FILES = ['files' => [
			'error' => [0],
			'tmp_name' => [$tmpFileName],
			'size' => [5],
		]];

		$request->expects($this->any())
			->method('setHeader')
			->withConsecutive(
				['If-None-Match', '*'],
				['Content-Length', 5]
			);

		$request->expects($this->once())
			->method('setMethod')
			->with('PUT');

		$this->server->expects($this->once())
			->method('invokeMethod')
			->with($request, $response);

		// response data before conversion
		$response->expects($this->once())
			->method('getHeaders')
			->will($this->returnValue(['Test-Response-Header' => [123]]));

		$response->expects($this->any())
			->method('getBody')
			->will($this->returnValue('test'));

		$response->expects($this->once())
			->method('getStatus')
			->will($this->returnValue(201));

		$responseBody = json_encode([
			'status' => 201,
			'headers' => ['Test-Response-Header' => [123]],
			'data' => 'test',
		]);

		// response data after conversion
		$response->expects($this->once())
			->method('setBody')
			->with($responseBody);

		$response->expects($this->once())
			->method('setStatus')
			->with(200);

		$response->expects($this->any())
			->method('setHeader')
			->withConsecutive(
				['Content-Type', 'text/plain'],
				['Content-Length', strlen($responseBody)]
			);

		$this->assertFalse($this->plugin->handlePost($request, $response));

		$this->assertEquals(5, $_SERVER['CONTENT_LENGTH']);

		$this->assertFalse(file_exists($tmpFileName));
	}

	public function testIgnoreNonPut() {
		$request = $this->getMock('Sabre\HTTP\RequestInterface');
		$response = $this->getMock('Sabre\HTTP\ResponseInterface');

		$request->expects($this->once())
			->method('getQueryParameters')
			->will($this->returnValue(['_method' => 'PROPFIND']));

		$this->server->expects($this->never())
			->method('invokeMethod')
			->with($request, $response);

		$this->assertNull($this->plugin->handlePost($request, $response));
	}

	public function testIgnoreMismatchedContentType() {
		$request = $this->getMock('Sabre\HTTP\RequestInterface');
		$response = $this->getMock('Sabre\HTTP\ResponseInterface');

		$request->expects($this->once())
			->method('getQueryParameters')
			->will($this->returnValue(['_method' => 'PUT']));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('text/plain'));

		$this->server->expects($this->never())
			->method('invokeMethod')
			->with($request, $response);

		$this->assertNull($this->plugin->handlePost($request, $response));
	}
}
