<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\Files\Storage\Home;
use OC\User\User;
use OCP\Files\Cache\ICache;
use OCP\ITempManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

class DummyUser extends User {
	public function __construct(
		private string $uid,
		private string $home,
	) {
	}

	#[\Override]
	public function getHome(): string {
		return $this->home;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getUID(): string {
		return $this->uid;
	}
}

/**
 * Tests home-storage-specific cache behavior, with a current focus on size reporting.
 */
#[Group('DB')]
class HomeCacheTest extends TestCase {
	private Home $storage;
	private ICache $cache;
	private User $user;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->user = new DummyUser('foo', Server::get(ITempManager::class)->getTemporaryFolder());
		$this->storage = new Home(['user' => $this->user]);
		$this->cache = $this->storage->getCache();
	}

	/**
	 * The files folder size must ignore children with unknown sizes, and the root
	 * must report the files folder size.
	 */
	public function testFilesFolderSizeIgnoresUnknownChildSizes(): void {
		$dir1 = 'files/knownsize';
		$dir2 = 'files/unknownsize';
		$fileData = [];
		$fileData[''] = ['size' => -1, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory'];
		$fileData['files'] = ['size' => -1, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory'];
		$fileData[$dir1] = ['size' => 1000, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory'];
		$fileData[$dir2] = ['size' => -1, 'mtime' => 25, 'mimetype' => 'httpd/unix-directory'];

		$this->cache->put('', $fileData['']);
		$this->cache->put('files', $fileData['files']);
		$this->cache->put($dir1, $fileData[$dir1]);
		$this->cache->put($dir2, $fileData[$dir2]);

		$this->assertTrue($this->cache->inCache('files'));
		$this->assertTrue($this->cache->inCache($dir1));
		$this->assertTrue($this->cache->inCache($dir2));

		$this->assertSame(1000, $this->cache->calculateFolderSize('files'));
		$this->assertSame(1000, $this->cache->get('files')['size']);
		$this->assertSame(1000, $this->cache->get('')['size']);

		// Removing the root also removes its descendants.
		$this->cache->remove('');

		$this->assertFalse($this->cache->inCache(''));
		$this->assertFalse($this->cache->inCache('files'));
		$this->assertFalse($this->cache->inCache($dir1));
		$this->assertFalse($this->cache->inCache($dir2));
	}

	/**
	 * The root reports the files folder size, not the stored root size or the
	 * size of other entries directly under the root.
	 */
	public function testRootFolderSizeMatchesFilesFolderSize(): void {
		$dir1 = 'files';
		$fileData = [];
		$fileData[''] = ['size' => 1500, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory'];
		$fileData[$dir1] = ['size' => 1000, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory'];
		$fileData['test.txt'] = ['size' => 500, 'mtime' => 20, 'mimetype' => 'text/plain'];

		$this->cache->put('', $fileData['']);
		$this->cache->put($dir1, $fileData[$dir1]);
		$this->cache->put('test.txt', $fileData['test.txt']);

		$this->assertTrue($this->cache->inCache('test.txt'));

		$this->assertSame(1000, $this->cache->get('files')['size']);
		$this->assertSame(1000, $this->cache->get('')['size']);

		// Removing the root also removes its descendants.
		$this->cache->remove('');

		$this->assertFalse($this->cache->inCache(''));
		$this->assertFalse($this->cache->inCache($dir1));
		$this->assertFalse($this->cache->inCache('test.txt'));
	}

	public static function specialFolderPathsDataProvider(): array {
		return [
			['files_trashbin'],
			['files_versions'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('specialFolderPathsDataProvider')]
	public function testSpecialFolderSizeIgnoresUnknownChildSizes(string $path): void {
		$knownSizePath = $path . '/knownsize';
		$unknownSizePath = $path . '/unknownsize';

		$this->cache->put('', ['size' => 0, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory']);
		$this->cache->put($path, ['size' => -1, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory']);
		$this->cache->put($knownSizePath, ['size' => 1000, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory']);
		$this->cache->put($unknownSizePath, ['size' => -1, 'mtime' => 25, 'mimetype' => 'httpd/unix-directory']);

		$this->assertSame(1000, $this->cache->calculateFolderSize($path));
		$this->assertSame(1000, $this->cache->get($path)['size']);

		$this->cache->remove('');

		$this->assertFalse($this->cache->inCache($path));
		$this->assertFalse($this->cache->inCache($knownSizePath));
		$this->assertFalse($this->cache->inCache($unknownSizePath));
	}
}
