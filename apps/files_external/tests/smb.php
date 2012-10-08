<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

$config=include('apps/files_external/tests/config.php');


if(!is_array($config) or !isset($config['smb']) or !$config['smb']['run']) {
	abstract class SMB extends Storage{}
	return;
}else{
	class SMB extends Storage {
		private $config;

		public function setUp() {
			$id=uniqid();
			$this->config=include('apps/files_external/tests/config.php');
			$this->config['smb']['root'].=$id;//make sure we have an new empty folder to work in
			$this->instance=new \OC\Files\Storage\SMB($this->config['smb']);
		}

		public function tearDown() {
			\OCP\Files::rmdirr($this->instance->constructUrl(''));
		}
	}
}
