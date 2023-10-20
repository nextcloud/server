<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

function loadDirectory($path): void {
	if (strpos($path, 'integration')) {
		return;
	}

	if (strpos($path, 'Integration')) {
		return;
	}

	if (! $dh = opendir($path)) {
		return;
	}

	while ($name = readdir($dh)) {
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
	closedir($dh);
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

foreach ($apps as $app) {
	$dir = OC_App::getAppPath($app);
	if (is_dir($dir . '/tests')) {
		loadDirectory($dir . '/tests');
	}
}
