<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\Connector\Sabre\AnonymousOptionsPlugin;
use Sabre\DAV\Auth\Backend\BasicCallBack;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\Server;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;
use Test\TestCase;

class AnonymousOptionsTest extends TestCase {
	private function sendRequest(string $method, string $path, string $userAgent = '') {
		$server = new Server();
		$server->addPlugin(new AnonymousOptionsPlugin());
		$server->addPlugin(new Plugin(new BasicCallBack(function () {
			return false;
		})));

		$server->httpRequest->setMethod($method);
		$server->httpRequest->setUrl($path);
		$server->httpRequest->setHeader('User-Agent', $userAgent);

		$server->sapi = new SapiMock();
		$server->exec();
		return $server->httpResponse;
	}

	public function testAnonymousOptionsRoot(): void {
		$response = $this->sendRequest('OPTIONS', '');

		$this->assertEquals(401, $response->getStatus());
	}

	public function testAnonymousOptionsNonRoot(): void {
		$response = $this->sendRequest('OPTIONS', 'foo');

		$this->assertEquals(401, $response->getStatus());
	}

	public function testAnonymousOptionsNonRootSubDir(): void {
		$response = $this->sendRequest('OPTIONS', 'foo/bar');

		$this->assertEquals(401, $response->getStatus());
	}

	public function testAnonymousOptionsRootOffice(): void {
		$response = $this->sendRequest('OPTIONS', '', 'Microsoft Office does strange things');

		$this->assertEquals(200, $response->getStatus());
	}

	public function testAnonymousOptionsNonRootOffice(): void {
		$response = $this->sendRequest('OPTIONS', 'foo', 'Microsoft Office does strange things');

		$this->assertEquals(200, $response->getStatus());
	}

	public function testAnonymousOptionsNonRootSubDirOffice(): void {
		$response = $this->sendRequest('OPTIONS', 'foo/bar', 'Microsoft Office does strange things');

		$this->assertEquals(200, $response->getStatus());
	}

	public function testAnonymousHead(): void {
		$response = $this->sendRequest('HEAD', '', 'Microsoft Office does strange things');

		$this->assertEquals(200, $response->getStatus());
	}

	public function testAnonymousHeadNoOffice(): void {
		$response = $this->sendRequest('HEAD', '');

		$this->assertEquals(401, $response->getStatus(), 'curl');
	}
}

class SapiMock extends Sapi {
	/**
	 * Overriding this so nothing is ever echo'd.
	 *
	 * @return void
	 */
	public static function sendResponse(ResponseInterface $response): void {
	}
}
