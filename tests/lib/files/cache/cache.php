<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

class Cache extends \UnitTestCase {
	/**
	 * @var \OC\Files\Storage\Temporary $storage;
	 */
	private $storage;

	/**
	 * @var \OC\Files\Cache\Cache $cache
	 */
	private $cache;

	public function testSimple() {
		$file1 = 'foo';
		$file2 = 'foo/bar';
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'foo/folder');
		$data2 = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');

		$this->assertFalse($this->cache->inCache($file1));
		$this->assertEqual($this->cache->get($file1), null);

		$id1 = $this->cache->put($file1, $data1);
		$this->assertTrue($this->cache->inCache($file1));
		$cacheData1 = $this->cache->get($file1);
		foreach ($data1 as $key => $value) {
			$this->assertEqual($value, $cacheData1[$key]);
		}
		$this->assertEqual($cacheData1['fileid'], $id1);
		$this->assertEqual($id1, $this->cache->getId($file1));

		$this->assertFalse($this->cache->inCache($file2));
		$id2 = $this->cache->put($file2, $data2);
		$this->assertTrue($this->cache->inCache($file2));
		$cacheData2 = $this->cache->get($file2);
		foreach ($data2 as $key => $value) {
			$this->assertEqual($value, $cacheData2[$key]);
		}
		$this->assertEqual($cacheData1['fileid'], $cacheData2['parent']);
		$this->assertEqual($cacheData2['fileid'], $id2);
		$this->assertEqual($id2, $this->cache->getId($file2));
		$this->assertEqual($id1, $this->cache->getParentId($file2));

		$newSize = 1050;
		$newId2 = $this->cache->put($file2, array('size' => $newSize));
		$cacheData2 = $this->cache->get($file2);
		$this->assertEqual($newId2, $id2);
		$this->assertEqual($cacheData2['size'], $newSize);
		$this->assertEqual($cacheData1, $this->cache->get($file1));

		$this->cache->remove($file2);
		$this->assertFalse($this->cache->inCache($file2));
		$this->assertEqual($this->cache->get($file2), null);
		$this->assertTrue($this->cache->inCache($file1));

		$this->assertEqual($cacheData1, $this->cache->get($id1));
	}

	public function testPartial() {
		$file1 = 'foo';

		$this->cache->put($file1, array('size' => 10));
		$this->assertEqual(array('size' => 10), $this->cache->get($file1));

		$this->cache->put($file1, array('mtime' => 15));
		$this->assertEqual(array('size' => 10, 'mtime' => 15), $this->cache->get($file1));

		$this->cache->put($file1, array('size' => 12));
		$this->assertEqual(array('size' => 12, 'mtime' => 15), $this->cache->get($file1));
	}

	public function testFolder() {
		$file1 = 'folder';
		$file2 = 'folder/bar';
		$file3 = 'folder/foo';
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'foo/folder');
		$fileData = array();
		$fileData['bar'] = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');
		$fileData['foo'] = array('size' => 20, 'mtime' => 25, 'mimetype' => 'foo/file');

		$this->cache->put($file1, $data1);
		$this->cache->put($file2, $fileData['bar']);
		$this->cache->put($file3, $fileData['foo']);

		$content = $this->cache->getFolderContents($file1);
		$this->assertEqual(count($content), 2);
		foreach ($content as $cachedData) {
			$data = $fileData[$cachedData['name']];
			foreach ($data as $name => $value) {
				$this->assertEqual($value, $cachedData[$name]);
			}
		}
	}

	public function tearDown() {
		$this->cache->clear();
	}

	public function setUp() {
		$this->storage = new \OC\Files\Storage\Temporary(array());
		$this->cache = new \OC\Files\Cache\Cache($this->storage);
	}
}
