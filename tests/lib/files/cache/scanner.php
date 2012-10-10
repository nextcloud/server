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

	private function fillTestFolders() {
		$textData = "dummy file data\n";
		$imgData = file_get_contents(\OC::$SERVERROOT . '/core/img/logo.png');
		$this->storage->mkdir('folder');
		$this->storage->file_put_contents('foo.txt', $textData);
		$this->storage->file_put_contents('foo.png', $imgData);
		$this->storage->file_put_contents('folder/bar.txt', $textData);
	}

	function testFolder() {
		$this->fillTestFolders();

		$this->scanner->scan('');
		$this->assertEqual($this->cache->inCache(''), true);
		$this->assertEqual($this->cache->inCache('foo.txt'), true);
		$this->assertEqual($this->cache->inCache('foo.png'), true);
		$this->assertEqual($this->cache->inCache('folder'), true);
		$this->assertEqual($this->cache->inCache('folder/bar.txt'), true);

		$cachedDataText = $this->cache->get('foo.txt');
		$cachedDataText2 = $this->cache->get('foo.txt');
		$cachedDataImage = $this->cache->get('foo.png');
		$cachedDataFolder = $this->cache->get('');
		$cachedDataFolder2 = $this->cache->get('folder');

		$this->assertEqual($cachedDataImage['parent'], $cachedDataText['parent']);
		$this->assertEqual($cachedDataFolder['fileid'], $cachedDataImage['parent']);
		$this->assertEqual($cachedDataFolder['size'], $cachedDataImage['size'] + $cachedDataText['size'] + $cachedDataText2['size']);
		$this->assertEqual($cachedDataFolder2['size'], $cachedDataText2['size']);
	}

	function testShallow() {
		$this->fillTestFolders();

		$this->scanner->scan('', \OC\Files\Cache\Scanner::SCAN_SHALLOW);
		$this->assertEqual($this->cache->inCache(''), true);
		$this->assertEqual($this->cache->inCache('foo.txt'), true);
		$this->assertEqual($this->cache->inCache('foo.png'), true);
		$this->assertEqual($this->cache->inCache('folder'), true);
		$this->assertEqual($this->cache->inCache('folder/bar.txt'), false);

		$cachedDataFolder = $this->cache->get('');
		$cachedDataFolder2 = $this->cache->get('folder');

		$this->assertEqual($cachedDataFolder['size'], -1);
		$this->assertEqual($cachedDataFolder2['size'], -1);

		$this->scanner->scan('folder', \OC\Files\Cache\Scanner::SCAN_SHALLOW);

		$cachedDataFolder2 = $this->cache->get('folder');

		$this->assertNotEqual($cachedDataFolder2['size'], -1);
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
