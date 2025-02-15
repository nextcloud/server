<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\Files\BrowserErrorPagePlugin;
use Sabre\DAV\Exception\NotFound;
use Sabre\HTTP\Response;

class BrowserErrorPagePluginTest extends \Test\TestCase {

	/**
	 * @dataProvider providesExceptions
	 * @param $expectedCode
	 * @param $exception
	 */
	public function test($expectedCode, $exception): void {
		/** @var BrowserErrorPagePlugin | \PHPUnit\Framework\MockObject\MockObject $plugin */
		$plugin = $this->getMockBuilder(BrowserErrorPagePlugin::class)->setMethods(['sendResponse', 'generateBody'])->getMock();
		$plugin->expects($this->once())->method('generateBody')->willReturn(':boom:');
		$plugin->expects($this->once())->method('sendResponse');
		/** @var \Sabre\DAV\Server | \PHPUnit\Framework\MockObject\MockObject $server */
		$server = $this->getMockBuilder('Sabre\DAV\Server')->disableOriginalConstructor()->getMock();
		$server->expects($this->once())->method('on');
		$httpResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
		$httpResponse->expects($this->once())->method('addHeaders');
		$httpResponse->expects($this->once())->method('setStatus')->with($expectedCode);
		$httpResponse->expects($this->once())->method('setBody')->with(':boom:');
		$server->httpResponse = $httpResponse;
		$plugin->initialize($server);
		$plugin->logException($exception);
	}

	public function providesExceptions() {
		return [
			[ 404, new NotFound()],
			[ 500, new \RuntimeException()],
		];
	}
}
