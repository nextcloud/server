<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\Server;

class DataResponseTest extends \Test\TestCase {
	/**
	 * @var DataResponse
	 */
	private $response;

	protected function setUp(): void {
		parent::setUp();
		$this->response = new DataResponse();
	}


	public function testSetData(): void {
		$params = ['hi', 'yo'];
		$this->response->setData($params);

		$this->assertEquals(['hi', 'yo'], $this->response->getData());
	}


	public function testConstructorAllowsToSetData(): void {
		$data = ['hi'];
		$code = 300;
		$response = new DataResponse($data, $code);

		$this->assertEquals($data, $response->getData());
		$this->assertEquals($code, $response->getStatus());
	}


	public function testConstructorAllowsToSetHeaders(): void {
		$data = ['hi'];
		$code = 300;
		$headers = ['test' => 'something'];
		$response = new DataResponse($data, $code, $headers);

		$expectedHeaders = [
			'Cache-Control' => 'no-cache, no-store, must-revalidate',
			'Content-Security-Policy' => "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'",
			'Feature-Policy' => "autoplay 'none';camera 'none';fullscreen 'none';geolocation 'none';microphone 'none';payment 'none'",
			'X-Robots-Tag' => 'noindex, nofollow',
			'X-Request-Id' => Server::get(IRequest::class)->getId(),
		];
		$expectedHeaders = array_merge($expectedHeaders, $headers);

		$this->assertEquals($data, $response->getData());
		$this->assertEquals($code, $response->getStatus());
		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}


	public function testChainability(): void {
		$params = ['hi', 'yo'];
		$this->response->setData($params)
			->setStatus(Http::STATUS_NOT_FOUND);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $this->response->getStatus());
		$this->assertEquals(['hi', 'yo'], $this->response->getData());
	}
}
