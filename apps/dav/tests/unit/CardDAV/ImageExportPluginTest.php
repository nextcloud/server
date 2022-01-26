<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Jacob Neplokh <me@jacobneplokh.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\ImageExportPlugin;
use OCA\DAV\CardDAV\PhotoCache;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\CardDAV\Card;
use Sabre\DAV\Node;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class ImageExportPluginTest extends TestCase {

	/** @var ResponseInterface|MockObject */
	private $response;
	/** @var RequestInterface|MockObject */
	private $request;
	/** @var ImageExportPlugin|MockObject */
	private $plugin;
	/** @var Tree|MockObject */
	private $tree;
	/** @var PhotoCache|MockObject */
	private $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);
		$server->tree = $this->tree;
		$this->cache = $this->createMock(PhotoCache::class);

		$this->plugin = $this->getMockBuilder(ImageExportPlugin::class)
			->addMethods(['getPhoto'])
			->setConstructorArgs([$this->cache])
			->getMock();
		$this->plugin->initialize($server);
	}

	/**
	 * @dataProvider providesQueryParams
	 */
	public function testQueryParams(array $param) {
		$this->request->expects($this->once())->method('getQueryParameters')->willReturn($param);
		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertTrue($result);
	}

	public function providesQueryParams(): array {
		return [
			[[]],
			[['1']],
			[['foo' => 'bar']],
		];
	}

	public function testNoCard() {
		$this->request->method('getQueryParameters')
			->willReturn([
				'photo'
			]);
		$this->request->method('getPath')
			->willReturn('user/book/card');

		$node = $this->createMock(Node::class);
		$this->tree->method('getNodeForPath')
			->with('user/book/card')
			->willReturn($node);

		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertTrue($result);
	}

	public function dataTestCard(): array {
		return [
			[null, false],
			[null, true],
			[32, false],
			[32, true],
		];
	}

	/**
	 * @dataProvider dataTestCard
	 */
	public function testCard(?int $size, bool $photo) {
		$query = ['photo' => null];
		if ($size !== null) {
			$query['size'] = $size;
		}

		$this->request->method('getQueryParameters')
			->willReturn($query);
		$this->request->method('getPath')
			->willReturn('user/book/card');

		$card = $this->createMock(Card::class);
		$card->method('getETag')
			->willReturn('"myEtag"');
		$card->method('getName')
			->willReturn('card');
		$book = $this->createMock(AddressBook::class);
		$book->method('getResourceId')
			->willReturn(1);

		$this->tree->method('getNodeForPath')
			->willReturnCallback(function ($path) use ($card, $book) {
				if ($path === 'user/book/card') {
					return $card;
				} elseif ($path === 'user/book') {
					return $book;
				}
				$this->fail();
			});

		$size = $size === null ? -1 : $size;

		$setHeadersParams = [
			['Cache-Control', 'private, max-age=3600, must-revalidate'],
			['Etag', '"myEtag"'],
			['Pragma', 'public']
		];

		if ($photo) {
			$file = $this->createMock(ISimpleFile::class);
			$file->method('getMimeType')
				->willReturn('image/jpeg');
			$file->method('getContent')
				->willReturn('imgdata');

			$this->cache->method('get')
				->with(1, 'card', $size, $card)
				->willReturn($file);

			$setHeadersParams = array_merge($setHeadersParams, [
				['Content-Type', 'image/jpeg'],
				['Content-Disposition', 'attachment; filename=card.jpg']
			]);

			$this->response->expects($this->once())
				->method('setStatus')
				->with(200);
			$this->response->expects($this->once())
				->method('setBody')
				->with('imgdata');
		} else {
			$this->cache->method('get')
				->with(1, 'card', $size, $card)
				->willThrowException(new NotFoundException());
			$this->response->expects($this->once())
				->method('setStatus')
				->with(404);
		}

		$this->response->expects($this->exactly(count($setHeadersParams)))
			->method('setHeader')
			->withConsecutive(...$setHeadersParams);

		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertFalse($result);
	}
}
