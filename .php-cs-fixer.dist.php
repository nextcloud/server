<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require_once './vendor-bin/cs-fixer/vendor/autoload.php';

use Nextcloud\CodingStandard\Config;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$config = new Config();
$config
	->setParallelConfig(ParallelConfigFactory::detect())
	->getFinder()
	->exclude('config')
	->exclude('3rdparty')
	->exclude('build/stubs')
	->exclude('composer')
	->in(__DIR__);

$ignoredEntries = shell_exec('git status --porcelain --ignored ' . escapeshellarg(__DIR__));
$ignoredEntries = explode("\n", $ignoredEntries);
$ignoredEntries = array_filter($ignoredEntries, static fn (string $line) => str_starts_with($line, '!! '));
$ignoredEntries = array_map(static fn (string $line) => substr($line, 3), $ignoredEntries);
$ignoredEntries = array_values($ignoredEntries);

foreach ($ignoredEntries as $ignoredEntry) {
	if (str_ends_with($ignoredEntry, '/')) {
		$config->getFinder()->exclude($ignoredEntry);
	} else {
		$config->getFinder()->notPath($ignoredEntry);
	}
}

return $config;
