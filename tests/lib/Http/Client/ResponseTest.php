<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\Client;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Utils;
use OC\Http\Client\Response;

/**
 * Class ResponseTest
 */
class ResponseTest extends \Test\TestCase {
	/** @var GuzzleResponse */
	private $guzzleResponse;

	protected function setUp(): void {
		parent::setUp();
		$this->guzzleResponse = new GuzzleResponse(418);
	}

	public function testGetBody(): void {
		$response = new Response($this->guzzleResponse->withBody(Utils::streamFor('MyResponse')));
		$this->assertSame('MyResponse', $response->getBody());
	}

	public function testGetStatusCode(): void {
		$response = new Response($this->guzzleResponse);
		$this->assertSame(418, $response->getStatusCode());
	}

	public function testGetHeader(): void {
		$response = new Response($this->guzzleResponse->withHeader('bar', 'foo'));
		$this->assertSame('foo', $response->getHeader('bar'));
	}

	public function testGetHeaders(): void {
		$response = new Response($this->guzzleResponse
			->withHeader('bar', 'foo')
			->withHeader('x-awesome', 'yes')
		);

		$expected = [
			'bar' => [
				0 => 'foo',
			],
			'x-awesome' => [
				0 => 'yes',
			],
		];
		$this->assertSame($expected, $response->getHeaders());
		$this->assertSame('yes', $response->getHeader('x-awesome'));
	}
}
