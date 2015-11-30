<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache\Wrapper;

use Test\Files\Cache\Cache;

/**
 * Class CacheJail
 *
 * @group DB
 *
 * @package Test\Files\Cache\Wrapper
 */
class CacheJail extends Cache {
	/**
	 * @var \OC\Files\Cache\Cache $sourceCache
	 */
	protected $sourceCache;

	public function setUp() {
		parent::setUp();
		$this->storage->mkdir('foo');
		$this->sourceCache = $this->cache;
		$this->cache = new \OC\Files\Cache\Wrapper\CacheJail($this->sourceCache, 'foo');
	}

	function testSearchOutsideJail() {
		$file1 = 'foo/foobar';
		$file2 = 'folder/foobar';
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'foo/folder');

		$this->sourceCache->put($file1, $data1);
		$this->sourceCache->put($file2, $data1);

		$this->assertCount(2, $this->sourceCache->search('%foobar'));

		$result = $this->cache->search('%foobar%');
		$this->assertCount(1, $result);
		$this->assertEquals('foobar', $result[0]['path']);
	}

	function testClearKeepEntriesOutsideJail() {
		$file1 = 'foo/foobar';
		$file2 = 'foo/foobar/asd';
		$file3 = 'folder/foobar';
		$data1 = array('size' => 100, 'mtime' => 50, 'mimetype' => 'httpd/unix-directory');

		$this->sourceCache->put('foo', $data1);
		$this->sourceCache->put($file1, $data1);
		$this->sourceCache->put($file2, $data1);
		$this->sourceCache->put($file3, $data1);

		$this->cache->clear();

		$this->assertFalse($this->cache->inCache('foobar'));
		$this->assertTrue($this->sourceCache->inCache('folder/foobar'));
	}

	function testGetById() {
		//not supported
		$this->assertTrue(true);
	}

	function testGetIncomplete() {
		//not supported
		$this->assertTrue(true);
	}
}
