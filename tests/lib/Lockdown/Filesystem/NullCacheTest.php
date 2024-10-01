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

	public function testGetNumericStorageId(): void {
		$this->assertSame(-1, $this->cache->getNumericStorageId());
	}

	public function testGetEmpty(): void {
		$this->assertNull($this->cache->get('foo'));
	}

	public function testGet(): void {
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

	public function testGetFolderContents(): void {
		$this->assertSame([], $this->cache->getFolderContents('foo'));
	}

	public function testGetFolderContentsById(): void {
		$this->assertSame([], $this->cache->getFolderContentsById(42));
	}

	public function testPut(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->put('foo', ['size' => 100]);
	}

	public function testInsert(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->insert('foo', ['size' => 100]);
	}

	public function testUpdate(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->update('foo', ['size' => 100]);
	}

	public function testGetId(): void {
		$this->assertSame(-1, $this->cache->getId('foo'));
	}

	public function testGetParentId(): void {
		$this->assertSame(-1, $this->cache->getParentId('foo'));
	}

	public function testInCache(): void {
		$this->assertTrue($this->cache->inCache(''));
		$this->assertFalse($this->cache->inCache('foo'));
	}

	public function testRemove(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->remove('foo');
	}

	public function testMove(): void {
		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->move('foo', 'bar');
	}

	public function testMoveFromCache(): void {
		$sourceCache = $this->createMock(ICache::class);

		$this->expectException(ForbiddenException::class);
		$this->expectExceptionMessage('This request is not allowed to access the filesystem');

		$this->cache->moveFromCache($sourceCache, 'foo', 'bar');
	}

	public function testGetStatus(): void {
		$this->assertSame(ICache::COMPLETE, $this->cache->getStatus('foo'));
	}

	public function testSearch(): void {
		$this->assertSame([], $this->cache->search('foo'));
	}

	public function testSearchByMime(): void {
		$this->assertSame([], $this->cache->searchByMime('foo'));
	}

	public function testGetIncomplete(): void {
		$this->assertSame([], $this->cache->getIncomplete());
	}

	public function testGetPathById(): void {
		$this->assertSame('', $this->cache->getPathById(42));
	}

	public function testNormalize(): void {
		$this->assertSame('foo/ bar /', $this->cache->normalize('foo/ bar /'));
	}
}
