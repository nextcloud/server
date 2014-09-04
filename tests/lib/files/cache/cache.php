<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

use PHPUnit_Framework_MockObject_MockObject;

class LongId extends \OC\Files\Storage\Temporary {
	public function getId() {
		return 'long:' . str_repeat('foo', 50) . parent::getId();
	}
}

class Cache extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OC\Files\Storage\Temporary $storage ;
	 */
	private $storage;
	/**
	 * @var \OC\Files\Storage\Temporary $storage2 ;
	 */
	private $storage2;

	/**
	 * @var \OC\Files\Cache\Cache $cache
	 */
	private $cache;
	/**
	 * @var \OC\Files\Cache\Cache $cache2
	 */
	private $cache2;

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

	/**
	 * @dataProvider folderDataProvider
	 */
	public function testFolder($folder) {
		$file2 = $folder.'/bar';
		$file3 = $folder.'/foo';
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'httpd/unix-directory');
		$fileData = array();
		$fileData['bar'] = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');
		$fileData['foo'] = array('size' => 20, 'mtime' => 25, 'mimetype' => 'foo/file');

		$this->cache->put($folder, $data1);
		$this->cache->put($file2, $fileData['bar']);
		$this->cache->put($file3, $fileData['foo']);

		$content = $this->cache->getFolderContents($folder);
		$this->assertEquals(count($content), 2);
		foreach ($content as $cachedData) {
			$data = $fileData[$cachedData['name']];
			foreach ($data as $name => $value) {
				$this->assertEquals($value, $cachedData[$name]);
			}
		}

		$file4 = $folder.'/unkownSize';
		$fileData['unkownSize'] = array('size' => -1, 'mtime' => 25, 'mimetype' => 'foo/file');
		$this->cache->put($file4, $fileData['unkownSize']);

		$this->assertEquals(-1, $this->cache->calculateFolderSize($folder));

		$fileData['unkownSize'] = array('size' => 5, 'mtime' => 25, 'mimetype' => 'foo/file');
		$this->cache->put($file4, $fileData['unkownSize']);

		$this->assertEquals(1025, $this->cache->calculateFolderSize($folder));

		$this->cache->remove($file2);
		$this->cache->remove($file3);
		$this->cache->remove($file4);
		$this->assertEquals(0, $this->cache->calculateFolderSize($folder));

		$this->cache->remove($folder);
		$this->assertFalse($this->cache->inCache($folder.'/foo'));
		$this->assertFalse($this->cache->inCache($folder.'/bar'));
	}

	public function folderDataProvider() {

		return array(
			array('folder'),
			// that was too easy, try something harder
			array('☺, WHITE SMILING FACE, UTF-8 hex E298BA'),
			// what about 4 byte utf-8
			array('😐, NEUTRAL_FACE, UTF-8 hex F09F9890'),
			// now the crazy stuff
			array(', UNASSIGNED PRIVATE USE, UTF-8 hex EF9890'),
		);
	}

	public function testEncryptedFolder() {
		$file1 = 'folder';
		$file2 = 'folder/bar';
		$file3 = 'folder/foo';
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'httpd/unix-directory');
		$fileData = array();
		$fileData['bar'] = array('size' => 1000, 'unencrypted_size' => 900, 'encrypted' => 1, 'mtime' => 20, 'mimetype' => 'foo/file');
		$fileData['foo'] = array('size' => 20, 'unencrypted_size' => 16, 'encrypted' => 1, 'mtime' => 25, 'mimetype' => 'foo/file');

		$this->cache->put($file1, $data1);
		$this->cache->put($file2, $fileData['bar']);
		$this->cache->put($file3, $fileData['foo']);

		$content = $this->cache->getFolderContents($file1);
		$this->assertEquals(count($content), 2);
		foreach ($content as $cachedData) {
			$data = $fileData[$cachedData['name']];
			// indirect retrieval swaps  unencrypted_size and size
			$this->assertEquals($data['unencrypted_size'], $cachedData['size']);
		}

		$file4 = 'folder/unkownSize';
		$fileData['unkownSize'] = array('size' => -1, 'mtime' => 25, 'mimetype' => 'foo/file');
		$this->cache->put($file4, $fileData['unkownSize']);

		$this->assertEquals(-1, $this->cache->calculateFolderSize($file1));

		$fileData['unkownSize'] = array('size' => 5, 'mtime' => 25, 'mimetype' => 'foo/file');
		$this->cache->put($file4, $fileData['unkownSize']);

		$this->assertEquals(916, $this->cache->calculateFolderSize($file1));
		// direct cache entry retrieval returns the original values
		$entry = $this->cache->get($file1);
		$this->assertEquals(1025, $entry['size']);
		$this->assertEquals(916, $entry['unencrypted_size']);

		$this->cache->remove($file2);
		$this->cache->remove($file3);
		$this->cache->remove($file4);
		$this->assertEquals(0, $this->cache->calculateFolderSize($file1));

		$this->cache->remove('folder');
		$this->assertFalse($this->cache->inCache('folder/foo'));
		$this->assertFalse($this->cache->inCache('folder/bar'));
	}

	public function testRootFolderSizeForNonHomeStorage() {
		$dir1 = 'knownsize';
		$dir2 = 'unknownsize';
		$fileData = array();
		$fileData[''] = array('size' => -1, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory');
		$fileData[$dir1] = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory');
		$fileData[$dir2] = array('size' => -1, 'mtime' => 25, 'mimetype' => 'httpd/unix-directory');

		$this->cache->put('', $fileData['']);
		$this->cache->put($dir1, $fileData[$dir1]);
		$this->cache->put($dir2, $fileData[$dir2]);

		$this->assertTrue($this->cache->inCache($dir1));
		$this->assertTrue($this->cache->inCache($dir2));

		// check that root size ignored the unknown sizes
		$this->assertEquals(-1, $this->cache->calculateFolderSize(''));

		// clean up
		$this->cache->remove('');
		$this->cache->remove($dir1);
		$this->cache->remove($dir2);

		$this->assertFalse($this->cache->inCache($dir1));
		$this->assertFalse($this->cache->inCache($dir2));
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

		// case insensitive search should match the same files
		$this->assertEquals(2, count($this->cache->search('%Foo%')));
		$this->assertEquals(1, count($this->cache->search('Foo')));
		$this->assertEquals(1, count($this->cache->search('%Folder%')));
		$this->assertEquals(1, count($this->cache->search('Folder%')));

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
		$folderData = array('size' => 100, 'mtime' => 50, 'mimetype' => 'httpd/unix-directory');

		$this->cache->put($file1, $folderData);
		$this->cache->put($file2, $folderData);
		$this->cache->put($file3, $folderData);
		$this->cache->put($file4, $data);
		$this->cache->put($file5, $data);

		/* simulate a second user with a different storage id but the same folder structure */
		$this->cache2->put($file1, $folderData);
		$this->cache2->put($file2, $folderData);
		$this->cache2->put($file3, $folderData);
		$this->cache2->put($file4, $data);
		$this->cache2->put($file5, $data);

		$this->cache->move('folder/foo', 'folder/foobar');

		$this->assertFalse($this->cache->inCache('folder/foo'));
		$this->assertFalse($this->cache->inCache('folder/foo/1'));
		$this->assertFalse($this->cache->inCache('folder/foo/2'));

		$this->assertTrue($this->cache->inCache('folder/bar'));
		$this->assertTrue($this->cache->inCache('folder/foobar'));
		$this->assertTrue($this->cache->inCache('folder/foobar/1'));
		$this->assertTrue($this->cache->inCache('folder/foobar/2'));

		/* the folder structure of the second user must not change! */
		$this->assertTrue($this->cache2->inCache('folder/bar'));
		$this->assertTrue($this->cache2->inCache('folder/foo'));
		$this->assertTrue($this->cache2->inCache('folder/foo/1'));
		$this->assertTrue($this->cache2->inCache('folder/foo/2'));

		$this->assertFalse($this->cache2->inCache('folder/foobar'));
		$this->assertFalse($this->cache2->inCache('folder/foobar/1'));
		$this->assertFalse($this->cache2->inCache('folder/foobar/2'));
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

	function testStorageMTime() {
		$data = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');
		$this->cache->put('foo', $data);
		$cachedData = $this->cache->get('foo');
		$this->assertEquals($data['mtime'], $cachedData['storage_mtime']); //if no storage_mtime is saved, mtime should be used

		$this->cache->put('foo', array('storage_mtime' => 30)); //when setting storage_mtime, mtime is also set
		$cachedData = $this->cache->get('foo');
		$this->assertEquals(30, $cachedData['storage_mtime']);
		$this->assertEquals(30, $cachedData['mtime']);

		$this->cache->put('foo', array('mtime' => 25)); //setting mtime does not change storage_mtime
		$cachedData = $this->cache->get('foo');
		$this->assertEquals(30, $cachedData['storage_mtime']);
		$this->assertEquals(25, $cachedData['mtime']);
	}

	function testLongId() {
		$storage = new LongId(array());
		$cache = $storage->getCache();
		$storageId = $storage->getId();
		$data = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file');
		$id = $cache->put('foo', $data);
		$this->assertEquals(array(md5($storageId), 'foo'), \OC\Files\Cache\Cache::getById($id));
	}

	/**
	 * this test show the bug resulting if we have no normalizer installed
	 */
	public function testWithoutNormalizer() {
		// folder name "Schön" with U+00F6 (normalized)
		$folderWith00F6 = "\x53\x63\x68\xc3\xb6\x6e";

		// folder name "Schön" with U+0308 (un-normalized)
		$folderWith0308 = "\x53\x63\x68\x6f\xcc\x88\x6e";

		/**
		 * @var \OC\Files\Cache\Cache | PHPUnit_Framework_MockObject_MockObject $cacheMock
		 */
		$cacheMock = $this->getMock('\OC\Files\Cache\Cache', array('normalize'), array($this->storage), '', true);

		$cacheMock->expects($this->any())
			->method('normalize')
			->will($this->returnArgument(0));

		$data = array('size' => 100, 'mtime' => 50, 'mimetype' => 'httpd/unix-directory');

		// put root folder
		$this->assertFalse($cacheMock->get('folder'));
		$this->assertGreaterThan(0, $cacheMock->put('folder', $data));

		// put un-normalized folder
		$this->assertFalse($cacheMock->get('folder/' . $folderWith0308));
		$this->assertGreaterThan(0, $cacheMock->put('folder/' . $folderWith0308, $data));

		// get un-normalized folder by name
		$unNormalizedFolderName = $cacheMock->get('folder/' . $folderWith0308);

		// check if database layer normalized the folder name (this should not happen)
		$this->assertEquals($folderWith0308, $unNormalizedFolderName['name']);

		// put normalized folder
		$this->assertFalse($cacheMock->get('folder/' . $folderWith00F6));
		$this->assertGreaterThan(0, $cacheMock->put('folder/' . $folderWith00F6, $data));

		// this is our bug, we have two different hashes with the same name (Schön)
		$this->assertEquals(2, count($cacheMock->getFolderContents('folder')));
	}

	/**
	 * this test shows that there is no bug if we use the normalizer
	 */
	public function testWithNormalizer() {

		if (!class_exists('Patchwork\PHP\Shim\Normalizer')) {
			$this->markTestSkipped('The 3rdparty Normalizer extension is not available.');
			return;
		}

		// folder name "Schön" with U+00F6 (normalized)
		$folderWith00F6 = "\x53\x63\x68\xc3\xb6\x6e";

		// folder name "Schön" with U+0308 (un-normalized)
		$folderWith0308 = "\x53\x63\x68\x6f\xcc\x88\x6e";

		$data = array('size' => 100, 'mtime' => 50, 'mimetype' => 'httpd/unix-directory');

		// put root folder
		$this->assertFalse($this->cache->get('folder'));
		$this->assertGreaterThan(0, $this->cache->put('folder', $data));

		// put un-normalized folder
		$this->assertFalse($this->cache->get('folder/' . $folderWith0308));
		$this->assertGreaterThan(0, $this->cache->put('folder/' . $folderWith0308, $data));

		// get un-normalized folder by name
		$unNormalizedFolderName = $this->cache->get('folder/' . $folderWith0308);

		// check if folder name was normalized
		$this->assertEquals($folderWith00F6, $unNormalizedFolderName['name']);

		// put normalized folder
		$this->assertTrue(is_array($this->cache->get('folder/' . $folderWith00F6)));
		$this->assertGreaterThan(0, $this->cache->put('folder/' . $folderWith00F6, $data));

		// at this point we should have only one folder named "Schön"
		$this->assertEquals(1, count($this->cache->getFolderContents('folder')));
	}

	public function tearDown() {
		if ($this->cache) {
			$this->cache->clear();
		}
	}

	public function setUp() {
		$this->storage = new \OC\Files\Storage\Temporary(array());
		$this->storage2 = new \OC\Files\Storage\Temporary(array());
		$this->cache = new \OC\Files\Cache\Cache($this->storage);
		$this->cache2 = new \OC\Files\Cache\Cache($this->storage2);
	}
}
