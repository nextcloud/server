<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$directories = [
	__DIR__ . '/../apps',
	__DIR__ . '/../core'
];

$errors = [];
foreach ($directories as $dir) {
	$it = new \RecursiveDirectoryIterator($dir);

	foreach (new RecursiveIteratorIterator($it) as $file) {
		if (($file->getExtension() === 'map') || $file->isDir()) {
			continue;
		}
		$content = file_get_contents($file->getPathname());
		$matches = preg_grep('/[^A-Za-z][tn]\([\'"][^\'"]*?[\'"],( ?)[\'"][^\'"]*?(\.\.\.)[^\'"]*?[\'"]/sU', explode(PHP_EOL, $content));

		foreach ($matches as $ln => $match) {
			if (file_exists($file->getPathname() . '.map')) {
				$errors[] = sprintf('At line %d in file %s (file may be transpiled)', $ln, $file);
			} else {
				$errors[] = sprintf('At line %d in file %s', $ln, $file);
			}
		}
	}
}

echo PHP_EOL . PHP_EOL;
if (count($errors) > 0) {
	if (count($errors) > 1) {
		echo sprintf('ERROR: There are %d uses of triple dot instead of ellipsis:', count($errors)) . PHP_EOL;
	} else {
		echo 'ERROR: There is 1 use of triple dot instead of ellipsis:' . PHP_EOL;
	}
	echo implode(PHP_EOL, $errors) . PHP_EOL;
	exit(1);
}

echo 'OK: all good! No use of triple dot instead of ellipsis found.' . PHP_EOL;
exit(0);
