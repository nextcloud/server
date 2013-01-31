<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

class Cache extends \PHPUnit_Framework_TestCase {
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
		$this->assertEquals($this->cache->get($file1), null);

		$id1 = $this->cache->put($file1, $data1);
		$this->assertTrue($this->cache->inCache($file1));
		$cacheData1 = $this->cache->get($file1);
		foreach ($data1 as $key => $value) {
			$this->assertEquals($value, $cacheData1[$key]);
		}
		$this->assertEquals($cacheData1['mimepart'], 'foo');
		$this->assertEquals($cacheData1['fileid'], $id1);
		$this->assertEquals($id1, $this->cache->getId($file1));

		$this->assertFalse($this->cache->inCache($file2));
		$id2 = $this->cache->put($file2, $data2);
		$this->assertTrue($this->cache->inCache($file2));
		$cacheData2 = $this->cache->get($file2);
		foreach ($data2 as $key => $value) {
			$this->assertEquals($value, $cacheData2[$key]);
		}
		$this->assertEquals($cacheData1['fileid'], $cacheData2['parent']);
		$this->assertEquals($cacheData2['fileid'], $id2);
		$this->assertEquals($id2, $this->cache->getId($file2));
		$this->assertEquals($id1, $this->cache->getParentId($file2));

		$newSize = 1050;
		$newId2 = $this->cache->put($file2, array('size' => $newSize));
		$cacheData2 = $this->cache->get($file2);
		$this->assertEquals($newId2, $id2);
		$this->assertEquals($cacheData2['size'], $newSize);
		$this->assertEquals($cacheData1, $this->cache->get($file1));

		$this->cache->remove($file2);
		$this->assertFalse($this->cache->inCache($file2));
		$this->assertEquals($this->cache->get($file2), null);
		$this->assertTrue($this->cache->inCache($file1));

		$this->assertEquals($cacheData1, $this->cache->get($id1));
	}

	public function testPartial() {
		$file1 = 'foo';

		$this->cache->put($file1, array('size' => 10));
		$this->assertEquals(array('size' => 10), $this->cache->get($file1));

		$this->cache->put($file1, array('mtime' => 15));
		$this->assertEquals(array('size' => 10, 'mtime' => 15), $this->cache->get($file1));

		$this->cache->put($file1, array('size' => 12));
		$this->assertEquals(array('size' => 12, 'mtime' => 15), $this->cache->get($file1));
	}

	public function testFolder() {
		$file1 = 'folder';
		$file2 = 'folder/bar';
		$file3 = 'folder/foo';
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'httpd/unix-directory');
		$fileData = array();
		$fileData['bar'] = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');
		$fileData['foo'] = array('size' => 20, 'mtime' => 25, 'mimetype' => 'foo/file');

		$this->cache->put($file1, $data1);
		$this->cache->put($file2, $fileData['bar']);
		$this->cache->put($file3, $fileData['foo']);

		$content = $this->cache->getFolderContents($file1);
		$this->assertEquals(count($content), 2);
		foreach ($content as $cachedData) {
			$data = $fileData[$cachedData['name']];
			foreach ($data as $name => $value) {
				$this->assertEquals($value, $cachedData[$name]);
			}
		}

		$file4 = 'folder/unkownSize';
		$fileData['unkownSize'] = array('size' => -1, 'mtime' => 25, 'mimetype' => 'foo/file');
		$this->cache->put($file4, $fileData['unkownSize']);

		$this->assertEquals(-1, $this->cache->calculateFolderSize($file1));

		$fileData['unkownSize'] = array('size' => 5, 'mtime' => 25, 'mimetype' => 'foo/file');
		$this->cache->put($file4, $fileData['unkownSize']);

		$this->assertEquals(1025, $this->cache->calculateFolderSize($file1));

		$this->cache->remove('folder');
		$this->assertFalse($this->cache->inCache('folder/foo'));
		$this->assertFalse($this->cache->inCache('folder/bar'));
	}

	function testStatus() {
		$this->assertEquals(\OC\Files\Cache\Cache::NOT_FOUND, $this->cache->getStatus('foo'));
		$this->cache->put('foo', array('size' => -1));
		$this->assertEquals(\OC\Files\Cache\Cache::PARTIAL, $this->cache->getStatus('foo'));
		$this->cache->put('foo', array('size' => -1, 'mtime' => 20, 'mimetype' => 'foo/file'));
		$this->assertEquals(\OC\Files\Cache\Cache::SHALLOW, $this->cache->getStatus('foo'));
		$this->cache->put('foo', array('size' => 10));
		$this->assertEquals(\OC\Files\Cache\Cache::COMPLETE, $this->cache->getStatus('foo'));
	}

	function testSearch() {
		$file1 = 'folder';
		$file2 = 'folder/foobar';
		$file3 = 'folder/foo';
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'foo/folder');
		$fileData = array();
		$fileData['foobar'] = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');
		$fileData['foo'] = array('size' => 20, 'mtime' => 25, 'mimetype' => 'foo/file');

		$this->cache->put($file1, $data1);
		$this->cache->put($file2, $fileData['foobar']);
		$this->cache->put($file3, $fileData['foo']);

		$this->assertEquals(2, count($this->cache->search('%foo%')));
		$this->assertEquals(1, count($this->cache->search('foo')));
		$this->assertEquals(1, count($this->cache->search('%folder%')));
		$this->assertEquals(1, count($this->cache->search('folder%')));
		$this->assertEquals(3, count($this->cache->search('%')));

		$this->assertEquals(3, count($this->cache->searchByMime('foo')));
		$this->assertEquals(2, count($this->cache->searchByMime('foo/file')));
	}

	function testMove() {
		$file1 = 'folder';
		$file2 = 'folder/bar';
		$file3 = 'folder/foo';
		$file4 = 'folder/foo/1';
		$file5 = 'folder/foo/2';
		$data = array('size' => 100, 'mtime' => 50, 'mimetype' => 'foo/bar');

		$this->cache->put($file1, $data);
		$this->cache->put($file2, $data);
		$this->cache->put($file3, $data);
		$this->cache->put($file4, $data);
		$this->cache->put($file5, $data);

		$this->cache->move('folder/foo', 'folder/foobar');

		$this->assertFalse($this->cache->inCache('folder/foo'));
		$this->assertFalse($this->cache->inCache('folder/foo/1'));
		$this->assertFalse($this->cache->inCache('folder/foo/2'));

		$this->assertTrue($this->cache->inCache('folder/bar'));
		$this->assertTrue($this->cache->inCache('folder/foobar'));
		$this->assertTrue($this->cache->inCache('folder/foobar/1'));
		$this->assertTrue($this->cache->inCache('folder/foobar/2'));
	}

	function testGetIncomplete() {
		$file1 = 'folder1';
		$file2 = 'folder2';
		$file3 = 'folder3';
		$file4 = 'folder4';
		$data = array('size' => 10, 'mtime' => 50, 'mimetype' => 'foo/bar');

		$this->cache->put($file1, $data);
		$data['size'] = -1;
		$this->cache->put($file2, $data);
		$this->cache->put($file3, $data);
		$data['size'] = 12;
		$this->cache->put($file4, $data);

		$this->assertEquals($file3, $this->cache->getIncomplete());
	}

	function testNonExisting() {
		$this->assertFalse($this->cache->get('foo.txt'));
		$this->assertEquals(array(), $this->cache->getFolderContents('foo'));
	}

	function testGetById() {
		$storageId = $this->storage->getId();
		$data = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');
		$id = $this->cache->put('foo', $data);
		$this->assertEquals(array($storageId, 'foo'), \OC\Files\Cache\Cache::getById($id));
	}

	public function tearDown() {
		$this->cache->clear();
	}

	public function setUp() {
		$this->storage = new \OC\Files\Storage\Temporary(array());
		$this->cache = new \OC\Files\Cache\Cache($this->storage);
	}
}
