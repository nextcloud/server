<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OCP\App\IAppManager;
use OCP\Server;

define('PHPUNIT_RUN', 1);

$configDir = getenv('CONFIG_DIR');
if ($configDir) {
	define('PHPUNIT_CONFIG_DIR', $configDir);
}

require_once __DIR__ . '/../lib/base.php';
require_once __DIR__ . '/autoload.php';

\OC::$composerAutoloader->addPsr4('Tests\\Core\\', OC::$SERVERROOT . '/tests/Core/', true);

$dontLoadApps = getenv('TEST_DONT_LOAD_APPS');
if (!$dontLoadApps) {
	// load all apps
	$appManager = Server::get(IAppManager::class);
	foreach (new \DirectoryIterator(__DIR__ . '/../apps/') as $file) {
		if ($file->isDot()) {
			continue;
		}
		$appManager->loadApp($file->getFilename());
	}
}

// Recorded on CI by default; set TEST_LOG_HTTP to a path for a manual run.
$logHttp = getenv('TEST_LOG_HTTP') ?: (getenv('CI') ? OC::$SERVERROOT . '/http-requests.log' : '');
if ($logHttp !== '') {
	\Test\HttpRequestLogger::install($logHttp);
}

OC_Hook::clear();

set_include_path(
	get_include_path() . PATH_SEPARATOR
	. '/usr/share/php' . PATH_SEPARATOR
	. __DIR__ . '/..'
);
