<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

class Scanner extends \UnitTestCase {
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

	function testFile() {
		$data = "dummy file data\n";
		$this->storage->file_put_contents('foo.txt', $data);
		$this->scanner->scanFile('foo.txt');

		$this->assertEqual($this->cache->inCache('foo.txt'), true);
		$cachedData = $this->cache->get('foo.txt');
		$this->assertEqual($cachedData['size'], strlen($data));
		$this->assertEqual($cachedData['mimetype'], 'text/plain');
		$this->assertNotEqual($cachedData['parent'], -1); //parent folders should be scanned automatically

		$data = file_get_contents(\OC::$SERVERROOT . '/core/img/logo.png');
		$this->storage->file_put_contents('foo.png', $data);
		$this->scanner->scanFile('foo.png');

		$this->assertEqual($this->cache->inCache('foo.png'), true);
		$cachedData = $this->cache->get('foo.png');
		$this->assertEqual($cachedData['size'], strlen($data));
		$this->assertEqual($cachedData['mimetype'], 'image/png');
	}

	function testFolder() {
		$textData = "dummy file data\n";
		$imgData = file_get_contents(\OC::$SERVERROOT . '/core/img/logo.png');
		$this->storage->file_put_contents('foo.txt', $textData);
		$this->storage->file_put_contents('foo.png', $imgData);

		$this->scanner->scan('');
		$this->assertEqual($this->cache->inCache('foo.txt'), true);
		$this->assertEqual($this->cache->inCache('foo.png'), true);
	}

	function setUp() {
		$this->storage = new \OC\Files\Storage\Temporary(array());
		$this->scanner = new \OC\Files\Cache\Scanner($this->storage);
		$this->cache = new \OC\Files\Cache\Cache($this->storage);
	}

	function tearDown() {
		$this->cache->clear();
	}
}
