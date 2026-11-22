<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Upload;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\S3;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Memcache\Redis;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Upload\ChunkingV2Plugin;
use OCA\DAV\Upload\FutureFile;
use OCA\DAV\Upload\UploadFile;
use OCA\DAV\Upload\UploadFolder;
use OCP\Files\Storage\IChunkedFileWrite;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use PHPUnit\Framework\Attributes\DataProvider;
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

		$this->assertTrue($this->plugin->forbiddenMethod($this->request));
	}

	public function testBeforeGetBlocksFutureFile(): void {
		$this->expectException(MethodNotAllowed::class);

		$this->request->method('getPath')->willReturn('uploads/admin/web-file-upload-id/1');
		$this->tree->method('getNodeForPath')->willReturn($this->createMock(FutureFile::class));

		$this->plugin->forbiddenMethod($this->request);
	}

	public function testBeforeGetBlocksUploadFile(): void {
		$this->expectException(MethodNotAllowed::class);

		$this->request->method('getPath')->willReturn('uploads/admin/web-file-upload-id/.target');
		$this->tree->method('getNodeForPath')->willReturn($this->createMock(UploadFile::class));

		$this->plugin->forbiddenMethod($this->request);
	}

	public function testBeforeGetAllowsRegularNode(): void {
		$this->request->method('getPath')->willReturn('files/admin/foo.txt');
		$this->tree->method('getNodeForPath')->willReturn($this->createMock(Directory::class));

		$this->assertTrue($this->plugin->forbiddenMethod($this->request));
	}

	public static function dataAfterMkcolChunkedWrite(): array {
		return [
			'without encryption' => ['encrypted' => false],
			'with server-side encryption' => ['encrypted' => true],
		];
	}

	#[DataProvider(methodName: 'dataAfterMkcolChunkedWrite')]
	public function testAfterMkcolStartsChunkedWriteOnlyWithoutEncryption(bool $encrypted): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->willReturnCallback(static fn (string $key, mixed $default = '') => $key === 'memcache.distributed' ? Redis::class : $default);
		$this->overwriteService(IConfig::class, $config);

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($this->createMock(Redis::class));
		$plugin = new ChunkingV2Plugin($cacheFactory);
		$plugin->initialize($this->server);

		$storage = $this->createMock(ObjectStoreStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnCallback(static fn (string $class): bool => match ($class) {
				IChunkedFileWrite::class, ObjectStoreStorage::class => true,
				Encryption::class => $encrypted,
				default => false,
			});
		$storage->method('getObjectStore')->willReturn($this->createMock(S3::class));
		$storage->expects($encrypted ? $this->never() : $this->once())
			->method('startChunkedWrite')
			->with('uploads/web-file-upload-id/.target')
			->willReturn('upload-token');

		$targetFile = $this->createMock(File::class);
		$targetFile->method('getId')->willReturn(42);
		$targetFile->method('getInternalPath')->willReturn('uploads/web-file-upload-id/.target');
		$tempTarget = $this->createMock(UploadFile::class);
		$tempTarget->method('getFile')->willReturn($targetFile);

		$uploadFolder = $this->createMock(UploadFolder::class);
		$uploadFolder->method('getName')->willReturn('web-file-upload-id');
		$uploadFolder->method('getStorage')->willReturn($storage);
		$uploadFolder->method('getChild')->with('.target')->willReturn($tempTarget);

		$this->tree->method('getNodeForPath')
			->willReturnCallback(static function (string $path) use ($uploadFolder) {
				if ($path === 'uploads/admin/web-file-upload-id') {
					return $uploadFolder;
				}
				throw new NotFound();
			});
		$this->request->method('getPath')->willReturn('uploads/admin/web-file-upload-id');
		$this->request->method('getHeader')
			->with('Destination')
			->willReturn('http://localhost/remote.php/dav/files/admin/target.txt');
		$this->server->method('calculateUri')->willReturn('files/admin/target.txt');

		$this->response->expects($encrypted ? $this->never() : $this->once())
			->method('setStatus')
			->with(201);

		$this->assertTrue($plugin->afterMkcol($this->request, $this->response));
	}
}
