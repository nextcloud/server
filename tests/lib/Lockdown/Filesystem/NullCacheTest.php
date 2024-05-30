<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Lockdown\Filesystem;

use OC\ForbiddenException;
use OC\Lockdown\Filesystem\NullCache;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\FileInfo;

class NulLCacheTest extends \Test\TestCase {
	/** @var NullCache */
	private $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->cache = new NullCache();
	}

	public function testGetNumericStorageId() {
		$this->assertSame(-1, $this->cache->getNumericStorageId());
	}

	public function testGetEmpty() {
		$this->assertNull($this->cache->get('foo'));
	}

	public function testGet() {
		$data = $this->cache->get('');

		$this->assertEquals(-1, $data['fileid']);
		$this->assertEquals(-1, $data['parent']);
		$this->assertEquals('', $data['name']);
		$this->assertEquals('', $data['path']);
		$this->assertEquals('0', $data['size']);
		$this->assertEquals('', $data['etag']);
		$this->assertEquals(FileInfo::MIMETYPE_FOLDER, $data['mimetype']);
		$this->assertEquals('httpd', $data['mimepart']);
		$this->assertEquals(Constants::PERMISSION_READ, $data['permissions']);
	}

	public function testGetFolderContents() {
		$this->assertSame([], $this->cache->getFolderContents('foo'));
	}

	public function testGetFolderContentsById() {
		$this->assertSame([], $this->cache->getFolderContentsById(42));
	}

	public function testPut() {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->put('foo', ['size' => 100]);
	}

	public function testInsert() {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->insert('foo', ['size' => 100]);
	}

	public function testUpdate() {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->update('foo', ['size' => 100]);
	}

	public function testGetId() {
		$this->assertSame(-1, $this->cache->getId('foo'));
	}

	public function testGetParentId() {
		$this->assertSame(-1, $this->cache->getParentId('foo'));
	}

	public function testInCache() {
		$this->assertTrue($this->cache->inCache(''));
		$this->assertFalse($this->cache->inCache('foo'));
	}

	public function testRemove() {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->remove('foo');
	}

	public function testMove() {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->move('foo', 'bar');
	}

	public function testMoveFromCache() {
		$sourceCache = $this->createMock(ICache::class);

		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->moveFromCache($sourceCache, 'foo', 'bar');
	}

	public function testGetStatus() {
		$this->assertSame(ICache::COMPLETE, $this->cache->getStatus('foo'));
	}

	public function testSearch() {
		$this->assertSame([], $this->cache->search('foo'));
	}

	public function testSearchByMime() {
		$this->assertSame([], $this->cache->searchByMime('foo'));
	}

	public function testGetIncomplete() {
		$this->assertSame([], $this->cache->getIncomplete());
	}

	public function testGetPathById() {
		$this->assertSame('', $this->cache->getPathById(42));
	}

	public function testNormalize() {
		$this->assertSame('foo/ bar /', $this->cache->normalize('foo/ bar /'));
	}
}
