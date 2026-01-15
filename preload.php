<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

define('PHP_PRELOAD', 1);
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/lib/base.php';

function scanFolder(string $folder): void {
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($folder),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	// names to skip
	$skip = [
		'update.php',
		'index.php',
		'cron.php',
		'console.php',
		'command.php',
		'routes.php',
		'authpicker.php',
		'grant.php',
		'/templates',
		'composer/',
		'tests/',
	];

	// require all
	foreach ($files as $file) {
		// check skipping
		foreach ($skip as $s) {
			if (strpos($file->getPathname(), $s) !== false) {
				continue 2;
			}
		}

		if (substr($file->getFilename(), -4) === '.php') {
			require_once $file;
		}
	}
}

scanFolder(__DIR__ . '/lib');
scanFolder(__DIR__ . '/core');
// scanFolder(__DIR__ . '/apps');
