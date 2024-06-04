<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
require_once __DIR__ . '/lib/versioncheck.php';

use OC\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

define('OC_CONSOLE', 1);

function exceptionHandler($exception) {
	echo "An unhandled exception has been thrown:" . PHP_EOL;
	echo $exception;
	exit(1);
}
try {
	require_once __DIR__ . '/lib/base.php';

	// set to run indefinitely if needed
	if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
		@set_time_limit(0);
	}

	if (!OC::$CLI) {
		echo "This script can be run from the command line only" . PHP_EOL;
		exit(1);
	}

	$config = \OCP\Server::get(\OCP\IConfig::class);
	set_exception_handler('exceptionHandler');

	if (!function_exists('posix_getuid')) {
		echo "The posix extensions are required - see https://www.php.net/manual/en/book.posix.php" . PHP_EOL;
		exit(1);
	}

	$user = posix_getuid();
	$configUser = fileowner(OC::$configDir . 'config.php');
	if ($user !== $configUser) {
		echo "Console has to be executed with the user that owns the file config/config.php" . PHP_EOL;
		echo "Current user id: " . $user . PHP_EOL;
		echo "Owner id of config.php: " . $configUser . PHP_EOL;
		echo "Try adding 'sudo -u #" . $configUser . "' to the beginning of the command (without the single quotes)" . PHP_EOL;
		echo "If running with 'docker exec' try adding the option '-u " . $configUser . "' to the docker command (without the single quotes)" . PHP_EOL;
		exit(1);
	}

	$oldWorkingDir = getcwd();
	if ($oldWorkingDir === false) {
		echo "This script can be run from the Nextcloud root directory only." . PHP_EOL;
		echo "Can't determine current working dir - the script will continue to work but be aware of the above fact." . PHP_EOL;
	} elseif ($oldWorkingDir !== __DIR__ && !chdir(__DIR__)) {
		echo "This script can be run from the Nextcloud root directory only." . PHP_EOL;
		echo "Can't change to Nextcloud root directory." . PHP_EOL;
		exit(1);
	}

	if (!(function_exists('pcntl_signal') && function_exists('pcntl_signal_dispatch')) && !in_array('--no-warnings', $argv)) {
		echo "The process control (PCNTL) extensions are required in case you want to interrupt long running commands - see https://www.php.net/manual/en/book.pcntl.php" . PHP_EOL;
		echo "Additionally the function 'pcntl_signal' and 'pcntl_signal_dispatch' need to be enabled in your php.ini." . PHP_EOL;
	}

	$application = \OCP\Server::get(Application::class);
	$application->loadCommands(new ArgvInput(), new ConsoleOutput());
	$application->run();
} catch (Exception $ex) {
	exceptionHandler($ex);
} catch (Error $ex) {
	exceptionHandler($ex);
}
