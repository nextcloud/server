<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use Symfony\Component\Console\Application;

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
	$application->run();
} catch (Exception $ex) {
	echo "An unhandled exception has been thrown:" . PHP_EOL;
	echo $ex;
}
