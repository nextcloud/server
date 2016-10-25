<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

use OC\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

define('OC_CONSOLE', 1);

// Show warning if a PHP version below 5.4.0 is used, this has to happen here
// because base.php will already use 5.4 syntax.
if (version_compare(PHP_VERSION, '5.4.0') === -1) {
	echo 'This version of Nextcloud requires at least PHP 5.4.0'.PHP_EOL;
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.'.PHP_EOL;
	return;
}

// Show warning if PHP 7.1 is used as Nextcloud is not compatible with PHP 7.1 for now
// @see https://github.com/nextcloud/docker-ci/issues/10
if (version_compare(PHP_VERSION, '7.1.0') !== -1) {
	echo 'This version of Nextcloud is not compatible with PHP 7.1.<br/>';
	echo 'You are currently running ' . PHP_VERSION . '.';
	return;
}

function exceptionHandler($exception) {
	echo "An unhandled exception has been thrown:" . PHP_EOL;
	echo $exception;
	exit(1);
}
try {
	require_once __DIR__ . '/lib/base.php';

	// set to run indefinitely if needed
	set_time_limit(0);

	if (!OC::$CLI) {
		echo "This script can be run from the command line only" . PHP_EOL;
		exit(0);
	}

	set_exception_handler('exceptionHandler');

	if (!function_exists('posix_getuid')) {
		echo "The posix extensions are required - see http://php.net/manual/en/book.posix.php" . PHP_EOL;
		exit(0);
	}
	$user = posix_getpwuid(posix_getuid());
	$configUser = posix_getpwuid(fileowner(OC::$configDir . 'config.php'));
	if ($user['name'] !== $configUser['name']) {
		echo "Console has to be executed with the user that owns the file config/config.php" . PHP_EOL;
		echo "Current user: " . $user['name'] . PHP_EOL;
		echo "Owner of config.php: " . $configUser['name'] . PHP_EOL;
		echo "Try adding 'sudo -u " . $configUser['name'] . " ' to the beginning of the command (without the single quotes)" . PHP_EOL;  
		exit(0);
	}

	$oldWorkingDir = getcwd();
	if ($oldWorkingDir === false) {
		echo "This script can be run from the ownCloud root directory only." . PHP_EOL;
		echo "Can't determine current working dir - the script will continue to work but be aware of the above fact." . PHP_EOL;
	} else if ($oldWorkingDir !== __DIR__ && !chdir(__DIR__)) {
		echo "This script can be run from the ownCloud root directory only." . PHP_EOL;
		echo "Can't change to ownCloud root directory." . PHP_EOL;
		exit(1);
	}

	if (!function_exists('pcntl_signal') && !in_array('--no-warnings', $argv)) {
		echo "The process control (PCNTL) extensions are required in case you want to interrupt long running commands - see http://php.net/manual/en/book.pcntl.php" . PHP_EOL;
	}

	$application = new Application(\OC::$server->getConfig(), \OC::$server->getEventDispatcher(), \OC::$server->getRequest());
	$application->loadCommands(new ArgvInput(), new ConsoleOutput());
	$application->run();
} catch (Exception $ex) {
	exceptionHandler($ex);
} catch (Error $ex) {
	exceptionHandler($ex);
}
