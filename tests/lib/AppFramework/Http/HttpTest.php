<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\Http;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for OC\AppFramework\Http.
 */
class HttpTest extends \Test\TestCase {

	#[Test]
	#[DataProvider('statusHeaderProvider')]
	public function testGetStatusHeader(string $protocol, int $statusCode, string $expectedHeader): void {
		$http = new Http($protocol);
		$header = $http->getStatusHeader($statusCode);
		$this->assertEquals($expectedHeader, $header);
	}

	public static function statusHeaderProvider(): array {
		return [
			// Standard OK
			['HTTP/1.1', Http::STATUS_OK, 'HTTP/1.1 200 OK'],
			// 307 is unchanged for HTTP/1.1
			['HTTP/1.1', Http::STATUS_TEMPORARY_REDIRECT, 'HTTP/1.1 307 Temporary Redirect'],
			// 307 maps to 302 for HTTP/1.0
			['HTTP/1.0', Http::STATUS_TEMPORARY_REDIRECT, 'HTTP/1.0 302 Found'],
			// Not Found
			['HTTP/1.1', Http::STATUS_NOT_FOUND, 'HTTP/1.1 404 Not Found'],
			// Forbidden
			['HTTP/1.1', Http::STATUS_FORBIDDEN, 'HTTP/1.1 403 Forbidden'],
			// Bad Request
			['HTTP/1.1', Http::STATUS_BAD_REQUEST, 'HTTP/1.1 400 Bad request'],
			// Unknown/Fallback
			['HTTP/1.1', 999, 'HTTP/1.1 999 Unknown Status'],
			['HTTP/2.0', 123, 'HTTP/2.0 123 Unknown Status'],
		];
	}
}
