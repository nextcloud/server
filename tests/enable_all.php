<?php
/**
 * SPDX-FileCopyrightText: 2016-2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require_once __DIR__ . '/../lib/base.php';

function enableApp($app) {
	try {
		(new \OC_App())->enable($app);
	} catch (Exception $e) {
		echo $e;
	}
}

foreach (new \DirectoryIterator(__DIR__ . '/../apps/') as $file) {
	if ($file->isDot()) {
		continue;
	}
	if (!file_exists($file->getPathname() . '/.git')) {
		enableApp($file->getFilename());
	}
}
