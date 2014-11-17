<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Archive_TAR extends Test_Archive {
	public function setUp() {
		if (OC_Util::runningOnWindows()) {
			$this->markTestSkipped('[Windows] tar archives are not supported on Windows');
		}
		parent::setUp();
	}

	protected function getExisting() {
		$dir = OC::$SERVERROOT . '/tests/data';
		return new OC_Archive_TAR($dir . '/data.tar.gz');
	}

	protected function getNew() {
		return new OC_Archive_TAR(OCP\Files::tmpFile('.tar.gz'));
	}
}
