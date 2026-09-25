<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Upload;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File as DavFile;
use OCA\DAV\Upload\ChunkingV2Plugin;
use OCA\DAV\Upload\FutureFile;
use OCA\DAV\Upload\UploadFile;
use OCA\DAV\Upload\UploadFolder;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\Storage\IChunkedFileWrite;
use OCP\Files\Storage\IStorage;
use OCP\Files\StorageInvalidException;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\PreconditionFailed;
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
	private ICache&MockObject $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();
		$this->server->tree = $this->tree;

		$this->cache = $this->createMock(ICache::class);

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')
			->with(ChunkingV2Plugin::CACHE_KEY)
			->willReturn($this->cache);

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

	public function testBeforeDeleteCancelsV2UploadWithoutDestination(): void {
		$uploadPath = 'uploads/admin/upload-id';
		$targetPath = 'files/admin/file.zip';
		$targetInternalPath = 'files/file.zip';
		$uploadId = 'multipart-upload-id';

		$storage = $this->createMock(IChunkedFileWrite::class);
		$storage->method('instanceOfStorage')
			->willReturnCallback(
				static fn (string $class): bool => $class === IChunkedFileWrite::class,
			);
		$storage->method('getId')->willReturn('storage-id');

		$uploadFolder = $this->createUploadFolder($storage);
		$targetFile = $this->createTargetFile($storage, $targetInternalPath);

		$this->request->expects($this->once())
			->method('getPath')
			->willReturn($uploadPath);
		$this->request->expects($this->never())
			->method('getHeader');

		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->willReturnCallback(
				static function (string $path) use (
					$uploadPath,
					$uploadFolder,
					$targetPath,
					$targetFile,
				) {
					return match ($path) {
						$uploadPath => $uploadFolder,
						$targetPath => $targetFile,
						default => throw new \LogicException("Unexpected path: $path"),
					};
				},
			);

		$this->cache->expects($this->once())
			->method('get')
			->with('upload-id')
			->willReturn([
				ChunkingV2Plugin::UPLOAD_ID => $uploadId,
				ChunkingV2Plugin::UPLOAD_TARGET_PATH => $targetPath,
				ChunkingV2Plugin::UPLOAD_TARGET_ID => 42,
			]);

		$canceled = false;
		$storage->expects($this->once())
			->method('cancelChunkedWrite')
			->with($targetInternalPath, $uploadId)
			->willReturnCallback(
				static function () use (&$canceled): void {
					$canceled = true;
				},
			);
		$this->cache->expects($this->once())
			->method('remove')
			->with('upload-id')
			->willReturnCallback(
				function () use (&$canceled): bool {
					$this->assertTrue(
						$canceled,
						'The cache entry must not be removed before the backend write is canceled',
					);
					return true;
				},
			);

		$this->assertTrue(
			$this->plugin->beforeDelete($this->request, $this->response),
		);
	}

	public function testBeforeDeleteAllowsDavToHandleMissingPath(): void {
		$uploadPath = 'uploads/admin/missing-upload';

		$this->request->method('getPath')->willReturn($uploadPath);

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($uploadPath)
			->willThrowException(new NotFound());

		$this->cache->expects($this->never())
			->method('get');
		$this->cache->expects($this->never())
			->method('remove');

		$this->assertTrue(
			$this->plugin->beforeDelete($this->request, $this->response),
		);
	}

	public function testBeforeDeleteAllowsDavToHandleNonUploadFolder(): void {
		$path = 'files/admin/folder';
		$directory = $this->createMock(Directory::class);
		$directory->method('getName')->willReturn('folder');

		$this->request->method('getPath')->willReturn($path);

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($path)
			->willReturn($directory);

		$this->cache->expects($this->once())
			->method('get')
			->with('folder')
			->willReturn(null);
		$this->cache->expects($this->never())
			->method('remove');

		$this->assertTrue(
			$this->plugin->beforeDelete($this->request, $this->response),
		);
	}

	public function testBeforeDeleteAllowsDavToHandleUploadWithoutV2Metadata(): void {
		$uploadPath = 'uploads/admin/upload-id';

		$storage = $this->createMock(IStorage::class);
		$uploadFolder = $this->createUploadFolder($storage);

		$this->request->method('getPath')->willReturn($uploadPath);
		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($uploadPath)
			->willReturn($uploadFolder);

		$this->cache->expects($this->once())
			->method('get')
			->with('upload-id')
			->willReturn(null);
		$this->cache->expects($this->never())
			->method('remove');

		$storage->expects($this->never())
			->method('instanceOfStorage');

		$this->assertTrue(
			$this->plugin->beforeDelete($this->request, $this->response),
		);
	}

	public static function incompleteUploadMetadataProvider(): array {
		return [
			'missing upload ID' => [[
				ChunkingV2Plugin::UPLOAD_TARGET_PATH => 'files/admin/file.zip',
				ChunkingV2Plugin::UPLOAD_TARGET_ID => 42,
			]],
			'missing upload target path' => [[
				ChunkingV2Plugin::UPLOAD_ID => 'multipart-upload-id',
				ChunkingV2Plugin::UPLOAD_TARGET_ID => 42,
			]],
		];
	}

	/**
	 * @param array<string, int|string> $metadata
	 */
	#[DataProvider('incompleteUploadMetadataProvider')]
	public function testBeforeDeleteRejectsIncompleteUploadMetadata(
		array $metadata,
	): void {
		$uploadPath = 'uploads/admin/upload-id';

		$storage = $this->createMock(IChunkedFileWrite::class);
		$storage->expects($this->never())
			->method('cancelChunkedWrite');

		$uploadFolder = $this->createUploadFolder($storage);

		$this->request->method('getPath')->willReturn($uploadPath);
		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($uploadPath)
			->willReturn($uploadFolder);

		$this->cache->expects($this->once())
			->method('get')
			->with('upload-id')
			->willReturn($metadata);
		$this->cache->expects($this->never())
			->method('remove');

		$this->expectException(PreconditionFailed::class);
		$this->expectExceptionMessage('Incomplete metadata for chunked upload');

		$this->plugin->beforeDelete($this->request, $this->response);
	}

	public function testBeforeDeleteRejectsStorageWithoutChunkedWriteSupport(): void {
		$uploadPath = 'uploads/admin/upload-id';

		$storage = $this->createMock(IStorage::class);
		$storage->expects($this->once())
			->method('instanceOfStorage')
			->with(IChunkedFileWrite::class)
			->willReturn(false);

		$uploadFolder = $this->createUploadFolder($storage);

		$this->request->method('getPath')->willReturn($uploadPath);
		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($uploadPath)
			->willReturn($uploadFolder);

		$this->cache->expects($this->once())
			->method('get')
			->with('upload-id')
			->willReturn([
				ChunkingV2Plugin::UPLOAD_ID => 'multipart-upload-id',
				ChunkingV2Plugin::UPLOAD_TARGET_PATH => 'files/admin/file.zip',
				ChunkingV2Plugin::UPLOAD_TARGET_ID => 42,
			]);
		$this->cache->expects($this->never())
			->method('remove');

		$this->expectException(StorageInvalidException::class);
		$this->expectExceptionMessage(
			'Storage does not support chunked file writing',
		);

		$this->plugin->beforeDelete($this->request, $this->response);
	}

	public function testBeforeDeleteRejectsObjectStorageWithoutMultipartSupport(): void {
		$uploadPath = 'uploads/admin/upload-id';

		$objectStore = $this->createMock(IObjectStore::class);
		$storage = $this->createMock(ObjectStoreStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnCallback(
				static fn (string $class): bool => in_array($class, [
					IChunkedFileWrite::class,
					ObjectStoreStorage::class,
				], true),
			);
		$storage->method('getObjectStore')->willReturn($objectStore);
		$storage->expects($this->never())
			->method('cancelChunkedWrite');

		$uploadFolder = $this->createUploadFolder($storage);

		$this->request->method('getPath')->willReturn($uploadPath);
		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($uploadPath)
			->willReturn($uploadFolder);

		$this->cache->expects($this->once())
			->method('get')
			->with('upload-id')
			->willReturn([
				ChunkingV2Plugin::UPLOAD_ID => 'multipart-upload-id',
				ChunkingV2Plugin::UPLOAD_TARGET_PATH => 'files/admin/file.zip',
				ChunkingV2Plugin::UPLOAD_TARGET_ID => 42,
			]);
		$this->cache->expects($this->never())
			->method('remove');

		$this->expectException(StorageInvalidException::class);
		$this->expectExceptionMessage(
			'Storage does not support multi part uploads',
		);

		$this->plugin->beforeDelete($this->request, $this->response);
	}

	public function testBeforeDeletePropagatesCancellationFailure(): void {
		$uploadPath = 'uploads/admin/upload-id';
		$targetPath = 'files/admin/file.zip';
		$targetInternalPath = 'files/file.zip';
		$uploadId = 'multipart-upload-id';

		$storage = $this->createMock(IChunkedFileWrite::class);
		$storage->method('instanceOfStorage')
			->willReturnCallback(
				static fn (string $class): bool => $class === IChunkedFileWrite::class,
			);
		$storage->method('getId')->willReturn('storage-id');
		$storage->expects($this->once())
			->method('cancelChunkedWrite')
			->with($targetInternalPath, $uploadId)
			->willThrowException(new GenericFileException('Cancellation failed'));

		$uploadFolder = $this->createUploadFolder($storage);
		$targetFile = $this->createTargetFile($storage, $targetInternalPath);

		$this->request->method('getPath')->willReturn($uploadPath);
		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->willReturnCallback(
				static fn (string $path) => match ($path) {
					$uploadPath => $uploadFolder,
					$targetPath => $targetFile,
					default => throw new \LogicException("Unexpected path: $path"),
				},
			);

		$this->cache->expects($this->once())
			->method('get')
			->with('upload-id')
			->willReturn([
				ChunkingV2Plugin::UPLOAD_ID => $uploadId,
				ChunkingV2Plugin::UPLOAD_TARGET_PATH => $targetPath,
				ChunkingV2Plugin::UPLOAD_TARGET_ID => 42,
			]);
		$this->cache->expects($this->never())
			->method('remove');

		$this->expectException(GenericFileException::class);
		$this->expectExceptionMessage('Cancellation failed');

		$this->plugin->beforeDelete($this->request, $this->response);
	}

	public function testBeforeDeleteCancelsV2UploadUsingTemporaryTarget(): void {
		$uploadPath = 'uploads/admin/upload-id';
		$targetPath = 'files/admin/new-file.zip';
		$targetInternalPath = 'uploads/upload-id/.target';
		$uploadId = 'multipart-upload-id';

		$storage = $this->createMock(IChunkedFileWrite::class);
		$storage->method('instanceOfStorage')
			->willReturnCallback(
				static fn (string $class): bool => $class === IChunkedFileWrite::class,
			);
		$storage->expects($this->once())
			->method('cancelChunkedWrite')
			->with($targetInternalPath, $uploadId);

		$uploadFolder = $this->createUploadFolder($storage);

		$temporaryDavFile = $this->createMock(DavFile::class);
		$temporaryDavFile->method('getInternalPath')
			->willReturn($targetInternalPath);

		$temporaryUploadFile = $this->createMock(UploadFile::class);
		$temporaryUploadFile->method('getFile')
			->willReturn($temporaryDavFile);

		$uploadFolder->expects($this->once())
			->method('getChild')
			->with('.target')
			->willReturn($temporaryUploadFile);

		$this->request->method('getPath')->willReturn($uploadPath);

		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->willReturnCallback(
				static fn (string $path) => match ($path) {
					$uploadPath => $uploadFolder,
					$targetPath => throw new NotFound('Upload target not found'),
					default => throw new \LogicException("Unexpected path: $path"),
				},
			);

		$this->cache->expects($this->once())
			->method('get')
			->with('upload-id')
			->willReturn([
				ChunkingV2Plugin::UPLOAD_ID => $uploadId,
				ChunkingV2Plugin::UPLOAD_TARGET_PATH => $targetPath,
				ChunkingV2Plugin::UPLOAD_TARGET_ID => 42,
			]);
		$this->cache->expects($this->once())
			->method('remove')
			->with('upload-id');

		$this->assertTrue(
			$this->plugin->beforeDelete($this->request, $this->response),
		);
	}

	public function testBeforeDeletePropagatesMissingTemporaryTarget(): void {
		$uploadPath = 'uploads/admin/upload-id';
		$targetPath = 'files/admin/new-file.zip';

		$storage = $this->createMock(IChunkedFileWrite::class);
		$storage->method('instanceOfStorage')
			->willReturnCallback(
				static fn (string $class): bool => $class === IChunkedFileWrite::class,
			);
		$storage->expects($this->never())
			->method('cancelChunkedWrite');

		$uploadFolder = $this->createUploadFolder($storage);
		$uploadFolder->expects($this->once())
			->method('getChild')
			->with('.target')
			->willThrowException(new NotFound('Temporary upload target not found'));

		$this->request->method('getPath')->willReturn($uploadPath);

		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->willReturnCallback(
				static fn (string $path) => match ($path) {
					$uploadPath => $uploadFolder,
					$targetPath => throw new NotFound('Destination does not exist'),
					default => throw new \LogicException("Unexpected path: $path"),
				},
			);

		$this->cache->expects($this->once())
			->method('get')
			->with('upload-id')
			->willReturn([
				ChunkingV2Plugin::UPLOAD_ID => 'multipart-upload-id',
				ChunkingV2Plugin::UPLOAD_TARGET_PATH => $targetPath,
				ChunkingV2Plugin::UPLOAD_TARGET_ID => 42,
			]);
		$this->cache->expects($this->never())
			->method('remove');

		$this->expectException(NotFound::class);
		$this->expectExceptionMessage('Temporary upload target not found');

		$this->plugin->beforeDelete($this->request, $this->response);
	}

	private function createUploadFolder(IStorage $storage): UploadFolder {
		$uploadFolder = $this->createMock(UploadFolder::class);
		$uploadFolder->method('getName')->willReturn('upload-id');
		$uploadFolder->method('getStorage')->willReturn($storage);

		return $uploadFolder;
	}

	private function createTargetFile(
		IStorage $storage,
		string $internalPath,
	): DavFile {
		$node = $this->createMock(File::class);
		$node->method('isUpdateable')->willReturn(true);
		$node->method('getStorage')->willReturn($storage);

		$file = $this->createMock(DavFile::class);
		$file->method('getNode')->willReturn($node);
		$file->method('getInternalPath')->willReturn($internalPath);

		return $file;
	}
}
