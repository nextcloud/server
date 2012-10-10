<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$config=include('files_external/tests/config.php');
if(!is_array($config) or !isset($config['dropbox']) or !$config['dropbox']['run']) {
	abstract class Test_Filestorage_Dropbox extends Test_FileStorage{}
	return;
}else{
	class Test_Filestorage_Dropbox extends Test_FileStorage {
		private $config;

		public function setUp() {
			$id=uniqid();
			$this->config=include('files_external/tests/config.php');
			$this->config['dropbox']['root'].='/'.$id;//make sure we have an new empty folder to work in
			$this->instance=new OC_Filestorage_Dropbox($this->config['dropbox']);
		}

		public function tearDown() {
			$this->instance->unlink('/');
		}
	}
}
