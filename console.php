<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christian Kampka <christian@kampka.net>
 * @author Jost Baron <Jost.Baron@gmx.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philippe Le Brouster <plb@nebkha.net>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
use Symfony\Component\Console\Output\ConsoleOutput;

define('OC_CONSOLE', 1);

// Show warning if a PHP version below 5.4.0 is used, this has to happen here
// because base.php will already use 5.4 syntax.
if (version_compare(PHP_VERSION, '5.4.0') === -1) {
	echo 'This version of ownCloud requires at least PHP 5.4.0'.PHP_EOL;
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.'.PHP_EOL;
	return;
}

try {
	require_once 'lib/base.php';

	// set to run indefinitely if needed
	set_time_limit(0);

	if (!OC::$CLI) {
		echo "This script can be run from the command line only" . PHP_EOL;
		exit(0);
	}

	if (!OC_Util::runningOnWindows())  {
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
			exit(0);
		}
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

	$application = new Application(\OC::$server->getConfig());
	$application->loadCommands(new ConsoleOutput());
	$application->run();
} catch (Exception $ex) {
	echo "An unhandled exception has been thrown:" . PHP_EOL;
	echo $ex;
	exit(1);
}
