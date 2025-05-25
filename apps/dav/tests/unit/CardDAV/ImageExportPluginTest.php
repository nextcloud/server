<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\ImageExportPlugin;
use OCA\DAV\CardDAV\PhotoCache;
use OCP\AppFramework\Http;
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
	private ResponseInterface&MockObject $response;
	private RequestInterface&MockObject $request;
	private Server&MockObject $server;
	private Tree&MockObject $tree;
	private PhotoCache&MockObject $cache;
	private ImageExportPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$this->server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);
		$this->server->tree = $this->tree;
		$this->cache = $this->createMock(PhotoCache::class);

		$this->plugin = new ImageExportPlugin($this->cache);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider providesQueryParams
	 */
	public function testQueryParams(array $param): void {
		$this->request->expects($this->once())->method('getQueryParameters')->willReturn($param);
		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertTrue($result);
	}

	public static function providesQueryParams(): array {
		return [
			[[]],
			[['1']],
			[['foo' => 'bar']],
		];
	}

	public function testNoCard(): void {
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

	public static function dataTestCard(): array {
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
	public function testCard(?int $size, bool $photo): void {
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

		if ($photo) {
			$file = $this->createMock(ISimpleFile::class);
			$file->method('getMimeType')
				->willReturn('image/jpeg');
			$file->method('getContent')
				->willReturn('imgdata');

			$this->cache->method('get')
				->with(1, 'card', $size, $card)
				->willReturn($file);

			$setHeaderCalls = [
				['Cache-Control', 'private, max-age=3600, must-revalidate'],
				['Etag', '"myEtag"'],
				['Content-Type', 'image/jpeg'],
				['Content-Disposition', 'attachment; filename=card.jpg'],
			];
			$this->response->expects($this->exactly(count($setHeaderCalls)))
				->method('setHeader')
				->willReturnCallback(function () use (&$setHeaderCalls) {
					$expected = array_shift($setHeaderCalls);
					$this->assertEquals($expected, func_get_args());
				});

			$this->response->expects($this->once())
				->method('setStatus')
				->with(200);
			$this->response->expects($this->once())
				->method('setBody')
				->with('imgdata');
		} else {
			$setHeaderCalls = [
				['Cache-Control', 'private, max-age=3600, must-revalidate'],
				['Etag', '"myEtag"'],
			];
			$this->response->expects($this->exactly(count($setHeaderCalls)))
				->method('setHeader')
				->willReturnCallback(function () use (&$setHeaderCalls) {
					$expected = array_shift($setHeaderCalls);
					$this->assertEquals($expected, func_get_args());
				});
			$this->cache->method('get')
				->with(1, 'card', $size, $card)
				->willThrowException(new NotFoundException());
			$this->response->expects($this->once())
				->method('setStatus')
				->with(Http::STATUS_NO_CONTENT);
		}

		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertFalse($result);
	}
}
