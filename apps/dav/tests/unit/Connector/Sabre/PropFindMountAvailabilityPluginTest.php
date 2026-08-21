<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\PropFindMountAvailabilityPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\ICollection;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\Request;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;
use Test\TestCase;

class PropFindMountAvailabilityTestSapi extends Sapi {
	public static ?Request $request = null;
	public static ?ResponseInterface $response = null;

	public static function getRequest(): Request {
		return self::$request ?? new Request('GET', '/');
	}

	public static function sendResponse(ResponseInterface $response): void {
		self::$response = $response;
	}
}

class PropFindMountAvailabilityPluginTest extends TestCase {
	private Server&MockObject $server;
	private Tree&MockObject $tree;
	private PropFindMountAvailabilityPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);
		$this->server->tree = $this->tree;
		$this->plugin = new PropFindMountAvailabilityPlugin();
		PropFindMountAvailabilityTestSapi::$request = null;
		PropFindMountAvailabilityTestSapi::$response = null;
	}

	public function testInitialize(): void {
		$this->server->expects(self::once())
			->method('on')
			->with('method:PROPFIND', [$this->plugin, 'preflight'], 10);

		$this->plugin->initialize($this->server);
	}

	public function testDepthZeroDoesNotEnumerateChildren(): void {
		$request = $this->createMock(RequestInterface::class);
		$this->server->expects(self::once())
			->method('getHTTPDepth')
			->with(1)
			->willReturn(0);
		$this->tree->expects(self::never())
			->method('getNodeForPath');

		$this->plugin->initialize($this->server);
		$this->plugin->preflight($request);
	}

	public function testNonFilesCollectionIsNotEnumerated(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->expects(self::once())
			->method('getPath')
			->willReturn('calendars/user');
		$this->server->expects(self::once())
			->method('getHTTPDepth')
			->with(1)
			->willReturn(1);
		$this->tree->expects(self::once())
			->method('getNodeForPath')
			->with('calendars/user')
			->willReturn($this->createMock(ICollection::class));

		$this->plugin->initialize($this->server);
		$this->plugin->preflight($request);
	}

	public function testFilesCollectionIsStrictlyEnumerated(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->expects(self::once())
			->method('getPath')
			->willReturn('files/user');
		$this->server->expects(self::once())
			->method('getHTTPDepth')
			->with(1)
			->willReturn(1);
		$directory = $this->createMock(Directory::class);
		$directory->expects(self::once())
			->method('getChildrenStrict')
			->willReturn([]);
		$this->tree->expects(self::once())
			->method('getNodeForPath')
			->with('files/user')
			->willReturn($directory);

		$this->plugin->initialize($this->server);
		$this->plugin->preflight($request);
	}

	public function testDepthInfinityStrictlyEnumeratesNestedDirectories(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->method('getPath')
			->willReturn('files/user');
		$this->server->method('getHTTPDepth')
			->with(1)
			->willReturn(Server::DEPTH_INFINITY);
		$nestedDirectory = $this->createMock(Directory::class);
		$nestedDirectory->expects(self::once())
			->method('getChildrenStrict')
			->willReturn([]);
		$directory = $this->createMock(Directory::class);
		$directory->expects(self::once())
			->method('getChildrenStrict')
			->willReturn([$nestedDirectory]);
		$this->tree->method('getNodeForPath')
			->with('files/user')
			->willReturn($directory);

		$this->plugin->initialize($this->server);
		$this->plugin->preflight($request);
	}

	public function testUnavailableMountReturnsServiceUnavailableBeforeStreamingMultiStatus(): void {
		$directory = $this->createMock(Directory::class);
		$directory->expects(self::once())
			->method('getChildrenStrict')
			->willThrowException(new ServiceUnavailable('Storage is temporarily not available'));
		PropFindMountAvailabilityTestSapi::$request = new Request('PROPFIND', '/', ['Depth' => '1']);
		$sapi = new PropFindMountAvailabilityTestSapi();
		$server = new Server($directory, $sapi);
		$server->addPlugin(new PropFindMountAvailabilityPlugin());
		$previousStreamMultiStatus = Server::$streamMultiStatus;
		try {
			Server::$streamMultiStatus = true;
			$server->start();
		} finally {
			Server::$streamMultiStatus = $previousStreamMultiStatus;
		}

		$response = PropFindMountAvailabilityTestSapi::$response;
		$this->assertNotNull($response);
		$this->assertSame(503, $response->getStatus());
		$this->assertStringContainsString(ServiceUnavailable::class, $response->getBodyAsString());
		$this->assertStringNotContainsString('multistatus', $response->getBodyAsString());
	}
}
