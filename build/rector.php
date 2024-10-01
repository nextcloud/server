<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use Rector\Config\RectorConfig;

$nextcloudDir = dirname(__DIR__);

$config = RectorConfig::configure()
	->withPaths([
		$nextcloudDir . '/apps',
		// $nextcloudDir . '/config',
		// $nextcloudDir . '/core',
		// $nextcloudDir . '/lib',
		// $nextcloudDir . '/ocs',
		// $nextcloudDir . '/ocs-provider',
		// $nextcloudDir . '/tests',
		// $nextcloudDir . '/themes',
	])
	->withSkip([
		$nextcloudDir . '/apps/*/3rdparty/*',
		$nextcloudDir . '/apps/*/build/stubs/*',
		$nextcloudDir . '/apps/*/composer/*',
		$nextcloudDir . '/apps/*/config/*',
	])
	// uncomment to reach your current PHP version
	// ->withPhpSets()
	->withTypeCoverageLevel(0);


$ignoredEntries = shell_exec('git status --porcelain --ignored ' . escapeshellarg($nextcloudDir));
$ignoredEntries = explode("\n", $ignoredEntries);
$ignoredEntries = array_filter($ignoredEntries, static fn (string $line) => str_starts_with($line, '!! '));
$ignoredEntries = array_map(static fn (string $line) => substr($line, 3), $ignoredEntries);
$ignoredEntries = array_values($ignoredEntries);

foreach ($ignoredEntries as $ignoredEntry) {
	if (str_ends_with($ignoredEntry, '/')) {
		$config->withSkip([$ignoredEntry . '*']);
	} else {
		$config->withSkip([$ignoredEntry . '/*']);
	}
}

return $config;
