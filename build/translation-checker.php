<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$directories = [
	__DIR__ . '/../core/l10n',
	__DIR__ . '/../settings/l10n',
];

$apps = new \DirectoryIterator(__DIR__ . '/../apps');
foreach ($apps as $app) {
	if (!file_exists($app->getPathname() . '/l10n')) {
		continue;
	}

	$directories[] = $app->getPathname() . '/l10n';
}

$errors = [];
foreach ($directories as $dir) {
	if (!file_exists($dir)) {
		continue;
	}
	$directory = new \DirectoryIterator($dir);

	foreach ($directory as $file) {
		if ($file->getExtension() !== 'json') {
			continue;
		}

		$content = file_get_contents($file->getPathname());
		$json = json_decode($content, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			echo '[Error] Could not parse: ' . $file->getPathname() . "\n";
			echo '  ' . json_last_error_msg() . "\n";
			$errors[] = $file->getPathname() . "\n" . '  ' . json_last_error_msg() . "\n";
		} else {
			echo '[OK]   ' . $file->getPathname() . "\n";
		}
	}
}

echo "\n\n";
if (count($errors) > 0) {
	echo sprintf('ERROR: There were %d errors:', count($errors)) . "\n";
	echo implode("\n", $errors) . "\n";
	exit(1);
}

echo 'OK: all files parse' . "\n";
exit(0);
