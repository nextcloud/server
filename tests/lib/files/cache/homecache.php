<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

class DummyUser extends \OC\User\User {
	/**
	 * @var string $home
	 */
	private $home;

	/**
	 * @var string $uid
	 */
	private $uid;

	public function __construct($uid, $home) {
		$this->home = $home;
		$this->uid = $uid;
	}

	/**
	 * @return string
	 */
	public function getHome() {
		return $this->home;
	}

	/**
	 * @return string
	 */
	public function getUID() {
		return $this->uid;
	}
}

class HomeCache extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OC\Files\Storage\Home $storage
	 */
	private $storage;

	/**
	 * @var \OC\Files\Cache\HomeCache $cache
	 */
	private $cache;

	/**
	 * @var \OC\User\User $user
	 */
	private $user;

	public function setUp() {
		$this->user = new DummyUser('foo', \OC_Helper::tmpFolder());
		$this->storage = new \OC\Files\Storage\Home(array('user' => $this->user));
		$this->cache = $this->storage->getCache();
	}

	/**
	 * Tests that the root folder size calculation ignores the subdirs that have an unknown
	 * size. This makes sure that quota calculation still works as it's based on the root
	 * folder size.
	 */
	public function testRootFolderSizeIgnoresUnknownUpdate() {
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
		$this->assertEquals(1000, $this->cache->calculateFolderSize(''));

		// clean up
		$this->cache->remove('');
		$this->cache->remove($dir1);
		$this->cache->remove($dir2);

		$this->assertFalse($this->cache->inCache($dir1));
		$this->assertFalse($this->cache->inCache($dir2));
	}

	public function testRootFolderSizeIsFilesSize() {
		$dir1 = 'files';
		$afile = 'test.txt';
		$fileData = array();
		$fileData[''] = array('size' => 1500, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory');
		$fileData[$dir1] = array('size' => 1000, 'mtime' => 20, 'mimetype' => 'httpd/unix-directory');
		$fileData[$afile] = array('size' => 500, 'mtime' => 20);

		$this->cache->put('', $fileData['']);
		$this->cache->put($dir1, $fileData[$dir1]);

		$this->assertTrue($this->cache->inCache($dir1));

		// check that root size ignored the unknown sizes
		$data = $this->cache->get('files');
		$this->assertEquals(1000, $data['size']);
		$data = $this->cache->get('');
		$this->assertEquals(1000, $data['size']);

		// clean up
		$this->cache->remove('');
		$this->cache->remove($dir1);

		$this->assertFalse($this->cache->inCache($dir1));
	}
}
