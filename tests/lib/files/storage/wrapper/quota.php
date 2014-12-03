<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage\Wrapper;

//ensure the constants are loaded
\OC::$loader->load('\OC\Files\Filesystem');

class Quota extends \Test\Files\Storage\Storage {
	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	protected function setUp() {
		parent::setUp();

		$this->tmpDir = \OC_Helper::tmpFolder();
		$storage = new \OC\Files\Storage\Local(array('datadir' => $this->tmpDir));
		$this->instance = new \OC\Files\Storage\Wrapper\Quota(array('storage' => $storage, 'quota' => 10000000));
	}

	protected function tearDown() {
		\OC_Helper::rmdirr($this->tmpDir);
		parent::tearDown();
	}

	/**
	 * @param integer $limit
	 */
	protected function getLimitedStorage($limit) {
		$storage = new \OC\Files\Storage\Local(array('datadir' => $this->tmpDir));
		$storage->getScanner()->scan('');
		return new \OC\Files\Storage\Wrapper\Quota(array('storage' => $storage, 'quota' => $limit));
	}

	public function testFilePutContentsNotEnoughSpace() {
		$instance = $this->getLimitedStorage(3);
		$this->assertFalse($instance->file_put_contents('foo', 'foobar'));
	}

	public function testCopyNotEnoughSpace() {
		$instance = $this->getLimitedStorage(9);
		$this->assertEquals(6, $instance->file_put_contents('foo', 'foobar'));
		$instance->getScanner()->scan('');
		$this->assertFalse($instance->copy('foo', 'bar'));
	}

	public function testFreeSpace() {
		$instance = $this->getLimitedStorage(9);
		$this->assertEquals(9, $instance->free_space(''));
	}

	public function testFreeSpaceWithUsedSpace() {
		$instance = $this->getLimitedStorage(9);
		$instance->getCache()->put(
			'', array('size' => 3, 'unencrypted_size' => 0)
		);
		$this->assertEquals(6, $instance->free_space(''));
	}

	public function testFreeSpaceWithUnknownDiskSpace() {
		$storage = $this->getMock(
			'\OC\Files\Storage\Local',
			array('free_space'),
			array(array('datadir' => $this->tmpDir))
		);
		$storage->expects($this->any())
			->method('free_space')
			->will($this->returnValue(-2));
		$storage->getScanner()->scan('');

		$instance = new \OC\Files\Storage\Wrapper\Quota(array('storage' => $storage, 'quota' => 9));
		$instance->getCache()->put(
			'', array('size' => 3, 'unencrypted_size' => 0)
		);
		$this->assertEquals(6, $instance->free_space(''));
	}

	public function testFreeSpaceWithUsedSpaceAndEncryption() {
		$instance = $this->getLimitedStorage(9);
		$instance->getCache()->put(
			'', array('size' => 7, 'unencrypted_size' => 3)
		);
		$this->assertEquals(6, $instance->free_space(''));
	}

	public function testFWriteNotEnoughSpace() {
		$instance = $this->getLimitedStorage(9);
		$stream = $instance->fopen('foo', 'w+');
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		$this->assertEquals(3, fwrite($stream, 'qwerty'));
		fclose($stream);
		$this->assertEquals('foobarqwe', $instance->file_get_contents('foo'));
	}

	public function testReturnFalseWhenFopenFailed() {
		$failStorage = $this->getMock(
			'\OC\Files\Storage\Local',
			array('fopen'),
			array(array('datadir' => $this->tmpDir)));
		$failStorage->expects($this->any())
			->method('fopen')
			->will($this->returnValue(false));

		$instance = new \OC\Files\Storage\Wrapper\Quota(array('storage' => $failStorage, 'quota' => 1000));

		$this->assertFalse($instance->fopen('failedfopen', 'r'));
	}

	public function testReturnRegularStreamOnRead() {
		$instance = $this->getLimitedStorage(9);

		// create test file first
		$stream = $instance->fopen('foo', 'w+');
		fwrite($stream, 'blablacontent');
		fclose($stream);

		$stream = $instance->fopen('foo', 'r');
		$meta = stream_get_meta_data($stream);
		$this->assertEquals('plainfile', $meta['wrapper_type']);
		fclose($stream);

		$stream = $instance->fopen('foo', 'rb');
		$meta = stream_get_meta_data($stream);
		$this->assertEquals('plainfile', $meta['wrapper_type']);
		fclose($stream);
	}

	public function testReturnQuotaStreamOnWrite() {
		$instance = $this->getLimitedStorage(9);
		$stream = $instance->fopen('foo', 'w+');
		$meta = stream_get_meta_data($stream);
		$this->assertEquals('user-space', $meta['wrapper_type']);
		fclose($stream);
	}

	public function testSpaceRoot() {
		$storage = $this->getMockBuilder('\OC\Files\Storage\Local')->disableOriginalConstructor()->getMock();
		$cache = $this->getMockBuilder('\OC\Files\Cache\Cache')->disableOriginalConstructor()->getMock();
		$storage->expects($this->once())
			->method('getCache')
			->will($this->returnValue($cache));
		$storage->expects($this->once())
			->method('free_space')
			->will($this->returnValue(2048));
		$cache->expects($this->once())
			->method('get')
			->with('files')
			->will($this->returnValue(array('size' => 50)));

		$instance = new \OC\Files\Storage\Wrapper\Quota(array('storage' => $storage, 'quota' => 1024, 'root' => 'files'));

		$this->assertEquals(1024 - 50, $instance->free_space(''));
	}

	public function testInstanceOfStorageWrapper() {
		$this->assertTrue($this->instance->instanceOfStorage('\OC\Files\Storage\Local'));
		$this->assertTrue($this->instance->instanceOfStorage('\OC\Files\Storage\Wrapper\Wrapper'));
		$this->assertTrue($this->instance->instanceOfStorage('\OC\Files\Storage\Wrapper\Quota'));
	}
}
