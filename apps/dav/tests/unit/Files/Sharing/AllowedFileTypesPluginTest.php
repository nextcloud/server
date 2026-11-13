<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Files\Sharing;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Files\Sharing\AllowedFileTypesPlugin;
use OCA\DAV\Upload\FutureFile;
use OCA\DAV\Upload\UploadFolder;
use OCA\DAV\Upload\UploadHome;
use OCP\Files\IMimeTypeDetector;
use OCP\Share\IAttributes;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\DAV\ICollection;
use Sabre\DAV\IFile;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Test\TestCase;

class AllowedFileTypesPluginTest extends TestCase {

	private AllowedFileTypesPlugin $plugin;

	private IAttributes&MockObject $attributes;
	private Server&MockObject $server;
	private Tree&MockObject $tree;
	private RequestInterface&MockObject $request;

	protected function setUp(): void {
		parent::setUp();

		$mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$mimeTypeDetector->method('detectPath')
			->willReturnCallback(static fn (string $path): string => match (pathinfo($path, PATHINFO_EXTENSION)) {
				'pdf' => 'application/pdf',
				'jpg' => 'image/jpeg',
				'gz' => 'application/gzip',
				default => 'application/octet-stream',
			});

		$this->attributes = $this->createMock(IAttributes::class);
		$share = $this->createMock(IShare::class);
		$share->method('getAttributes')
			->willReturn($this->attributes);

		$this->request = $this->createMock(RequestInterface::class);
		$this->tree = $this->createMock(Tree::class);
		$this->server = $this->createMock(Server::class);
		$this->server->tree = $this->tree;
		$this->server->httpRequest = $this->request;

		$this->plugin = new AllowedFileTypesPlugin($mimeTypeDetector);
		$this->plugin->initialize($this->server);
		$this->plugin->setShare($share);
	}

	private function setAllowedTypes(mixed $allowedTypes): void {
		$this->attributes->method('getAttribute')
			->with('upload', 'allowed_types')
			->willReturn($allowedTypes);
	}

	public static function dataBeforeBind(): array {
		return [
			'no restriction' => [null, 'files/token/run.exe', true],
			'empty list' => [[], 'files/token/run.exe', true],
			'extension' => [['.pdf'], 'files/token/doc.pdf', true],
			'extension is case insensitive' => [['.PDF'], 'files/token/Doc.Pdf', true],
			'multi-part extension' => [['.tar.gz'], 'files/token/archive.tar.gz', true],
			'extension mismatch' => [['.pdf'], 'files/token/run.exe', false],
			'no extension' => [['.pdf'], 'files/token/README', false],
			'mime type' => [['application/pdf'], 'files/token/doc.pdf', true],
			'mime type mismatch' => [['application/pdf'], 'files/token/photo.jpg', false],
			'mime type group' => [['image/*', 'video/*'], 'files/token/photo.jpg', true],
			'mime type group mismatch' => [['image/*'], 'files/token/doc.pdf', false],
			'invalid entries are ignored' => [[42, '', '.pdf'], 'files/token/doc.pdf', true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataBeforeBind')]
	public function testBeforeBindOnPut(mixed $allowedTypes, string $path, bool $allowed): void {
		$this->setAllowedTypes($allowedTypes);
		$this->request->method('getMethod')
			->willReturn('PUT');
		$this->tree->method('getNodeForPath')
			->willReturn($this->createMock(ICollection::class));

		if (!$allowed) {
			$this->expectException(UnsupportedMediaType::class);
		}

		$this->assertTrue($this->plugin->beforeBind($path));
	}

	public function testFolderCreationIsAllowed(): void {
		$this->setAllowedTypes(['.pdf']);
		$this->request->method('getMethod')
			->willReturn('MKCOL');

		$this->assertTrue($this->plugin->beforeBind('files/token/folder.exe'));
	}

	public function testFolderMoveIsAllowed(): void {
		$this->setAllowedTypes(['.pdf']);
		$this->request->method('getMethod')
			->willReturn('MOVE');
		$this->request->method('getPath')
			->willReturn('files/token/folder');
		$this->tree->method('getNodeForPath')
			->willReturn($this->createMock(Directory::class));

		$this->assertTrue($this->plugin->beforeBind('files/token/folder.exe'));
	}

	public function testUploadChunkIsSkipped(): void {
		$this->setAllowedTypes(['.pdf']);
		$this->request->method('getMethod')
			->willReturn('PUT');
		$this->tree->method('getNodeForPath')
			->with('uploads/token/123')
			->willReturn($this->createMock(UploadFolder::class));

		$this->assertTrue($this->plugin->beforeBind('uploads/token/123/1'));
	}

	public static function dataForeignCollectionTransfer(): array {
		return [
			'copy upload folder' => ['COPY', 'uploads/token/123', UploadFolder::class],
			'move upload folder' => ['MOVE', 'uploads/token/123', UploadFolder::class],
			'copy upload home' => ['COPY', 'uploads/token', UploadHome::class],
		];
	}

	/**
	 * @param class-string $sourceClass
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataForeignCollectionTransfer')]
	public function testForeignCollectionTransferIsRejected(string $method, string $sourcePath, string $sourceClass): void {
		$this->setAllowedTypes(['.pdf']);
		$this->request->method('getMethod')
			->willReturn($method);
		$this->request->method('getPath')
			->willReturn($sourcePath);
		$this->tree->method('getNodeForPath')
			->willReturnMap([
				[$sourcePath, $this->createMock($sourceClass)],
				['files/token', $this->createMock(Directory::class)],
			]);

		$this->expectException(Forbidden::class);
		$this->plugin->beforeBind('files/token/folder.pdf');
	}

	public static function dataBeforeMkcol(): array {
		return [
			'allowed type' => ['uploads/token/123', 'files/token/doc.pdf', UploadHome::class, true],
			'disallowed type' => ['uploads/token/123', 'files/token/run.exe', UploadHome::class, false],
			'no destination' => ['uploads/token/123', null, UploadHome::class, true],
			'not a chunked upload' => ['files/token/folder', 'files/token/run.exe', ICollection::class, true],
		];
	}

	/**
	 * @param class-string $parentClass
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataBeforeMkcol')]
	public function testBeforeMkcol(string $path, ?string $destination, string $parentClass, bool $allowed): void {
		$baseUri = 'https://example.com/public.php/dav/';
		$this->setAllowedTypes(['.pdf']);
		$this->request->method('getPath')
			->willReturn($path);
		$this->request->method('getHeader')
			->with('Destination')
			->willReturn($destination === null ? null : $baseUri . $destination);
		$this->server->method('calculateUri')
			->willReturnCallback(static fn (string $uri): string => substr($uri, strlen($baseUri)));
		$this->tree->method('getNodeForPath')
			->willReturn($this->createMock($parentClass));

		if (!$allowed) {
			$this->expectException(UnsupportedMediaType::class);
		}

		$this->assertTrue($this->plugin->beforeMkcol($this->request));
	}

	public function testBeforeMkcolWithoutRestriction(): void {
		$this->setAllowedTypes(null);
		$this->request->method('getHeader')
			->willReturn('https://example.com/public.php/dav/files/token/run.exe');
		$this->tree->expects($this->never())
			->method('getNodeForPath');

		$this->assertTrue($this->plugin->beforeMkcol($this->request));
	}

	public function testChunkedUploadIsCheckedOnAssembly(): void {
		$this->setAllowedTypes(['.pdf']);
		$this->request->method('getMethod')
			->willReturn('MOVE');
		$this->request->method('getPath')
			->willReturn('uploads/token/123/.file');
		$this->tree->method('getNodeForPath')
			->willReturnMap([
				['uploads/token/123/.file', $this->createMock(FutureFile::class)],
				['files/token', $this->createMock(ICollection::class)],
			]);

		$this->expectException(UnsupportedMediaType::class);
		$this->plugin->beforeBind('files/token/run.exe');
	}

	public function testRenameToDisallowedTypeIsRejected(): void {
		$this->setAllowedTypes(['.pdf']);
		$this->request->method('getMethod')
			->willReturn('MOVE');
		$this->request->method('getPath')
			->willReturn('files/token/doc.pdf');
		$this->tree->method('getNodeForPath')
			->willReturnMap([
				['files/token/doc.pdf', $this->createMock(IFile::class)],
				['files/token', $this->createMock(ICollection::class)],
			]);

		$this->expectException(UnsupportedMediaType::class);
		$this->plugin->beforeBind('files/token/doc.exe');
	}
}
