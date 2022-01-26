<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\DAV;

use Exception;
use OCA\DAV\Files\BrowserErrorPagePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\HTTP\Response;
use Test\TestCase;

class BrowserErrorPagePluginTest extends TestCase {

	/**
	 * @dataProvider providesExceptions
	 */
	public function test(int $expectedCode, Exception $exception) {
		/** @var BrowserErrorPagePlugin | MockObject $plugin */
		$plugin = $this->getMockBuilder(BrowserErrorPagePlugin::class)->onlyMethods(['sendResponse', 'generateBody'])->getMock();
		$plugin->expects($this->once())->method('generateBody')->willReturn(':boom:');
		$plugin->expects($this->once())->method('sendResponse');
		/** @var Server | MockObject $server */
		$server = $this->createMock(Server::class);
		$server->expects($this->once())->method('on');
		$httpResponse = $this->createMock(Response::class);
		$httpResponse->expects($this->once())->method('addHeaders');
		$httpResponse->expects($this->once())->method('setStatus')->with($expectedCode);
		$httpResponse->expects($this->once())->method('setBody')->with(':boom:');
		$server->httpResponse = $httpResponse;
		$plugin->initialize($server);
		$plugin->logException($exception);
	}

	public function providesExceptions(): array {
		return [
			[ 404, new NotFound()],
			[ 500, new RuntimeException()],
		];
	}
}
