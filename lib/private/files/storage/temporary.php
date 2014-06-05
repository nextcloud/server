<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

/**
 * local storage backend in temporary folder for testing purpose
 */
class Temporary extends Local{
	public function __construct($arguments) {
		parent::__construct(array('datadir' => \OC_Helper::tmpFolder()));
	}

	public function cleanUp() {
		\OC_Helper::rmdirr($this->datadir);
	}

	public function __destruct() {
		parent::__destruct();
		$this->cleanUp();
	}

	public function getDataDir() {
		return $this->datadir;
	}
}
