<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
define('PHPUNIT_RUN', 1);

$configDir = getenv('CONFIG_DIR');
if ($configDir) {
	define('PHPUNIT_CONFIG_DIR', $configDir);
}

require_once __DIR__ . '/../lib/base.php';
require_once __DIR__ . '/autoload.php';

\OC::$composerAutoloader->addPsr4('Tests\\', OC::$SERVERROOT . '/tests/', true);

// load all apps
foreach (new \DirectoryIterator(__DIR__ . '/../apps/') as $file) {
	if ($file->isDot()) {
		continue;
	}
	\OC_App::loadApp($file->getFilename());
}

OC_Hook::clear();

set_include_path(
	get_include_path() . PATH_SEPARATOR
	. '/usr/share/php' . PATH_SEPARATOR
	. __DIR__ . '/..'
);
