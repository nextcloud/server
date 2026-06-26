<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Paginate;

use OCA\DAV\Paginate\PaginateCache;
use OCA\DAV\Paginate\PaginatePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Xml\Service;
use Test\TestCase;

class PaginatePluginTest extends TestCase {

	private PaginateCache&MockObject $cache;
	private PaginatePlugin $plugin;
	private Server&MockObject $server;
	private RequestInterface&MockObject $request;
	private ResponseInterface&MockObject $response;

	public function testOnMultiStatusCachesAndUpdatesResponse(): void {
		$this->initializePlugin();

		$fileProperties = [
			[
				'href' => '/file1',
				200 => [
					'{DAV:}displayname' => 'File 1',
					'{DAV:}resourcetype' => null
				],
			],
			[
				'href' => '/file2',
				200 => [
					'{DAV:}displayname' => 'File 2',
					'{DAV:}resourcetype' => null
				],
			],
			[
				'href' => '/file3',
				200 => [
					'{DAV:}displayname' => 'File 3',
					'{DAV:}resourcetype' => null
				],
			],
		];

		$this->request->expects(self::exactly(2))
			->method('hasHeader')
			->willReturnMap([
				[PaginatePlugin::PAGINATE_HEADER, true],
				[PaginatePlugin::PAGINATE_TOKEN_HEADER, false],
			]);

		$this->request->expects(self::once())
			->method('getUrl')
			->willReturn('url');

		$this->request->expects(self::exactly(2))
			->method('getHeader')
			->willReturnMap([
				[PaginatePlugin::PAGINATE_COUNT_HEADER, 2],
				[PaginatePlugin::PAGINATE_OFFSET_HEADER, 0],
			]);

		$this->request->expects(self::once())
			->method('setHeader')
			->with(PaginatePlugin::PAGINATE_TOKEN_HEADER, 'token');

		$this->cache->expects(self::once())
			->method('store')
			->with(
				'url',
				$this->callback(function ($generator) {
					self::assertInstanceOf(\Generator::class, $generator);
					$items = iterator_to_array($generator);
					self::assertCount(3, $items);
					self::assertStringContainsString($this->getResponseXmlForFile('/dav/file1', 'File 1'), $items[0]);
					self::assertStringContainsString($this->getResponseXmlForFile('/dav/file2', 'File 2'), $items[1]);
					self::assertStringContainsString($this->getResponseXmlForFile('/dav/file3', 'File 3'), $items[2]);
					return true;
				}),
			)
			->willReturn([
				'token' => 'token',
				'count' => 3,
			]);

		$this->expectSequentialCalls(
			$this->response,
			'addHeader',
			[
				[PaginatePlugin::PAGINATE_HEADER, 'true'],
				[PaginatePlugin::PAGINATE_TOKEN_HEADER, 'token'],
				[PaginatePlugin::PAGINATE_TOTAL_HEADER, '3'],
			],
		);

		$this->plugin->onMultiStatus($fileProperties);

		self::assertInstanceOf(\Iterator::class, $fileProperties);
		// the iterator should be replaced with one that has the amount of
		// items for the page
		$items = iterator_to_array($fileProperties, false);
		$this->assertCount(2, $items);
	}

	private function initializePlugin(): void {
		$this->expectSequentialCalls(
			$this->server,
			'on',
			[
				['beforeMultiStatus', [$this->plugin, 'onMultiStatus'], 100],
				['method:SEARCH', [$this->plugin, 'onMethod'], 1],
				['method:PROPFIND', [$this->plugin, 'onMethod'], 1],
				['method:REPORT', [$this->plugin, 'onMethod'], 1],
			],
		);

		$this->plugin->initialize($this->server);
	}

	/**
	 * @param array<int, array<int, mixed>> $expectedCalls
	 */
	private function expectSequentialCalls(MockObject $mock, string $method, array $expectedCalls): void {
		$mock->expects(self::exactly(\count($expectedCalls)))
			->method($method)
			->willReturnCallback(function (...$args) use (&$expectedCalls): void {
				$expected = array_shift($expectedCalls);
				self::assertNotNull($expected);
				self::assertSame($expected, $args);
			});
	}

	private function getResponseXmlForFile(string $fileName, string $displayName): string {
		return preg_replace('/>\s+</', '><', <<<XML
			<d:response>
				<d:href>$fileName</d:href>
				<d:propstat>
					<d:prop>
						<d:displayname>$displayName</d:displayname>
						<d:resourcetype/>
					</d:prop>
					<d:status>HTTP/1.1 200 OK</d:status>
				</d:propstat>
			</d:response>
			XML
		);
	}

	public function testOnMultiStatusSkipsWhenHeadersAndCacheExist(): void {
		$this->initializePlugin();

		$fileProperties = [
			[
				'href' => '/file1',
			],
			[
				'href' => '/file2',
			],
		];

		$this->request->expects(self::exactly(2))
			->method('hasHeader')
			->willReturnMap([
				[PaginatePlugin::PAGINATE_HEADER, true],
				[PaginatePlugin::PAGINATE_TOKEN_HEADER, true],
			]);

		$this->request->expects(self::once())
			->method('getUrl')
			->willReturn('');

		$this->request->expects(self::once())
			->method('getHeader')
			->with(PaginatePlugin::PAGINATE_TOKEN_HEADER)
			->willReturn('token');

		$this->cache->expects(self::once())
			->method('exists')
			->with('', 'token')
			->willReturn(true);

		$this->cache->expects(self::never())
			->method('store');

		$this->plugin->onMultiStatus($fileProperties);

		self::assertInstanceOf(\Iterator::class, $fileProperties);
		self::assertSame(
			[
				['href' => '/file1'],
				['href' => '/file2'],
			],
			iterator_to_array($fileProperties)
		);
	}

	public function testOnMethodReturnsCachedResponse(): void {
		$this->initializePlugin();

		$response = $this->createMock(ResponseInterface::class);

		$this->request->expects(self::exactly(2))
			->method('hasHeader')
			->willReturnMap([
				[PaginatePlugin::PAGINATE_TOKEN_HEADER, true],
				[PaginatePlugin::PAGINATE_OFFSET_HEADER, true],
			]);

		$this->request->expects(self::once())
			->method('getUrl')
			->willReturn('url');

		$this->request->expects(self::exactly(4))
			->method('getHeader')
			->willReturnMap([
				[PaginatePlugin::PAGINATE_TOKEN_HEADER, 'token'],
				[PaginatePlugin::PAGINATE_OFFSET_HEADER, '2'],
				[PaginatePlugin::PAGINATE_COUNT_HEADER, '4'],
			]);

		$this->cache->expects(self::once())
			->method('exists')
			->with('url', 'token')
			->willReturn(true);

		$this->cache->expects(self::once())
			->method('get')
			->with('url', 'token', 2, 4)
			->willReturn((function (): \Generator {
				yield $this->getResponseXmlForFile('/file1', 'File 1');
				yield $this->getResponseXmlForFile('/file2', 'File 2');
			})());

		$response->expects(self::once())
			->method('setStatus')
			->with(207);

		$response->expects(self::once())
			->method('addHeader')
			->with(PaginatePlugin::PAGINATE_HEADER, 'true');

		$this->expectSequentialCalls(
			$response,
			'setHeader',
			[
				['Content-Type', 'application/xml; charset=utf-8'],
				['Vary', 'Brief,Prefer'],
			],
		);

		$response->expects(self::once())
			->method('setBody')
			->with($this->callback(function (string $body) {
				// header of the XML
				self::assertStringContainsString(<<<XML
					<?xml version="1.0"?>
					<d:multistatus xmlns:d="DAV:">
					XML,
					$body);
				self::assertStringContainsString($this->getResponseXmlForFile('/file1', 'File 1'), $body);
				self::assertStringContainsString($this->getResponseXmlForFile('/file2', 'File 2'), $body);
				// footer of the XML
				self::assertStringContainsString('</d:multistatus>', $body);

				return true;
			}));

		self::assertFalse($this->plugin->onMethod($this->request, $response));
	}

	public function testOnMultiStatusNoPaginateHeaderShouldSucceed(): void {
		$this->initializePlugin();

		$this->request->expects(self::once())
			->method('getUrl')
			->willReturn('');

		$this->cache->expects(self::never())
			->method('exists');
		$this->cache->expects(self::never())
			->method('store');

		$this->plugin->onMultiStatus($this->request);
	}

	public function testOnMethodNoTokenHeaderShouldSucceed(): void {
		$this->initializePlugin();
		$this->request->expects(self::once())
			->method('hasHeader')
			->with(PaginatePlugin::PAGINATE_TOKEN_HEADER)
			->willReturn(false);

		$this->cache->expects(self::never())
			->method('exists');
		$this->cache->expects(self::never())
			->method('get');

		$this->plugin->onMethod($this->request, $this->response);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->cache = $this->createMock(PaginateCache::class);

		$this->server = $this->getMockBuilder(Server::class)
			->disableOriginalConstructor()
			->onlyMethods(['on', 'getHTTPPrefer', 'getBaseUri'])
			->getMock();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);

		$this->server->httpRequest = $this->request;
		$this->server->httpResponse = $this->response;
		$this->server->xml = new Service();
		$this->server->xml->namespaceMap = [ 'DAV:' => 'd' ];

		$this->server->method('getHTTPPrefer')
			->willReturn(['return' => null]);
		$this->server->method('getBaseUri')
			->willReturn('/dav/');

		$this->plugin = new PaginatePlugin($this->cache, 2);
	}
}
