<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Http\Client;

use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Message\Response as GuzzleResponse;
use OC\Http\Client\Response;

/**
 * Class ResponseTest
 */
class ResponseTest extends \Test\TestCase {
	/** @var Response */
	private $response;
	/** @var GuzzleResponse */
	private $guzzleResponse;

	public function setUp() {
		parent::setUp();
		$this->guzzleResponse = new GuzzleResponse(1337);
		$this->response = new Response($this->guzzleResponse);
	}

	public function testGetBody() {
		$this->guzzleResponse->setBody(Stream::factory('MyResponse'));
		$this->assertSame('MyResponse', $this->response->getBody());
	}

	public function testGetStatusCode() {
		$this->assertSame(1337, $this->response->getStatusCode());
	}

	public function testGetHeader() {
		$this->guzzleResponse->setHeader('bar', 'foo');
		$this->assertSame('foo', $this->response->getHeader('bar'));
	}

	public function testGetHeaders() {
		$this->guzzleResponse->setHeader('bar', 'foo');
		$this->guzzleResponse->setHeader('x-awesome', 'yes');

		$expected = [
			'bar' => [
				0 => 'foo',
			],
			'x-awesome' => [
				0 => 'yes',
			],
		];
		$this->assertSame($expected, $this->response->getHeaders());
		$this->assertSame('yes', $this->response->getHeader('x-awesome'));
	}
}
