<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

$nextcloudDir = dirname(__DIR__);

return (require 'rector-shared.php')
	->withPaths([
		$nextcloudDir . '/apps',
		$nextcloudDir . '/core',
		$nextcloudDir . '/ocs',
		$nextcloudDir . '/ocs-provider',
		$nextcloudDir . '/console.php',
		$nextcloudDir . '/cron.php',
		$nextcloudDir . '/index.php',
		$nextcloudDir . '/occ',
		$nextcloudDir . '/public.php',
		$nextcloudDir . '/remote.php',
		$nextcloudDir . '/status.php',
		$nextcloudDir . '/version.php',
		$nextcloudDir . '/lib/private/Share20/ProviderFactory.php',
		$nextcloudDir . '/lib/private/Template',
		$nextcloudDir . '/tests',
		// $nextcloudDir . '/config',
		// $nextcloudDir . '/lib',
		// $nextcloudDir . '/themes',
	])
	->withTypeCoverageLevel(0);
