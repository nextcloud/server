<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Archive;

use OC\Archive\ZIP;

class ZIPTest extends TestBase {
	protected function getExisting() {
		$dir = \OC::$SERVERROOT . '/tests/data';
		return new ZIP($dir . '/data.zip');
	}

	protected function getNew() {
		return new ZIP(\OC::$server->getTempManager()->getTempBaseDir() . '/newArchive.zip');
	}
}
