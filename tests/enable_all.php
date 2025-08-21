<?php

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OC\Installer;
use OCP\App\IAppManager;
use OCP\Server;

require_once __DIR__ . '/../lib/base.php';

function enableApp($app) {
	$installer = Server::get(Installer::class);
	$appManager = Server::get(IAppManager::class);

	$installer->installApp($app);
	$appManager->enableApp($app);
	echo "Enabled application {$app}\n";
}

foreach (new \DirectoryIterator(__DIR__ . '/../apps/') as $file) {
	if ($file->isDot()) {
		continue;
	}
	if (!file_exists($file->getPathname() . '/.git')) {
		enableApp($file->getFilename());
	}
}
