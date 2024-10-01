<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Archive;

use OC\Archive\TAR;

class TARTest extends TestBase {
	protected function getExisting() {
		$dir = \OC::$SERVERROOT . '/tests/data';
		return new TAR($dir . '/data.tar.gz');
	}

	protected function getNew() {
		return new TAR(\OC::$server->getTempManager()->getTemporaryFile('.tar.gz'));
	}
}
