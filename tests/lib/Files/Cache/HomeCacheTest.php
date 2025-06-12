<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\Files\Storage\Home;
use OC\User\User;
use OCP\ITempManager;
use OCP\Server;

class DummyUser extends User {
	/**
	 * @param string $uid
	 * @param string $home
	 */
	public function __construct(
		private $uid,
		private $home,
	) {
	}

	/**
	 * @return string
	 */
	public function getHome() {
		return $this->home;
	}

	/**
	 * @return string
	 */
	public function getUID() {
		return $this->uid;
	}
}

/**
 * Class HomeCacheTest
 *
 * @group DB
 *
 * @package Test\Files\Cache
 */
class HomeCacheTest extends \Test\TestCase {
	/**
	 * @var \OC\Files\Storage\Home $storage
	 */
	private $storage;

	/**
	 * @var \OC\Files\Cache\HomeCache $cache
	 */
	private $cache;

	/**
	 * @var \OC\User\User $user
	 */
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->user = new DummyUser('foo', Server::get(ITempManager::class)->getTemporaryFolder());
		$this->storage = new Home(['user' => $this->user]);
		$this->cache = $this->storage->getCache();
	}

	/**
	 * Tests that the root and files folder size calculation ignores the subdirs
	 * that have an unknown size. This makes sure that quota calculation still
	 * works as it's based on the "files" folder size.
	 */
	public function testRootFolderSizeIgnoresUnknownUpdate(): void {
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

		// check that files and root size ignored the unknown sizes
		$this->assertEquals(1000, $this->cache->calculateFolderSize('files'));

		// clean up
		$this->cache->remove('');
		$this->cache->remove('files');
		$this->cache->remove($dir1);
		$this->cache->remove($dir2);

		$this->assertFalse($this->cache->inCache('files'));
		$this->assertFalse($this->cache->inCache($dir1));
		$this->assertFalse($this->cache->inCache($dir2));
	}

	public function testRootFolderSizeIsFilesSize(): void {
		$dir1 = 'files';
		$afile = 'test.txt';
		$fileData = [];
		$fileData[''] = ['size' => 1500, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory'];
		$fileData[$dir1] = ['size' => 1000, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory'];
		$fileData[$afile] = ['size' => 500, 'mtime' => 20];

		$this->cache->put('', $fileData['']);
		$this->cache->put($dir1, $fileData[$dir1]);

		$this->assertTrue($this->cache->inCache($dir1));

		// check that root size ignored the unknown sizes
		$data = $this->cache->get('files');
		$this->assertEquals(1000, $data['size']);
		$data = $this->cache->get('');
		$this->assertEquals(1000, $data['size']);

		// clean up
		$this->cache->remove('');
		$this->cache->remove($dir1);

		$this->assertFalse($this->cache->inCache($dir1));
	}
}
