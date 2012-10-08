<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

$config=include('files_external/tests/config.php');
if(!is_array($config) or !isset($config['dropbox']) or !$config['dropbox']['run']) {
	abstract class Dropbox extends Storage{}
	return;
}else{
	class Dropbox extends Storage {
		private $config;

		public function setUp() {
			$id=uniqid();
			$this->config=include('files_external/tests/config.php');
			$this->config['dropbox']['root'].='/'.$id;//make sure we have an new empty folder to work in
			$this->instance=new \OC\Files\Storage\Dropbox($this->config['dropbox']);
		}

		public function tearDown() {
			$this->instance->unlink('/');
		}
	}
}
