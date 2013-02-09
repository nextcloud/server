<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

use \OC\Files\Filesystem as Filesystem;

class Updater extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	private $storage;

	/**
	 * @var \OC\Files\Cache\Scanner $scanner
	 */
	private $scanner;

	/**
	 * @var \OC\Files\Cache\Cache $cache
	 */
	private $cache;

	private static $user;

	public function setUp() {
		$this->storage = new \OC\Files\Storage\Temporary(array());
		$textData = "dummy file data\n";
		$imgData = file_get_contents(\OC::$SERVERROOT . '/core/img/logo.png');
		$this->storage->mkdir('folder');
		$this->storage->file_put_contents('foo.txt', $textData);
		$this->storage->file_put_contents('foo.png', $imgData);
		$this->storage->file_put_contents('folder/bar.txt', $textData);
		$this->storage->file_put_contents('folder/bar2.txt', $textData);

		$this->scanner = $this->storage->getScanner();
		$this->scanner->scan('');
		$this->cache = $this->storage->getCache();

		if (!self::$user) {
			if (!\OC\Files\Filesystem::getView()) {
				self::$user = uniqid();
				\OC\Files\Filesystem::init('/' . self::$user . '/files');
			} else {
				self::$user = \OC_User::getUser();
			}
		}

		Filesystem::clearMounts();
		Filesystem::mount($this->storage, array(), '/' . self::$user . '/files');

		\OC_Hook::connect('OC_Filesystem', 'post_write', '\OC\Files\Cache\Updater', 'writeHook');
		\OC_Hook::connect('OC_Filesystem', 'post_delete', '\OC\Files\Cache\Updater', 'deleteHook');
		\OC_Hook::connect('OC_Filesystem', 'post_rename', '\OC\Files\Cache\Updater', 'renameHook');

	}

	public function tearDown() {
		if ($this->cache) {
			$this->cache->clear();
		}
		Filesystem::tearDown();
	}

	public function testWrite() {
		$textSize = strlen("dummy file data\n");
		$imageSize = filesize(\OC::$SERVERROOT . '/core/img/logo.png');
		$rootCachedData = $this->cache->get('');
		$this->assertEquals(3 * $textSize + $imageSize, $rootCachedData['size']);

		$fooCachedData = $this->cache->get('foo.txt');
		Filesystem::file_put_contents('foo.txt', 'asd');
		$cachedData = $this->cache->get('foo.txt');
		$this->assertEquals(3, $cachedData['size']);
		$this->assertNotEquals($fooCachedData['etag'], $cachedData['etag']);
		$mtime = $cachedData['mtime'];
		$cachedData = $this->cache->get('');
		$this->assertEquals(2 * $textSize + $imageSize + 3, $cachedData['size']);
		$this->assertNotEquals($rootCachedData['etag'], $cachedData['etag']);
		$this->assertGreaterThanOrEqual($rootCachedData['mtime'], $mtime);
		$rootCachedData = $cachedData;

		$this->assertFalse($this->cache->inCache('bar.txt'));
		Filesystem::file_put_contents('bar.txt', 'asd');
		$this->assertTrue($this->cache->inCache('bar.txt'));
		$cachedData = $this->cache->get('bar.txt');
		$this->assertEquals(3, $cachedData['size']);
		$mtime = $cachedData['mtime'];
		$cachedData = $this->cache->get('');
		$this->assertEquals(2 * $textSize + $imageSize + 2 * 3, $cachedData['size']);
		$this->assertNotEquals($rootCachedData['etag'], $cachedData['etag']);
		$this->assertGreaterThanOrEqual($rootCachedData['mtime'], $mtime);
	}

	public function testDelete() {
		$textSize = strlen("dummy file data\n");
		$imageSize = filesize(\OC::$SERVERROOT . '/core/img/logo.png');
		$rootCachedData = $this->cache->get('');
		$this->assertEquals(3 * $textSize + $imageSize, $rootCachedData['size']);

		$this->assertTrue($this->cache->inCache('foo.txt'));
		Filesystem::unlink('foo.txt', 'asd');
		$this->assertFalse($this->cache->inCache('foo.txt'));
		$cachedData = $this->cache->get('');
		$this->assertEquals(2 * $textSize + $imageSize, $cachedData['size']);
		$this->assertNotEquals($rootCachedData['etag'], $cachedData['etag']);
		$this->assertGreaterThanOrEqual($rootCachedData['mtime'], $cachedData['mtime']);
		$rootCachedData = $cachedData;

		Filesystem::mkdir('bar_folder');
		$this->assertTrue($this->cache->inCache('bar_folder'));
		$cachedData = $this->cache->get('');
		$this->assertNotEquals($rootCachedData['etag'], $cachedData['etag']);
		$rootCachedData = $cachedData;
		Filesystem::rmdir('bar_folder');
		$this->assertFalse($this->cache->inCache('bar_folder'));
		$cachedData = $this->cache->get('');
		$this->assertNotEquals($rootCachedData['etag'], $cachedData['etag']);
		$this->assertGreaterThanOrEqual($rootCachedData['mtime'], $cachedData['mtime']);
	}

	public function testRename() {
		$textSize = strlen("dummy file data\n");
		$imageSize = filesize(\OC::$SERVERROOT . '/core/img/logo.png');
		$rootCachedData = $this->cache->get('');
		$this->assertEquals(3 * $textSize + $imageSize, $rootCachedData['size']);

		$this->assertTrue($this->cache->inCache('foo.txt'));
		$fooCachedData = $this->cache->get('foo.txt');
		$this->assertFalse($this->cache->inCache('bar.txt'));
		Filesystem::rename('foo.txt', 'bar.txt');
		$this->assertFalse($this->cache->inCache('foo.txt'));
		$this->assertTrue($this->cache->inCache('bar.txt'));
		$cachedData = $this->cache->get('bar.txt');
		$this->assertNotEquals($fooCachedData['etag'], $cachedData['etag']);
		$mtime = $cachedData['mtime'];
		$cachedData = $this->cache->get('');
		$this->assertEquals(3 * $textSize + $imageSize, $cachedData['size']);
		$this->assertNotEquals($rootCachedData['etag'], $cachedData['etag']);
		$this->assertEquals($mtime, $cachedData['mtime']);
	}
}
