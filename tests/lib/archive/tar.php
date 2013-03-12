<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once 'archive.php';

if (!OC_Util::runningOnWindows()) {
class Test_Archive_TAR extends Test_Archive {
	protected function getExisting() {
		$dir = OC::$SERVERROOT . '/tests/data';
		return new OC_Archive_TAR($dir . '/data.tar.gz');
	}

	protected function getNew() {
		return new OC_Archive_TAR(OCP\Files::tmpFile('.tar.gz'));
	}
}
}
