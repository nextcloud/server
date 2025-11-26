<?php

declare(strict_types=1);

use OC\Core\Service\CronService;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/lib/versioncheck.php';

try {
	require_once __DIR__ . '/lib/base.php';

	if (isset($argv[1]) && ($argv[1] === '-h' || $argv[1] === '--help')) {
		echo 'Description:
  Run the background job routine

Usage:
  php -f cron.php -- [-h] [--verbose] [<job-classes>...]

Arguments:
  job-classes                  Optional job class list to only run those jobs
                               Providing a class will ignore the time-sensitivity restriction

Options:
  -h, --help                 Display this help message
  -v, --verbose              Output more information' . PHP_EOL;
		exit(0);
	}

	$cronService = Server::get(CronService::class);
	if (isset($argv[1])) {
		$verbose = $argv[1] === '-v' || $argv[1] === '--verbose';
		$jobClasses = array_slice($argv, $verbose ? 2 : 1);
		$jobClasses = empty($jobClasses) ? null : $jobClasses;

		if ($verbose) {
			$cronService->registerVerboseCallback(function (string $message): void {
				echo $message . PHP_EOL;
			});
		}
	} else {
		$jobClasses = null;
	}

	$cronService->run($jobClasses);
	if (!OC::$CLI) {
		$data = [
			'status' => 'success',
		];
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_HEX_TAG);
	}
	exit(0);
} catch (Throwable $e) {
	Server::get(LoggerInterface::class)->error(
		$e->getMessage(),
		['app' => 'cron', 'exception' => $e]
	);
	if (OC::$CLI) {
		echo $e->getMessage() . PHP_EOL;
	} else {
		$data = [
			'status' => 'error',
			'message' => $e->getMessage(),
		];
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_HEX_TAG);
	}
	exit(1);
}
