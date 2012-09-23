<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

use \OC\Files\Cache\Cache as FileCache;

class Cache extends \UnitTestCase {
	/**
	 * @var \OC\Files\Storage\Temporary $storage;
	 */
	private $storage;

	private function createPath($path) {
		return new \OC\Files\File($this->storage, $path);
	}

	public function testSimple() {
		$file1 = $this->createPath('foo');
		$file2 = $this->createPath('foo/bar');
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'foo/folder');
		$data2 = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');

		$this->assertFalse(FileCache::inCache($file1));
		$this->assertEqual(FileCache::get($file1), null);

		$id1 = FileCache::put($file1, $data1);
		$this->assertTrue(FileCache::inCache($file1));
		$cacheData1 = FileCache::get($file1);
		foreach ($data1 as $key => $value) {
			$this->assertEqual($value, $cacheData1[$key]);
		}
		$this->assertEqual($cacheData1['fileid'], $id1);
		$this->assertEqual($id1, FileCache::getId($file1));

		$this->assertFalse(FileCache::inCache($file2));
		$id2 = FileCache::put($file2, $data2);
		$this->assertTrue(FileCache::inCache($file2));
		$cacheData2 = FileCache::get($file2);
		foreach ($data2 as $key => $value) {
			$this->assertEqual($value, $cacheData2[$key]);
		}
		$this->assertEqual($cacheData1['fileid'], $cacheData2['parent']);
		$this->assertEqual($cacheData2['fileid'], $id2);
		$this->assertEqual($id2, FileCache::getId($file2));
		$this->assertEqual($id1, FileCache::getParentId($file2));

		$newSize = 1050;
		$newId2 = FileCache::put($file2, array('size' => $newSize));
		$cacheData2 = FileCache::get($file2);
		$this->assertEqual($newId2, $id2);
		$this->assertEqual($cacheData2['size'], $newSize);
		$this->assertEqual($cacheData1, FileCache::get($file1));

		FileCache::remove($file2);
		$this->assertFalse(FileCache::inCache($file2));
		$this->assertEqual(FileCache::get($file2), null);
		$this->assertTrue(FileCache::inCache($file1));

		$this->assertEqual($cacheData1, FileCache::get($id1));
	}

	public function testPartial() {
		$file1 = $this->createPath('foo');

		FileCache::put($file1, array('size' => 10));
		$this->assertEqual(array('size' => 10), FileCache::get($file1));

		FileCache::put($file1, array('mtime' => 15));
		$this->assertEqual(array('size' => 10, 'mtime' => 15), FileCache::get($file1));

		FileCache::put($file1, array('size' => 12));
		$this->assertEqual(array('size' => 12, 'mtime' => 15), FileCache::get($file1));
	}

	public function testFolder() {
		$file1 = $this->createPath('folder');
		$file2 = $this->createPath('folder/bar');
		$file3 = $this->createPath('folder/foo');
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'foo/folder');
		$fileData = array();
		$fileData['bar'] = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');
		$fileData['foo'] = array('size' => 20, 'mtime' => 25, 'mimetype' => 'foo/file');

		FileCache::put($file1, $data1);
		FileCache::put($file2, $fileData['bar']);
		FileCache::put($file3, $fileData['foo']);

		$content = FileCache::getFolderContents($file1);
		$this->assertEqual(count($content), 2);
		foreach ($content as $cachedData) {
			$data = $fileData[$cachedData['name']];
			foreach ($data as $name => $value) {
				$this->assertEqual($value, $cachedData[$name]);
			}
		}
	}

	public function tearDown() {
		FileCache::removeStorage($this->storage);
	}

	public function setUp() {
		$this->storage = new \OC\Files\Storage\Temporary(array());
	}
}
