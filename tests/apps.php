<?php
/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

function loadDirectory($path): void {
	if (strpos($path, 'apps/user_ldap/tests/Integration')) {
		return;
	}

	if (! $dh = opendir($path)) {
		return;
	}

	while (($name = readdir($dh)) !== false) {
		if ($name[0] === '.') {
			continue;
		}

		$file = $path . '/' . $name;
		if (is_dir($file)) {
			loadDirectory($file);
		} elseif (str_ends_with($name, '.php')) {
			require_once $file;
		}
	}
}

function getSubclasses($parentClassName): array {
	$classes = [];
	foreach (get_declared_classes() as $className) {
		if (is_subclass_of($className, $parentClassName)) {
			$classes[] = $className;
		}
	}

	return $classes;
}

$apps = OC_App::getEnabledApps();
$appManager = \OCP\Server::get(\OCP\App\IAppManager::class);

foreach ($apps as $app) {
	try {
		$dir = $appManager->getAppPath($app);
		if (is_dir($dir . '/tests')) {
			loadDirectory($dir . '/tests');
		}
	} catch (\OCP\App\AppPathNotFoundException) {
		/* ignore */
	}
}
