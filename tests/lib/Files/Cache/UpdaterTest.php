<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\Files\Filesystem;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\StorageObjectStore;
use OC\Files\Storage\Temporary;
use OCP\Files\Storage\IStorage;

/**
 * Class UpdaterTest
 *
 * @group DB
 *
 * @package Test\Files\Cache
 */
class UpdaterTest extends \Test\TestCase {
	/**
	 * @var \OC\Files\Storage\Storage
	 */
	protected $storage;

	/**
	 * @var \OC\Files\Cache\Cache
	 */
	protected $cache;

	/**
	 * @var \OC\Files\View
	 */
	protected $view;

	/**
	 * @var \OC\Files\Cache\Updater
	 */
	protected $updater;

	protected function setUp(): void {
		parent::setUp();

		$this->loginAsUser();

		$this->storage = new Temporary([]);
		$this->updater = $this->storage->getUpdater();
		$this->cache = $this->storage->getCache();
	}

	protected function tearDown(): void {
		$this->logout();
		parent::tearDown();
	}

	public function testNewFile(): void {
		$this->storage->file_put_contents('foo.txt', 'bar');
		$this->assertFalse($this->cache->inCache('foo.txt'));

		$this->updater->update('foo.txt');

		$this->assertTrue($this->cache->inCache('foo.txt'));
		$cached = $this->cache->get('foo.txt');
		$this->assertEquals(3, $cached['size']);
		$this->assertEquals('text/plain', $cached['mimetype']);
	}

	public function testUpdatedFile(): void {
		$this->storage->file_put_contents('foo.txt', 'bar');
		$this->updater->update('foo.txt');

		$cached = $this->cache->get('foo.txt');
		$this->assertEquals(3, $cached['size']);
		$this->assertEquals('text/plain', $cached['mimetype']);

		$this->storage->file_put_contents('foo.txt', 'qwerty');

		$cached = $this->cache->get('foo.txt');
		$this->assertEquals(3, $cached['size']);

		$this->updater->update('/foo.txt');

		$cached = $this->cache->get('foo.txt');
		$this->assertEquals(6, $cached['size']);
	}

	public function testParentSize(): void {
		$this->storage->getScanner()->scan('');

		$parentCached = $this->cache->get('');
		$this->assertEquals(0, $parentCached['size']);

		$this->storage->file_put_contents('foo.txt', 'bar');

		$parentCached = $this->cache->get('');
		$this->assertEquals(0, $parentCached['size']);

		$this->updater->update('foo.txt');

		$parentCached = $this->cache->get('');
		$this->assertEquals(3, $parentCached['size']);

		$this->storage->file_put_contents('foo.txt', 'qwerty');

		$parentCached = $this->cache->get('');
		$this->assertEquals(3, $parentCached['size']);

		$this->updater->update('foo.txt');

		$parentCached = $this->cache->get('');
		$this->assertEquals(6, $parentCached['size']);

		$this->storage->unlink('foo.txt');

		$parentCached = $this->cache->get('');
		$this->assertEquals(6, $parentCached['size']);

		$this->updater->remove('foo.txt');

		$parentCached = $this->cache->get('');
		$this->assertEquals(0, $parentCached['size']);
	}

	public function testMove(): void {
		$this->storage->file_put_contents('foo.txt', 'qwerty');
		$this->updater->update('foo.txt');

		$this->assertTrue($this->cache->inCache('foo.txt'));
		$this->assertFalse($this->cache->inCache('bar.txt'));
		$cached = $this->cache->get('foo.txt');

		$this->storage->rename('foo.txt', 'bar.txt');

		$this->assertTrue($this->cache->inCache('foo.txt'));
		$this->assertFalse($this->cache->inCache('bar.txt'));

		$this->updater->renameFromStorage($this->storage, 'foo.txt', 'bar.txt');

		$this->assertFalse($this->cache->inCache('foo.txt'));
		$this->assertTrue($this->cache->inCache('bar.txt'));

		$cachedTarget = $this->cache->get('bar.txt');
		$this->assertEquals($cached['etag'], $cachedTarget['etag']);
		$this->assertEquals($cached['mtime'], $cachedTarget['mtime']);
		$this->assertEquals($cached['size'], $cachedTarget['size']);
		$this->assertEquals($cached['fileid'], $cachedTarget['fileid']);
	}

	public function testMoveNonExistingOverwrite(): void {
		$this->storage->file_put_contents('bar.txt', 'qwerty');
		$this->updater->update('bar.txt');

		$cached = $this->cache->get('bar.txt');

		$this->updater->renameFromStorage($this->storage, 'foo.txt', 'bar.txt');

		$this->assertFalse($this->cache->inCache('foo.txt'));
		$this->assertTrue($this->cache->inCache('bar.txt'));

		$cachedTarget = $this->cache->get('bar.txt');
		$this->assertEquals($cached['etag'], $cachedTarget['etag']);
		$this->assertEquals($cached['mtime'], $cachedTarget['mtime']);
		$this->assertEquals($cached['size'], $cachedTarget['size']);
		$this->assertEquals($cached['fileid'], $cachedTarget['fileid']);
	}

	public function testUpdateStorageMTime(): void {
		$this->storage->mkdir('sub');
		$this->storage->mkdir('sub2');
		$this->storage->file_put_contents('sub/foo.txt', 'qwerty');

		$this->updater->update('sub');
		$this->updater->update('sub/foo.txt');
		$this->updater->update('sub2');

		$cachedSourceParent = $this->cache->get('sub');
		$cachedSource = $this->cache->get('sub/foo.txt');

		$this->storage->rename('sub/foo.txt', 'sub2/bar.txt');

		// simulate storage having a different mtime
		$testmtime = 1433323578;

		// source storage mtime change
		$this->storage->touch('sub', $testmtime);

		// target storage mtime change
		$this->storage->touch('sub2', $testmtime);
		// some storages (like Dropbox) change storage mtime on rename
		$this->storage->touch('sub2/bar.txt', $testmtime);

		$this->updater->renameFromStorage($this->storage, 'sub/foo.txt', 'sub2/bar.txt');

		$cachedTargetParent = $this->cache->get('sub2');
		$cachedTarget = $this->cache->get('sub2/bar.txt');

		$this->assertEquals($cachedSource['mtime'], $cachedTarget['mtime'], 'file mtime preserved');

		$this->assertNotEquals($cachedTarget['storage_mtime'], $cachedTarget['mtime'], 'mtime is not storage_mtime for moved file');

		$this->assertEquals($testmtime, $cachedTarget['storage_mtime'], 'target file storage_mtime propagated');
		$this->assertNotEquals($testmtime, $cachedTarget['mtime'], 'target file mtime changed, not from storage');

		$this->assertEquals($testmtime, $cachedTargetParent['storage_mtime'], 'target parent storage_mtime propagated');
		$this->assertNotEquals($testmtime, $cachedTargetParent['mtime'], 'target folder mtime changed, not from storage');
	}

	public function testNewFileDisabled(): void {
		$this->storage->file_put_contents('foo.txt', 'bar');
		$this->assertFalse($this->cache->inCache('foo.txt'));

		$this->updater->disable();
		$this->updater->update('/foo.txt');

		$this->assertFalse($this->cache->inCache('foo.txt'));
	}

	public function testMoveCrossStorage(): void {
		$storage2 = new Temporary([]);
		$cache2 = $storage2->getCache();
		Filesystem::mount($storage2, [], '/bar');
		$this->storage->file_put_contents('foo.txt', 'qwerty');

		$this->updater->update('foo.txt');

		$this->assertTrue($this->cache->inCache('foo.txt'));
		$this->assertFalse($cache2->inCache('bar.txt'));
		$cached = $this->cache->get('foo.txt');

		// "rename"
		$storage2->file_put_contents('bar.txt', 'qwerty');
		$this->storage->unlink('foo.txt');

		$this->assertTrue($this->cache->inCache('foo.txt'));
		$this->assertFalse($cache2->inCache('bar.txt'));

		$storage2->getUpdater()->renameFromStorage($this->storage, 'foo.txt', 'bar.txt');

		$this->assertFalse($this->cache->inCache('foo.txt'));
		$this->assertTrue($cache2->inCache('bar.txt'));

		$cachedTarget = $cache2->get('bar.txt');
		$this->assertEquals($cached['mtime'], $cachedTarget['mtime']);
		$this->assertEquals($cached['size'], $cachedTarget['size']);
		$this->assertEquals($cached['etag'], $cachedTarget['etag']);
		$this->assertEquals($cached['fileid'], $cachedTarget['fileid']);
	}

	public function testMoveFolderCrossStorage(): void {
		$storage2 = new Temporary([]);
		$cache2 = $storage2->getCache();
		Filesystem::mount($storage2, [], '/bar');
		$this->storage->mkdir('foo');
		$this->storage->mkdir('foo/bar');
		$this->storage->file_put_contents('foo/foo.txt', 'qwerty');
		$this->storage->file_put_contents('foo/bar.txt', 'foo');
		$this->storage->file_put_contents('foo/bar/bar.txt', 'qwertyuiop');

		$this->storage->getScanner()->scan('');

		$this->assertTrue($this->cache->inCache('foo'));
		$this->assertTrue($this->cache->inCache('foo/foo.txt'));
		$this->assertTrue($this->cache->inCache('foo/bar.txt'));
		$this->assertTrue($this->cache->inCache('foo/bar'));
		$this->assertTrue($this->cache->inCache('foo/bar/bar.txt'));
		$cached = [];
		$cached[] = $this->cache->get('foo');
		$cached[] = $this->cache->get('foo/foo.txt');
		$cached[] = $this->cache->get('foo/bar.txt');
		$cached[] = $this->cache->get('foo/bar');
		$cached[] = $this->cache->get('foo/bar/bar.txt');

		// add extension to trigger the possible mimetype change
		$storage2->moveFromStorage($this->storage, 'foo', 'foo.b');
		$storage2->getUpdater()->renameFromStorage($this->storage, 'foo', 'foo.b');

		$this->assertFalse($this->cache->inCache('foo'));
		$this->assertFalse($this->cache->inCache('foo/foo.txt'));
		$this->assertFalse($this->cache->inCache('foo/bar.txt'));
		$this->assertFalse($this->cache->inCache('foo/bar'));
		$this->assertFalse($this->cache->inCache('foo/bar/bar.txt'));
		$this->assertTrue($cache2->inCache('foo.b'));
		$this->assertTrue($cache2->inCache('foo.b/foo.txt'));
		$this->assertTrue($cache2->inCache('foo.b/bar.txt'));
		$this->assertTrue($cache2->inCache('foo.b/bar'));
		$this->assertTrue($cache2->inCache('foo.b/bar/bar.txt'));

		$cachedTarget = [];
		$cachedTarget[] = $cache2->get('foo.b');
		$cachedTarget[] = $cache2->get('foo.b/foo.txt');
		$cachedTarget[] = $cache2->get('foo.b/bar.txt');
		$cachedTarget[] = $cache2->get('foo.b/bar');
		$cachedTarget[] = $cache2->get('foo.b/bar/bar.txt');

		foreach ($cached as $i => $old) {
			$new = $cachedTarget[$i];
			$this->assertEquals($old['mtime'], $new['mtime']);
			$this->assertEquals($old['size'], $new['size']);
			$this->assertEquals($old['etag'], $new['etag']);
			$this->assertEquals($old['fileid'], $new['fileid']);
			$this->assertEquals($old['mimetype'], $new['mimetype']);
		}
	}

	public function changeExtensionProvider(): array {
		return [
			[new Temporary()],
			[new ObjectStoreStorage(['objectstore' => new StorageObjectStore(new Temporary())])]
		];
	}

	/**
	 * @dataProvider changeExtensionProvider
	 */
	public function testChangeExtension(IStorage $storage) {
		$updater = $storage->getUpdater();
		$cache = $storage->getCache();
		$storage->file_put_contents('foo', 'qwerty');
		$updater->update('foo');

		$bareCached = $cache->get('foo');
		$this->assertEquals('application/octet-stream', $bareCached->getMimeType());

		$storage->rename('foo', 'foo.txt');
		$updater->renameFromStorage($storage, 'foo', 'foo.txt');

		$cached = $cache->get('foo.txt');
		$this->assertEquals('text/plain', $cached->getMimeType());

		$storage->rename('foo.txt', 'foo.md');
		$updater->renameFromStorage($storage, 'foo.txt', 'foo.md');

		$cachedTarget = $cache->get('foo.md');
		$this->assertEquals('text/markdown', $cachedTarget->getMimeType());
	}
}
