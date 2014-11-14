<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

class SMB extends Storage {

	private $config;

	protected function setUp() {
		parent::setUp();

		$id = $this->getUniqueID();
		$this->config = include('files_external/tests/config.php');
		if (!is_array($this->config) or !isset($this->config['smb']) or !$this->config['smb']['run']) {
			$this->markTestSkipped('Samba backend not configured');
		}
		$this->config['smb']['root'] .= $id; //make sure we have an new empty folder to work in
		$this->instance = new \OC\Files\Storage\SMB($this->config['smb']);
		$this->instance->mkdir('/');
	}

	protected function tearDown() {
		if ($this->instance) {
			\OCP\Files::rmdirr($this->instance->constructUrl(''));
		}

		parent::tearDown();
	}

	public function directoryProvider() {
		// doesn't support leading/trailing spaces
		return array(array('folder'));
	}

	public function testRenameWithSpaces() {
		$this->instance->mkdir('with spaces');
		$result = $this->instance->rename('with spaces', 'foo bar');
		$this->assertTrue($result);
		$this->assertTrue($this->instance->is_dir('foo bar'));
	}
}
