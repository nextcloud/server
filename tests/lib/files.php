<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file. */

use \OC\Files\Filesystem as Filesystem;

class Test_Files extends PHPUnit_Framework_TestCase {
	/**
	 * @var \OC\Files\Storage\Storage[] $storages;
	 */
	private $storages = array();

	public function setUp() {
		Filesystem::clearMounts();
	}

	public function tearDown() {
		foreach ($this->storages as $storage) {
			$cache = $storage->getCache();
			$cache->clear();
		}
	}

	public function testCacheAPI() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$storage3 = $this->getTestStorage();
		Filesystem::mount($storage1, array(), '/');
		Filesystem::mount($storage2, array(), '/substorage');
		Filesystem::mount($storage3, array(), '/folder/anotherstorage');
		$textSize = strlen("dummy file data\n");
		$imageSize = filesize(\OC::$SERVERROOT . '/core/img/logo.png');
		$storageSize = $textSize * 2 + $imageSize;

		$cachedData = OC_Files::getFileInfo('/foo.txt');
		$this->assertEquals($textSize, $cachedData['size']);
		$this->assertEquals('text/plain', $cachedData['mimetype']);

		$cachedData = OC_Files::getFileInfo('/');
		$this->assertEquals($storageSize * 3, $cachedData['size']);
		$this->assertEquals('httpd/unix-directory', $cachedData['mimetype']);

		$cachedData = OC_Files::getFileInfo('/folder');
		$this->assertEquals($storageSize + $textSize, $cachedData['size']);
		$this->assertEquals('httpd/unix-directory', $cachedData['mimetype']);

		$folderData = OC_Files::getDirectoryContent('/');
		/**
		 * expected entries:
		 * folder
		 * foo.png
		 * foo.txt
		 * substorage
		 */
		$this->assertEquals(4, count($folderData));
		$this->assertEquals('folder', $folderData[0]['name']);
		$this->assertEquals('foo.png', $folderData[1]['name']);
		$this->assertEquals('foo.txt', $folderData[2]['name']);
		$this->assertEquals('substorage', $folderData[3]['name']);

		$this->assertEquals($storageSize + $textSize, $folderData[0]['size']);
		$this->assertEquals($imageSize, $folderData[1]['size']);
		$this->assertEquals($textSize, $folderData[2]['size']);
		$this->assertEquals($storageSize, $folderData[3]['size']);
	}

	/**
	 * @return OC\Files\Storage\Storage
	 */
	private function getTestStorage() {
		$storage = new \OC\Files\Storage\Temporary(array());
		$textData = "dummy file data\n";
		$imgData = file_get_contents(\OC::$SERVERROOT . '/core/img/logo.png');
		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', $textData);
		$storage->file_put_contents('foo.png', $imgData);
		$storage->file_put_contents('folder/bar.txt', $textData);

		$scanner = $storage->getScanner();
		$scanner->scan('');
		$this->storages[] = $storage;
		return $storage;
	}
}
