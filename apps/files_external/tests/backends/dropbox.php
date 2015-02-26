<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

class Dropbox extends Storage {
	private $config;

	protected function setUp() {
		parent::setUp();

		$id = $this->getUniqueID();
		$this->config = include('files_external/tests/config.php');
		if ( ! is_array($this->config) or ! isset($this->config['dropbox']) or ! $this->config['dropbox']['run']) {
			$this->markTestSkipped('Dropbox backend not configured');
		}
		$this->config['dropbox']['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new \OC\Files\Storage\Dropbox($this->config['dropbox']);
	}

	protected function tearDown() {
		if ($this->instance) {
			$this->instance->unlink('/');
		}

		parent::tearDown();
	}

	public function directoryProvider() {
		// doesn't support leading/trailing spaces
		return array(array('folder'));
	}

	public function testDropboxTouchReturnValue() {
		$this->assertFalse($this->instance->file_exists('foo'));

		// true because succeeded
		$this->assertTrue($this->instance->touch('foo'));
		$this->assertTrue($this->instance->file_exists('foo'));

		// false because not supported
		$this->assertFalse($this->instance->touch('foo'));
	}
}
