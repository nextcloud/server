<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\Http;

class HttpTest extends \Test\TestCase {
	private $server;

	/**
	 * @var Http
	 */
	private $http;

	protected function setUp(): void {
		parent::setUp();

		$this->server = [];
		$this->http = new Http($this->server);
	}


	public function testProtocol(): void {
		$header = $this->http->getStatusHeader(Http::STATUS_TEMPORARY_REDIRECT);
		$this->assertEquals('HTTP/1.1 307 Temporary Redirect', $header);
	}


	public function testProtocol10(): void {
		$this->http = new Http($this->server, 'HTTP/1.0');
		$header = $this->http->getStatusHeader(Http::STATUS_OK);
		$this->assertEquals('HTTP/1.0 200 OK', $header);
	}

	public function testTempRedirectBecomesFoundInHttp10(): void {
		$http = new Http([], 'HTTP/1.0');

		$header = $http->getStatusHeader(Http::STATUS_TEMPORARY_REDIRECT);
		$this->assertEquals('HTTP/1.0 302 Found', $header);
	}
	// TODO: write unittests for http codes
}
