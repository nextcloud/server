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

	public function setUp() {
		$this->tmpDir = \OC_Helper::tmpFolder();
		$storage = new \OC\Files\Storage\Local(array('datadir' => $this->tmpDir));
		$this->instance = new \OC\Files\Storage\Wrapper\Quota(array('storage' => $storage, 'quota' => 10000000));
	}

	public function tearDown() {
		\OC_Helper::rmdirr($this->tmpDir);
	}

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

	public function testFWriteNotEnoughSpace() {
		$instance = $this->getLimitedStorage(9);
		$stream = $instance->fopen('foo', 'w+');
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		$this->assertEquals(3, fwrite($stream, 'qwerty'));
		fclose($stream);
		$this->assertEquals('foobarqwe', $instance->file_get_contents('foo'));
	}

	public function testReturnRegularStreamOnRead(){
		$instance = $this->getLimitedStorage(9);

		// create test file first
		$stream = $instance->fopen('foo', 'w+');
		fwrite($stream, 'blablacontent');
		fclose($stream);

		$stream = $instance->fopen('foo', 'r');
		$meta = stream_get_meta_data($stream);
		$this->assertEquals('plainfile', $meta['wrapper_type']);
		fclose($stream);
	}

	public function testReturnQuotaStreamOnWrite(){
		$instance = $this->getLimitedStorage(9);
		$stream = $instance->fopen('foo', 'w+');
		$meta = stream_get_meta_data($stream);
		$this->assertEquals('user-space', $meta['wrapper_type']);
		fclose($stream);
	}
}
