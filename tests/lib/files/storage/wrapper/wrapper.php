<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage\Wrapper;

class Wrapper extends \Test\Files\Storage\Storage {
	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	public function setUp() {
		$this->tmpDir = \OC_Helper::tmpFolder();
		$storage = new \OC\Files\Storage\Local(array('datadir' => $this->tmpDir));
		$this->instance = new \OC\Files\Storage\Wrapper\Wrapper(array('storage' => $storage));
	}

	public function tearDown() {
		\OC_Helper::rmdirr($this->tmpDir);
	}

	public function testInstanceOfStorageWrapper() {
		$this->assertTrue($this->instance->instanceOfStorage('\OC\Files\Storage\Local'));
		$this->assertTrue($this->instance->instanceOfStorage('\OC\Files\Storage\Wrapper\Wrapper'));
	}
}
