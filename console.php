<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use Symfony\Component\Console\Application;

try {
	require_once 'lib/base.php';

	// Don't do anything if ownCloud has not been installed yet
	if (!\OC::$server->getConfig()->getSystemValue('installed', false)) {
		echo "Console can only be used once ownCloud has been installed" . PHP_EOL;
		exit(0);
	}

	if (!OC::$CLI) {
		echo "This script can be run from the command line only" . PHP_EOL;
		exit(0);
	}

	// only load apps if no update is due,
	// else only core commands will be available
	if (!\OCP\Util::needUpgrade()) {
		// load all apps to get all api routes properly setup
		OC_App::loadApps();
	}

	$defaults = new OC_Defaults;
	$application = new Application($defaults->getName(), \OC_Util::getVersionString());
	require_once 'core/register_command.php';
	if (!\OCP\Util::needUpgrade()) {
		foreach(OC_App::getAllApps() as $app) {
			$file = OC_App::getAppPath($app).'/appinfo/register_command.php';
			if(file_exists($file)) {
				require $file;
			}
		}
	}
	$application->run();
} catch (Exception $ex) {
	echo "An unhandled exception has been thrown:" . PHP_EOL;
	echo $ex;
}
