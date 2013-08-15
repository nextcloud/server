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

if ($argc <= 1) {
	echo "Usage:" . PHP_EOL;
	echo " " . basename($argv[0]) . " <command>" . PHP_EOL;
	exit(0);
}

$command = $argv[1];
array_shift($argv);

if ($command === 'files:scan') {
	require_once 'apps/files/console/scan.php';
} else {
	echo "Unknown command '$command'" . PHP_EOL;
}
