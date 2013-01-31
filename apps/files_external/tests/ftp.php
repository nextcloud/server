<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

class FTP extends Storage {
	private $config;

	public function setUp() {
		$id = uniqid();
		$this->config = include('files_external/tests/config.php');
		if ( ! is_array($this->config) or ! isset($this->config['ftp']) or ! $this->config['ftp']['run']) {
			$this->markTestSkipped('FTP backend not configured');
		}
		$this->config['ftp']['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new \OC\Files\Storage\FTP($this->config['ftp']);
	}

	public function tearDown() {
		if ($this->instance) {
			\OCP\Files::rmdirr($this->instance->constructUrl(''));
		}
	}

	public function testConstructUrl(){
		$config = array ( 'host' => 'localhost',
						  'user' => 'ftp',
						  'password' => 'ftp',
						  'root' => '/',
						  'secure' => false );
		$instance = new OC_Filestorage_FTP($config);
		$this->assertEquals('ftp://ftp:ftp@localhost/', $instance->constructUrl(''));

		$config['secure'] = true;
		$instance = new OC_Filestorage_FTP($config);
		$this->assertEquals('ftps://ftp:ftp@localhost/', $instance->constructUrl(''));

		$config['secure'] = 'false';
		$instance = new OC_Filestorage_FTP($config);
		$this->assertEquals('ftp://ftp:ftp@localhost/', $instance->constructUrl(''));

		$config['secure'] = 'true';
		$instance = new OC_Filestorage_FTP($config);
		$this->assertEquals('ftps://ftp:ftp@localhost/', $instance->constructUrl(''));
	}
}
