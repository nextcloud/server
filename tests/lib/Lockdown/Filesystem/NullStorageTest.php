<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Lockdown\Filesystem;

use Icewind\Streams\IteratorDirectory;
use OC\Files\FileInfo;
use OC\ForbiddenException;
use OC\Lockdown\Filesystem\NullCache;
use OC\Lockdown\Filesystem\NullStorage;
use OCP\Files\Storage\IStorage;
use Test\TestCase;

class NullStorageTest extends TestCase {
	/** @var NullStorage */
	private $storage;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = new NullStorage([]);
	}

	public function testGetId(): void {
		$this->assertSame('null', $this->storage->getId());
	}

	public function testMkdir(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->mkdir('foo');
	}

	public function testRmdir(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->rmdir('foo');
	}

	public function testOpendir(): void {
		$this->assertInstanceOf(IteratorDirectory::class, $this->storage->opendir('foo'));
	}

	public function testIs_dir(): void {
		$this->assertTrue($this->storage->is_dir(''));
		$this->assertFalse($this->storage->is_dir('foo'));
	}

	public function testIs_file(): void {
		$this->assertFalse($this->storage->is_file('foo'));
	}

	public function testStat(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->stat('foo');
	}

	public function testFiletype(): void {
		$this->assertSame('dir', $this->storage->filetype(''));
		$this->assertFalse($this->storage->filetype('foo'));
	}

	public function testFilesize(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->filesize('foo');
	}

	public function testIsCreatable(): void {
		$this->assertFalse($this->storage->isCreatable('foo'));
	}

	public function testIsReadable(): void {
		$this->assertTrue($this->storage->isReadable(''));
		$this->assertFalse($this->storage->isReadable('foo'));
	}

	public function testIsUpdatable(): void {
		$this->assertFalse($this->storage->isUpdatable('foo'));
	}

	public function testIsDeletable(): void {
		$this->assertFalse($this->storage->isDeletable('foo'));
	}

	public function testIsSharable(): void {
		$this->assertFalse($this->storage->isSharable('foo'));
	}

	public function testGetPermissions(): void {
		$this->assertEquals(0, $this->storage->getPermissions('foo'));
	}

	public function testFile_exists(): void {
		$this->assertTrue($this->storage->file_exists(''));
		$this->assertFalse($this->storage->file_exists('foo'));
	}

	public function testFilemtime(): void {
		$this->assertFalse($this->storage->filemtime('foo'));
	}

	public function testFile_get_contents(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->file_get_contents('foo');
	}

	public function testFile_put_contents(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->file_put_contents('foo', 'bar');
	}

	public function testUnlink(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->unlink('foo');
	}

	public function testRename(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->rename('foo', 'bar');
	}

	public function testCopy(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->copy('foo', 'bar');
	}

	public function testFopen(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->fopen('foo', 'R');
	}

	public function testGetMimeType(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->getMimeType('foo');
	}

	public function testHash(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->hash('md5', 'foo', true);
	}

	public function testFree_space(): void {
		$this->assertSame(FileInfo::SPACE_UNKNOWN, $this->storage->free_space('foo'));
	}

	public function testTouch(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->touch('foo');
	}

	public function testGetLocalFile(): void {
		$this->assertFalse($this->storage->getLocalFile('foo'));
	}

	public function testHasUpdated(): void {
		$this->assertFalse($this->storage->hasUpdated('foo', 42));
	}

	public function testGetETag(): void {
		$this->assertSame('', $this->storage->getETag('foo'));
	}

	public function testIsLocal(): void {
		$this->assertFalse($this->storage->isLocal());
	}

	public function testGetDirectDownload(): void {
		$this->assertFalse($this->storage->getDirectDownload('foo'));
	}

	public function testCopyFromStorage(): void {
		$sourceStorage = $this->createMock(IStorage::class);

		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->copyFromStorage($sourceStorage, 'foo', 'bar');
	}

	public function testMoveFromStorage(): void {
		$sourceStorage = $this->createMock(IStorage::class);

		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->storage->moveFromStorage($sourceStorage, 'foo', 'bar');
	}

	public function testTest() {
		$this->assertTrue($this->storage->test());
		return true;
	}

	public function testGetOwner(): void {
		$this->assertFalse($this->storage->getOwner('foo'));
	}

	public function testGetCache(): void {
		$this->assertInstanceOf(NullCache::class, $this->storage->getCache('foo'));
	}
}
