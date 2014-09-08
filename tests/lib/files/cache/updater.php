<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

use OC\Files\Filesystem;
use OC\Files\Storage\Temporary;
use OC\Files\View;

class Updater extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OC\Files\Storage\Storage
	 */
	protected $storage;

	/**
	 * @var \OC\Files\Cache\Cache
	 */
	protected $cache;

	/**
	 * @var \OC\Files\View
	 */
	protected $view;

	/**
	 * @var \OC\Files\Cache\Updater
	 */
	protected $updater;

	public function setUp() {
		$this->storage = new Temporary(array());
		Filesystem::clearMounts();
		Filesystem::mount($this->storage, array(), '/');
		$this->view = new View('');
		$this->updater = new \OC\Files\Cache\Updater($this->view);
		$this->cache = $this->storage->getCache();
	}

	public function testNewFile() {
		$this->storage->file_put_contents('foo.txt', 'bar');
		$this->assertFalse($this->cache->inCache('foo.txt'));

		$this->updater->update('/foo.txt');

		$this->assertTrue($this->cache->inCache('foo.txt'));
		$cached = $this->cache->get('foo.txt');
		$this->assertEquals(3, $cached['size']);
		$this->assertEquals('text/plain', $cached['mimetype']);
	}

	public function testUpdatedFile() {
		$this->view->file_put_contents('/foo.txt', 'bar');
		$cached = $this->cache->get('foo.txt');
		$this->assertEquals(3, $cached['size']);
		$this->assertEquals('text/plain', $cached['mimetype']);

		$this->storage->file_put_contents('foo.txt', 'qwerty');

		$cached = $this->cache->get('foo.txt');
		$this->assertEquals(3, $cached['size']);

		$this->updater->update('/foo.txt');

		$cached = $this->cache->get('foo.txt');
		$this->assertEquals(6, $cached['size']);
	}

	public function testParentSize() {
		$this->storage->getScanner()->scan('');

		$parentCached = $this->cache->get('');
		$this->assertEquals(0, $parentCached['size']);

		$this->storage->file_put_contents('foo.txt', 'bar');

		$parentCached = $this->cache->get('');
		$this->assertEquals(0, $parentCached['size']);

		$this->updater->update('foo.txt');

		$parentCached = $this->cache->get('');
		$this->assertEquals(3, $parentCached['size']);

		$this->storage->file_put_contents('foo.txt', 'qwerty');

		$parentCached = $this->cache->get('');
		$this->assertEquals(3, $parentCached['size']);

		$this->updater->update('foo.txt');

		$parentCached = $this->cache->get('');
		$this->assertEquals(6, $parentCached['size']);

		$this->storage->unlink('foo.txt');

		$parentCached = $this->cache->get('');
		$this->assertEquals(6, $parentCached['size']);

		$this->updater->remove('foo.txt');

		$parentCached = $this->cache->get('');
		$this->assertEquals(0, $parentCached['size']);
	}

	public function testMove() {
		$this->storage->file_put_contents('foo.txt', 'qwerty');
		$this->updater->update('foo.txt');

		$this->assertTrue($this->cache->inCache('foo.txt'));
		$this->assertFalse($this->cache->inCache('bar.txt'));
		$cached = $this->cache->get('foo.txt');

		$this->storage->rename('foo.txt', 'bar.txt');

		$this->assertTrue($this->cache->inCache('foo.txt'));
		$this->assertFalse($this->cache->inCache('bar.txt'));

		$this->updater->rename('foo.txt', 'bar.txt');

		$this->assertFalse($this->cache->inCache('foo.txt'));
		$this->assertTrue($this->cache->inCache('bar.txt'));

		$cachedTarget = $this->cache->get('bar.txt');
		$this->assertEquals($cached['etag'], $cachedTarget['etag']);
		$this->assertEquals($cached['mtime'], $cachedTarget['mtime']);
		$this->assertEquals($cached['size'], $cachedTarget['size']);
		$this->assertEquals($cached['fileid'], $cachedTarget['fileid']);
	}
}
