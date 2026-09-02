<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Nextcloud\Rector\Rector\RemoveSetMaxResultsFromJoinQueryRector;
use Rector\Config\RectorConfig;

require_once __DIR__ . '/rector/RemoveSetMaxResultsFromJoinQueryRector.php';

$nextcloudDir = dirname(__DIR__);

return RectorConfig::configure()
	->withPaths([
		$nextcloudDir . '/apps',
		$nextcloudDir . '/core',
		$nextcloudDir . '/lib',
		$nextcloudDir . '/ocs',
	])
	->withSkip([
		$nextcloudDir . '/apps/*/3rdparty/*',
		$nextcloudDir . '/apps/*/vendor/*',
		$nextcloudDir . '/lib/public/*',
	])
	->withRules([
		RemoveSetMaxResultsFromJoinQueryRector::class,
	]);




