<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * This file is used to run the upgrade process of an application in occ upgrade command,
 *  after downloading the application code. This needs to be done in its own process to make
 *  sure it uses up-to-date code and autoloaders.
 */

require_once __DIR__ . '/lib/versioncheck.php';

try {
	require_once __DIR__ . '/lib/base.php';

	if (!\OC::$CLI) {
		throw new \Exception('This file can only be used in CLI');
	}

	if (isset($argv[1])) {
		$appid = $argv[1];
	} else {
		throw new \Exception('No appid');
	}

	$config = Server::get(IConfig::class);

	// Don't do anything if Nextcloud has not been installed
	if (!$config->getSystemValueBool('installed', false)) {
		// TODO: check if this may be needed before installation is complete
		exit(0);
	}

	$appManager = Server::get(IAppManager::class);

	$logger = Server::get(LoggerInterface::class);

	// set to run indefinitely if needed
	if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
		@set_time_limit(0);
	}

	$success = $appManager->upgradeApp($appid);

	if ($success) {
		exit();
	} else {
		exit(1);
	}
} catch (Throwable $ex) {
	Server::get(LoggerInterface::class)->error(
		$ex->getMessage(),
		['app' => 'appupgrade.php', 'exception' => $ex]
	);
	echo $ex . PHP_EOL;
	exit(1);
}
