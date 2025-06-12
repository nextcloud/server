<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache\Wrapper;

use OC\Files\Cache\Wrapper\CachePermissionsMask;
use OCP\Constants;
use Test\Files\Cache\CacheTest;

/**
 * Class CachePermissionsMask
 *
 * @group DB
 *
 * @package Test\Files\Cache\Wrapper
 */
class CachePermissionsMaskTest extends CacheTest {
	/**
	 * @var \OC\Files\Cache\Cache $sourceCache
	 */
	protected $sourceCache;

	protected function setUp(): void {
		parent::setUp();
		$this->storage->mkdir('foo');
		$this->sourceCache = $this->cache;
		$this->cache = $this->getMaskedCached(Constants::PERMISSION_ALL);
	}

	protected function getMaskedCached($mask) {
		return new CachePermissionsMask($this->sourceCache, $mask);
	}

	public static function maskProvider(): array {
		return [
			[Constants::PERMISSION_ALL],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_SHARE],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_UPDATE],
			[Constants::PERMISSION_READ]
		];
	}

	/**
	 * @dataProvider maskProvider
	 * @param int $mask
	 */
	public function testGetMasked($mask): void {
		$cache = $this->getMaskedCached($mask);
		$data = ['size' => 100, 'mtime' => 50, 'mimetype' => 'text/plain', 'permissions' => Constants::PERMISSION_ALL];
		$this->sourceCache->put('foo', $data);
		$result = $cache->get('foo');
		$this->assertEquals($mask, $result['permissions']);

		$data = ['size' => 100, 'mtime' => 50, 'mimetype' => 'text/plain', 'permissions' => Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE];
		$this->sourceCache->put('bar', $data);
		$result = $cache->get('bar');
		$this->assertEquals($mask & ~Constants::PERMISSION_DELETE, $result['permissions']);
	}

	/**
	 * @dataProvider maskProvider
	 * @param int $mask
	 */
	public function testGetFolderContentMasked($mask): void {
		$this->storage->mkdir('foo');
		$this->storage->file_put_contents('foo/bar', 'asd');
		$this->storage->file_put_contents('foo/asd', 'bar');
		$this->storage->getScanner()->scan('');

		$cache = $this->getMaskedCached($mask);
		$files = $cache->getFolderContents('foo');
		$this->assertCount(2, $files);

		foreach ($files as $file) {
			$this->assertEquals($mask & ~Constants::PERMISSION_CREATE, $file['permissions']);
		}
	}

	/**
	 * @dataProvider maskProvider
	 * @param int $mask
	 */
	public function testSearchMasked($mask): void {
		$this->storage->mkdir('foo');
		$this->storage->file_put_contents('foo/bar', 'asd');
		$this->storage->file_put_contents('foo/foobar', 'bar');
		$this->storage->getScanner()->scan('');

		$cache = $this->getMaskedCached($mask);
		$files = $cache->search('%bar');
		$this->assertCount(2, $files);

		foreach ($files as $file) {
			$this->assertEquals($mask & ~Constants::PERMISSION_CREATE, $file['permissions']);
		}
	}
}
