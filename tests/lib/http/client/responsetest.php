<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Http\Client;

use Guzzle\Stream\Stream;
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

	public function testGetStatusCode() {
		$this->assertEquals(1337, $this->response->getStatusCode());
	}

	public function testGetHeader() {
		$this->guzzleResponse->setHeader('bar', 'foo');
		$this->assertEquals('foo', $this->response->getHeader('bar'));
	}
}
