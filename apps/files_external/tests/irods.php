<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

class iRODS extends Storage {

	protected $backupGlobals = FALSE;

	private $config;

	public function setUp() {
		$id = uniqid();
		$this->config = include('files_external/tests/config.php');
		if ( ! is_array($this->config) or ! isset($this->config['irods']) or ! $this->config['irods']['run']) {
			$this->markTestSkipped('irods backend not configured');
		}
		$this->config['irods']['root'] .= $id; //make sure we have an new empty folder to work in
		$this->instance = new \OC\Files\Storage\iRODS($this->config['irods']);
	}

	public function tearDown() {
		if ($this->instance) {
			\OCP\Files::rmdirr($this->instance->constructUrl(''));
		}
	}
}
