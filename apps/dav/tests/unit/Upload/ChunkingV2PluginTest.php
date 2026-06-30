<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Upload\ChunkingV2Plugin;
use OCA\DAV\Upload\FutureFile;
use OCA\DAV\Upload\UploadFile;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class ChunkingV2PluginTest extends TestCase {
	/** @var Server | MockObject */
	private $server;
	/** @var Tree | MockObject */
	private $tree;
	/** @var ChunkingV2Plugin */
	private $plugin;
	/** @var RequestInterface | MockObject */
	private $request;
	/** @var ResponseInterface | MockObject */
	private $response;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();
		$this->server->tree = $this->tree;

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($this->createMock(ICache::class));

		$this->plugin = new ChunkingV2Plugin($cacheFactory);

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$this->server->httpRequest = $this->request;
		$this->server->httpResponse = $this->response;

		$this->plugin->initialize($this->server);
	}

	/**
	 * The handler only blocks reading intermediate uploads. A path it cannot
	 * resolve (e.g. an app-provided collection such as `versions`/`trashbin`
	 * that is registered later in the request lifecycle) must not abort the
	 * request: beforeGet has to swallow the NotFound and let normal handling
	 * (and any real 404) take over. This is the regression guard for the
	 * "version/trashbin downloads return 404" bug.
	 */
	public function testBeforeGetIgnoresUnresolvablePath(): void {
		$this->request->method('getPath')->willReturn('versions/admin/versions/74/1782831952');
		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with('versions/admin/versions/74/1782831952')
			->willThrowException(new NotFound("File not found: versions in 'root'"));

		$this->assertTrue($this->plugin->beforeGet($this->request));
	}

	public function testBeforeGetBlocksFutureFile(): void {
		$this->expectException(MethodNotAllowed::class);

		$this->request->method('getPath')->willReturn('uploads/admin/web-file-upload-id/1');
		$this->tree->method('getNodeForPath')->willReturn($this->createMock(FutureFile::class));

		$this->plugin->beforeGet($this->request);
	}

	public function testBeforeGetBlocksUploadFile(): void {
		$this->expectException(MethodNotAllowed::class);

		$this->request->method('getPath')->willReturn('uploads/admin/web-file-upload-id/.target');
		$this->tree->method('getNodeForPath')->willReturn($this->createMock(UploadFile::class));

		$this->plugin->beforeGet($this->request);
	}

	public function testBeforeGetAllowsRegularNode(): void {
		$this->request->method('getPath')->willReturn('files/admin/foo.txt');
		$this->tree->method('getNodeForPath')->willReturn($this->createMock(Directory::class));

		$this->assertTrue($this->plugin->beforeGet($this->request));
	}
}
