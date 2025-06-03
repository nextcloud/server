<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\Files\BrowserErrorPagePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\NotFound;
use Sabre\HTTP\Response;

class BrowserErrorPagePluginTest extends \Test\TestCase {

	/**
	 * @dataProvider providesExceptions
	 */
	public function test(int $expectedCode, \Throwable $exception): void {
		/** @var BrowserErrorPagePlugin&MockObject $plugin */
		$plugin = $this->getMockBuilder(BrowserErrorPagePlugin::class)->onlyMethods(['sendResponse', 'generateBody'])->getMock();
		$plugin->expects($this->once())->method('generateBody')->willReturn(':boom:');
		$plugin->expects($this->once())->method('sendResponse');
		/** @var \Sabre\DAV\Server&MockObject $server */
		$server = $this->createMock('Sabre\DAV\Server');
		$server->expects($this->once())->method('on');
		$httpResponse = $this->createMock(Response::class);
		$httpResponse->expects($this->once())->method('addHeaders');
		$httpResponse->expects($this->once())->method('setStatus')->with($expectedCode);
		$httpResponse->expects($this->once())->method('setBody')->with(':boom:');
		$server->httpResponse = $httpResponse;
		$plugin->initialize($server);
		$plugin->logException($exception);
	}

	public static function providesExceptions(): array {
		return [
			[ 404, new NotFound()],
			[ 500, new \RuntimeException()],
		];
	}
}
