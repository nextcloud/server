<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage\Wrapper;

class JailTest extends \Test\Files\Storage\Storage {

	/**
	 * @var \OC\Files\Storage\Temporary
	 */
	private $sourceStorage;

	public function setUp() {
		parent::setUp();
		$this->sourceStorage = new \OC\Files\Storage\Temporary(array());
		$this->sourceStorage->mkdir('foo');
		$this->instance = new \OC\Files\Storage\Wrapper\Jail(array(
			'storage' => $this->sourceStorage,
			'root' => 'foo'
		));
	}

	public function tearDown() {
		// test that nothing outside our jail is touched
		$contents = array();
		$dh = $this->sourceStorage->opendir('');
		while ($file = readdir($dh)) {
			if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
				$contents[] = $file;
			}
		}
		$this->assertEquals(array('foo'), $contents);
		$this->sourceStorage->cleanUp();
		parent::tearDown();
	}

	public function testMkDirRooted() {
		$this->instance->mkdir('bar');
		$this->assertTrue($this->sourceStorage->is_dir('foo/bar'));
	}

	public function testFilePutContentsRooted() {
		$this->instance->file_put_contents('bar', 'asd');
		$this->assertEquals('asd', $this->sourceStorage->file_get_contents('foo/bar'));
	}
}
