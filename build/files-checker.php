<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$expectedFiles = [
	'.',
	'..',
	'.devcontainer',
	'.editorconfig',
	'.eslintignore',
	'.eslintrc.js',
	'.git',
	'.git-blame-ignore-revs',
	'.gitattributes',
	'.github',
	'.gitignore',
	'.gitmodules',
	'.htaccess',
	'.idea',
	'.jshintrc',
	'.mailmap',
	'.npmignore',
	'.php-cs-fixer.dist.php',
	'.pre-commit-config.yaml',
	'.reuse',
	'.tag',
	'.tx',
	'.user.ini',
	'__mocks__',
	'__tests__',
	'3rdparty',
	'AUTHORS',
	'CHANGELOG.md',
	'CODE_OF_CONDUCT.md',
	'COPYING',
	'COPYING-README',
	'DESIGN.md',
	'Makefile',
	'README.md',
	'SECURITY.md',
	'apps',
	'autotest-checkers.sh',
	'autotest-external.sh',
	'autotest.sh',
	'babel.config.js',
	'build',
	'codecov.yml',
	'composer.json',
	'composer.lock',
	'config',
	'console.php',
	'contribute',
	'core',
	'cron.php',
	'custom.d.ts',
	'cypress.config.ts',
	'cypress.d.ts',
	'cypress',
	'dist',
	'index.html',
	'index.php',
	'lib',
	'LICENSES',
	'occ',
	'ocs',
	'ocs-provider',
	'openapi.json',
	'package-lock.json',
	'package.json',
	'psalm-ncu.xml',
	'psalm-ocp.xml',
	'psalm.xml',
	'public.php',
	'remote.php',
	'resources',
	'robots.txt',
	'status.php',
	'stylelint.config.js',
	'tests',
	'themes',
	'tsconfig.json',
	'vendor-bin',
	'version.php',
	'vitest.config.ts',
	'webpack.common.js',
	'webpack.config.js',
	'webpack.modules.js',
];
$actualFiles = [];

$files = new \DirectoryIterator(__DIR__ . '/..');
foreach ($files as $file) {
	$actualFiles[] = $file->getFilename();
}

$additionalFiles = array_diff($actualFiles, $expectedFiles);
$missingFiles = array_diff($expectedFiles, $actualFiles);

$failed = false;
if (count($additionalFiles) > 0) {
	echo sprintf('ERROR: There were %d additional files:', count($additionalFiles)) . PHP_EOL;
	echo implode(PHP_EOL, $additionalFiles) . PHP_EOL;
	$failed = true;
}
if (count($missingFiles) > 0) {
	echo sprintf('ERROR: There were %d missing files:', count($missingFiles)) . PHP_EOL;
	echo implode(PHP_EOL, $missingFiles) . PHP_EOL;
	$failed = true;
}

if ($failed) {
	echo 'ERROR: Please remove or add those files again or inform the release team about those now files to be included or excluded from the release tar ball.' . PHP_EOL;
	exit(1);
}

echo 'OK: all expected files are present and no additional files are there.' . PHP_EOL;
exit(0);
