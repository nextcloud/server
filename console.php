<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christian Kampka <christian@kampka.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

define('OC_CONSOLE', 1);

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
		$configUser = posix_getpwuid(fileowner(OC::$SERVERROOT . '/config/config.php'));
		if ($user['name'] !== $configUser['name']) {
			echo "Console has to be executed with the same user as the web server is operated" . PHP_EOL;
			echo "Current user: " . $user['name'] . PHP_EOL;
			echo "Web server user: " . $configUser['name'] . PHP_EOL;
			exit(0);
		}
	}

	$defaults = new OC_Defaults;
	$application = new Application($defaults->getName(), \OC_Util::getVersionString());
	require_once 'core/register_command.php';
	if (\OC::$server->getConfig()->getSystemValue('installed', false)) {
		if (!\OCP\Util::needUpgrade()) {
			OC_App::loadApps();
			foreach (OC_App::getAllApps() as $app) {
				$file = OC_App::getAppPath($app) . '/appinfo/register_command.php';
				if (file_exists($file)) {
					require $file;
				}
			}
		} else {
			echo "ownCloud or one of the apps require upgrade - only a limited number of commands are available" . PHP_EOL;
		}
	} else {
		echo "ownCloud is not installed - only a limited number of commands are available" . PHP_EOL;
	}
	$input = new ArgvInput();
	if ($input->getFirstArgument() !== 'check') {
		$errors = \OC_Util::checkServer(\OC::$server->getConfig());
		if (!empty($errors)) {
			foreach ($errors as $error) {
				echo $error['error'] . "\n";
				echo $error['hint'] . "\n\n";
			}
			exit(1);
		}
	}

	$application->run();
} catch (Exception $ex) {
	echo "An unhandled exception has been thrown:" . PHP_EOL;
	echo $ex;
	exit(1);
}
