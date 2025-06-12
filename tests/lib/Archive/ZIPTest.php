<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Archive;

use OC\Archive\ZIP;
use OCP\ITempManager;
use OCP\Server;

class ZIPTest extends TestBase {
	protected function getExisting() {
		$dir = \OC::$SERVERROOT . '/tests/data';
		return new ZIP($dir . '/data.zip');
	}

	protected function getNew() {
		$newZip = Server::get(ITempManager::class)->getTempBaseDir() . '/newArchive.zip';
		if (file_exists($newZip)) {
			unlink($newZip);
		}
		return new ZIP($newZip);
	}
}
