<?php
/**
 * Copyright (c) 2013 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

class SMBFunctions extends \Test\TestCase {

	protected function setUp() {
		parent::setUp();

		// dummy config
		$this->config = array(
			'run'=>false,
			'user'=>'test',
			'password'=>'testpassword',
			'host'=>'smbhost',
			'share'=>'/sharename',
			'root'=>'/rootdir/',
		);

		$this->instance = new \OC\Files\Storage\SMB($this->config);
	}

	public function testGetId() {
		$this->assertEquals('smb::test@smbhost//sharename//rootdir/', $this->instance->getId());
	}

	public function testConstructUrl() {
		$this->assertEquals("smb://test:testpassword@smbhost/sharename/rootdir/abc", $this->instance->constructUrl('/abc'));
		$this->assertEquals("smb://test:testpassword@smbhost/sharename/rootdir/abc", $this->instance->constructUrl('/abc/'));
		$this->assertEquals("smb://test:testpassword@smbhost/sharename/rootdir/abc%2F", $this->instance->constructUrl('/abc/.'));
		$this->assertEquals("smb://test:testpassword@smbhost/sharename/rootdir/abc%2Fdef", $this->instance->constructUrl('/abc/def'));
	}
}
