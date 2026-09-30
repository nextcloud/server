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
require_once __DIR__ . '/extra-apps.php';

function enableApp($app) {
	$installer = Server::get(Installer::class);
	$appManager = Server::get(IAppManager::class);

	$installer->installApp($app);
	$appManager->enableApp($app);
	echo "Enabled application {$app}\n";
}

$extraAppIds = getExtraAppIds();

// Enabled before the default apps/ apps: installing an app after dozens of
// others have already run their Application::register() in this same PHP
// process can make OC\AppFramework\Bootstrap\Coordinator re-process already
// finalized apps (e.g. a RuntimeException re-registering a share source
// type), so the app(s) under test go first while that state is still empty.
foreach ($extraAppIds as $appId) {
	enableApp($appId);
}

foreach (new \DirectoryIterator(__DIR__ . '/../apps/') as $file) {
	if ($file->isDot()) {
		continue;
	}
	// Apps checked out from their own git repository into the default apps/
	// directory are managed separately and must not be enabled here.
	// Apps requested via EXTRA_APPS_PATHS were already enabled above.
	if (!in_array($file->getFilename(), $extraAppIds, true) && !file_exists($file->getPathname() . '/.git')) {
		enableApp($file->getFilename());
	}
}
