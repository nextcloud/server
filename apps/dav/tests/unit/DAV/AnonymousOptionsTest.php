<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @author Bastien Durel <bastien@durel.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\tests\unit\DAV;

use OCA\DAV\Connector\Sabre\AnonymousOptionsPlugin;
use Sabre\DAV\Auth\Backend\BasicCallBack;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\Server;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;
use Test\TestCase;

class AnonymousOptionsTest extends TestCase {
	private function sendRequest($method, $path, $userAgent = '') {
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
