<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

class Watcher extends \PHPUnit_Framework_TestCase {

	/**
	 * @var \OC\Files\Storage\Storage[] $storages;
	 */
	private $storages = array();

	public function setUp() {
		\OC\Files\Filesystem::clearMounts();
	}

	public function tearDown() {
		foreach ($this->storages as $storage) {
			$cache = $storage->getCache();
			$ids = $cache->getAll();
			$permissionsCache = $storage->getPermissionsCache();
			$permissionsCache->removeMultiple($ids, \OC_User::getUser());
			$cache->clear();
		}
	}

	/**
	 * @medium
	 */
	function testWatcher() {
		$storage = $this->getTestStorage();
		$cache = $storage->getCache();
		$updater = $storage->getWatcher();

		//set the mtime to the past so it can detect an mtime change
		$cache->put('', array('storage_mtime' => 10));

		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$this->assertTrue($cache->inCache('folder/bar2.txt'));

		$this->assertFalse($cache->inCache('bar.test'));
		$storage->file_put_contents('bar.test', 'foo');
		$updater->checkUpdate('');
		$this->assertTrue($cache->inCache('bar.test'));
		$cachedData = $cache->get('bar.test');
		$this->assertEquals(3, $cachedData['size']);

		$cache->put('bar.test', array('storage_mtime' => 10));
		$storage->file_put_contents('bar.test', 'test data');

		// make sure that PHP can read the new size correctly
		clearstatcache();

		$updater->checkUpdate('bar.test');
		$cachedData = $cache->get('bar.test');
		$this->assertEquals(9, $cachedData['size']);

		$cache->put('folder', array('storage_mtime' => 10));

		$storage->unlink('folder/bar2.txt');
		$updater->checkUpdate('folder');

		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$this->assertFalse($cache->inCache('folder/bar2.txt'));
	}

	/**
	 * @medium
	 */
	public function testFileToFolder() {
		$storage = $this->getTestStorage();
		$cache = $storage->getCache();
		$updater = $storage->getWatcher();

		//set the mtime to the past so it can detect an mtime change
		$cache->put('', array('storage_mtime' => 10));

		$storage->unlink('foo.txt');
		$storage->rename('folder', 'foo.txt');
		$updater->checkUpdate('');

		$entry = $cache->get('foo.txt');
		$this->assertEquals('httpd/unix-directory', $entry['mimetype']);
		$this->assertFalse($cache->inCache('folder'));
		$this->assertFalse($cache->inCache('folder/bar.txt'));

		$storage = $this->getTestStorage();
		$cache = $storage->getCache();
		$updater = $storage->getWatcher();

		//set the mtime to the past so it can detect an mtime change
		$cache->put('foo.txt', array('storage_mtime' => 10));

		$storage->unlink('foo.txt');
		$storage->rename('folder', 'foo.txt');
		$updater->checkUpdate('foo.txt');

		$entry = $cache->get('foo.txt');
		$this->assertEquals('httpd/unix-directory', $entry['mimetype']);
		$this->assertTrue($cache->inCache('foo.txt/bar.txt'));
	}

	/**
	 * @param bool $scan
	 * @return \OC\Files\Storage\Storage
	 */
	private function getTestStorage($scan = true) {
		$storage = new \OC\Files\Storage\Temporary(array());
		$textData = "dummy file data\n";
		$imgData = file_get_contents(\OC::$SERVERROOT . '/core/img/logo.png');
		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', $textData);
		$storage->file_put_contents('foo.png', $imgData);
		$storage->file_put_contents('folder/bar.txt', $textData);
		$storage->file_put_contents('folder/bar2.txt', $textData);

		if ($scan) {
			$scanner = $storage->getScanner();
			$scanner->scan('');
		}
		$this->storages[] = $storage;
		return $storage;
	}
}
