<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache\Wrapper;

use OC\Files\Cache\Cache;
use OC\Files\Cache\Wrapper\CachePermissionsMask;
use OCP\Constants;
use Test\Files\Cache\CacheTest;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class CachePermissionsMaskTest extends CacheTest {
	protected Cache $sourceCache;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->sourceCache = $this->cache;
		$this->cache = $this->getMaskedCache(Constants::PERMISSION_ALL);
	}

	protected function getMaskedCache(int $mask): CachePermissionsMask {
		return new CachePermissionsMask($this->sourceCache, $mask);
	}

	/**
	 * @return list<array{0: int}>
	 */
	public static function maskProvider(): array {
		return [
			[Constants::PERMISSION_ALL],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_SHARE],
			[Constants::PERMISSION_ALL - Constants::PERMISSION_UPDATE],
			[Constants::PERMISSION_READ]
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('maskProvider')]
	public function testGetMasked(int $mask): void {
		$cache = $this->getMaskedCache($mask);
		$data = ['size' => 100, 'mtime' => 50, 'mimetype' => 'text/plain', 'permissions' => Constants::PERMISSION_ALL];
		$this->sourceCache->put('foo', $data);
		$result = $cache->get('foo');
		$this->assertSame($mask, $result['permissions']);

		$data = ['size' => 100, 'mtime' => 50, 'mimetype' => 'text/plain', 'permissions' => Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE];
		$this->sourceCache->put('bar', $data);
		$result = $cache->get('bar');
		$this->assertSame($mask & ~Constants::PERMISSION_DELETE, $result['permissions']);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('maskProvider')]
	public function testGetFolderContentMasked(int $mask): void {
		$this->storage->mkdir('foo');
		$this->storage->file_put_contents('foo/bar', 'asd');
		$this->storage->file_put_contents('foo/asd', 'bar');
		$this->storage->getScanner()->scan('');

		$cache = $this->getMaskedCache($mask);
		$files = $cache->getFolderContents('foo');
		$this->assertCount(2, $files);

		foreach ($files as $file) {
			$this->assertSame($mask & ~Constants::PERMISSION_CREATE, $file['permissions']);
		}
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('maskProvider')]
	public function testSearchMasked(int $mask): void {
		$this->storage->mkdir('foo');
		$this->storage->file_put_contents('foo/bar', 'asd');
		$this->storage->file_put_contents('foo/foobar', 'bar');
		$this->storage->getScanner()->scan('');

		$cache = $this->getMaskedCache($mask);
		$files = $cache->search('%bar');
		$this->assertCount(2, $files);

		foreach ($files as $file) {
			$this->assertSame($mask & ~Constants::PERMISSION_CREATE, $file['permissions']);
		}
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('maskProvider')]
	public function testGetScannedFileMasked(int $mask): void {
		$this->storage->mkdir('foo');
		$this->storage->file_put_contents('foo/bar', 'asd');
		$this->storage->getScanner()->scan('');

		$cache = $this->getMaskedCache($mask);
		$file = $cache->get('foo/bar');

		$this->assertNotFalse($file);
		$this->assertSame($mask & ~Constants::PERMISSION_CREATE, $file['permissions']);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('maskProvider')]
	public function testGetScannedFolderMasked(int $mask): void {
		$this->storage->mkdir('foo');
		$this->storage->file_put_contents('foo/bar', 'asd');
		$this->storage->getScanner()->scan('');

		$cache = $this->getMaskedCache($mask);
		$folder = $cache->get('foo');

		$this->assertNotFalse($folder);
		$this->assertSame($mask, $folder['permissions']);
	}

	public function testGetSetsScanPermissionsFromOriginalPermissions(): void {
		$mask = Constants::PERMISSION_READ;
		$cache = $this->getMaskedCache($mask);

		$data = [
			'size' => 100,
			'mtime' => 50,
			'mimetype' => 'text/plain',
			'permissions' => Constants::PERMISSION_ALL,
		];
		$this->sourceCache->put('foo', $data);

		$result = $cache->get('foo');

		$this->assertSame(Constants::PERMISSION_ALL, $result['scan_permissions']);
		$this->assertSame(Constants::PERMISSION_ALL & $mask, $result['permissions']);
	}

	public function testGetDoesNotOverwriteExistingScanPermissions(): void {
		$mask = Constants::PERMISSION_READ;
		$cache = $this->getMaskedCache($mask);

		$data = [
			'size' => 100,
			'mtime' => 50,
			'mimetype' => 'text/plain',
			'permissions' => Constants::PERMISSION_ALL,
			'scan_permissions' => Constants::PERMISSION_READ,
		];
		$this->sourceCache->put('foo', $data);

		$result = $cache->get('foo');

		$this->assertSame(Constants::PERMISSION_READ, $result['scan_permissions']);
		$this->assertSame(Constants::PERMISSION_ALL & $mask, $result['permissions']);
	}
}
