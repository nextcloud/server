<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

class DAV extends Storage {

	private $config;

	public function setUp() {
		$id = uniqid();
		$this->config = include('files_external/tests/config.php');
		if ( ! is_array($this->config) or ! isset($this->config['webdav']) or ! $this->config['webdav']['run']) {
			$this->markTestSkipped('WebDAV backend not configured');
		}
		$this->config['webdav']['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new \OC\Files\Storage\DAV($this->config['webdav']);
	}

	public function tearDown() {
		if ($this->instance) {
			$this->instance->rmdir('/');
		}
	}
}
