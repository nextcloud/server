
<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$RUNTIME_NOAPPS = true;
require_once 'lib/base.php';

// Don't do anything if ownCloud has not been installed yet
if (!OC_Config::getValue('installed', false)) {
	echo "Console can only be used once ownCloud has been installed" . PHP_EOL;
	exit(0);
}

if (!OC::$CLI) {
	echo "This script can be run from the command line only" . PHP_EOL;
	exit(0);
}

$self = basename($argv[0]);
if ($argc <= 1) {
	$argv[1] = "help";
}

$command = $argv[1];
array_shift($argv);

switch ($command) {
	case 'files:scan':
		require_once 'apps/files/console/scan.php';
		break;
	case 'status':
		require_once 'status.php';
		break;
	case 'help':
		echo "Usage:" . PHP_EOL;
		echo " " . $self . " <command>" . PHP_EOL;
		echo PHP_EOL;
		echo "Available commands:" . PHP_EOL;
		echo " files:scan -> rescan filesystem" .PHP_EOL;
		echo " status -> show some status information" .PHP_EOL;
		echo " help -> show this help screen" .PHP_EOL;
		break;
	default:
		echo "Unknown command '$command'" . PHP_EOL;
		echo "For available commands type ". $self . " help" . PHP_EOL;
		break;
}
