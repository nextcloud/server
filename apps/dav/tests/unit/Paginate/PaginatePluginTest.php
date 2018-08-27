<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Tests\Paginate;

use OCA\DAV\Paginate\PaginateCache;
use OCA\DAV\Paginate\PaginatePlugin;
use Sabre\DAV\Server;
use Sabre\HTTP\Request;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\Response;
use Test\TestCase;

class PaginatePluginTest extends TestCase {
	/** @var PaginateCache|\PHPUnit_Framework_MockObject_MockObject */
	private $cache;
	/** @var Server|\PHPUnit_Framework_MockObject_MockObject */
	private $server;

	/** @var PaginatePlugin */
	private $plugin;

	const DATA = [
		[
			'href' => 'foo',
			'200' => [
				'd:getcontentlength' => 10
			],
		],
		[
			'href' => 'bar',
			'200' => [
				'd:getcontentlength' => 11
			],
		],
		[
			'href' => 'asd',
			'200' => [
				'd:getcontentlength' => 11
			],
		]
	];

	protected function setUp() {
		parent::setUp();

		$this->cache = $this->createMock(PaginateCache::class);
		$this->server = $this->createMock(Server::class);
		$this->server->httpRequest = new Request('GET', 'http://example.com');
		$this->server->httpResponse = new Response();
		$this->plugin = new PaginatePlugin($this->cache, 2);
		$this->plugin->initialize($this->server);
	}

	public function testPaginateRequest() {
		$fileProperties = new \ArrayIterator(self::DATA);
		$storedProps = [];
		$this->cache->expects($this->any())
			->method('store')
			->willReturnCallback(function ($url, $props) use (&$storedProps) {
				$storedProps = $props;
				return ['token', iterator_count($storedProps)];
			});

		$this->server->httpRequest->setHeader(PaginatePlugin::PAGINATE_HEADER, 'true');

		$this->plugin->onMultiStatus($fileProperties);
		$this->assertTrue($this->server->httpResponse->hasHeader(PaginatePlugin::PAGINATE_HEADER));
		$this->assertTrue($this->server->httpResponse->hasHeader(PaginatePlugin::PAGINATE_TOKEN_HEADER));
		$this->assertEquals(3, $this->server->httpResponse->getHeader(PaginatePlugin::PAGINATE_TOTAL_HEADER));

		$data = self::DATA;
		$this->assertEquals(array_splice($data, 0, 2), array_values(iterator_to_array($fileProperties)));
		$this->assertEquals(self::DATA, iterator_to_array($storedProps));
	}

	public function testGetCached() {
		$this->cache->expects($this->any())
			->method('get')
			->willReturnCallback(function ($url, $token, $offset, $count) {
				return new \LimitIterator(new \ArrayIterator(self::DATA), $offset, $count);
			});
		$responseItems = null;
		$this->server->expects($this->any())
			->method('generateMultiStatus')
			->willReturnCallback(function ($items) use (&$responseItems) {
				$responseItems = $items;
			});

		$this->server->httpRequest->setHeader(PaginatePlugin::PAGINATE_TOKEN_HEADER, 'foo');
		$this->server->httpRequest->setHeader(PaginatePlugin::PAGINATE_OFFSET_HEADER, '1');
		$this->server->httpRequest->setHeader(PaginatePlugin::PAGINATE_COUNT_HEADER, '1');

		$this->plugin->onMethod($this->server->httpRequest, $this->server->httpResponse);

		$this->assertTrue($this->server->httpResponse->hasHeader(PaginatePlugin::PAGINATE_HEADER));
		$this->assertEquals(array_slice(self::DATA, 1, 1), array_values(iterator_to_array($responseItems)));
	}
}