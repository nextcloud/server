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

	protected function setUp() {
		parent::setUp();

		$id = $this->getUniqueID();
		$config = include('files_external/tests/config.webdav.php');
		if ( ! is_array($config) or !$config['run']) {
			$this->markTestSkipped('WebDAV backend not configured');
		}
		if (isset($config['wait'])) {
			$this->waitDelay = $config['wait'];
		}
		$config['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new \OC\Files\Storage\DAV($config);
		$this->instance->mkdir('/');
	}

	protected function tearDown() {
		if ($this->instance) {
			$this->instance->rmdir('/');
		}

		parent::tearDown();
	}
}
