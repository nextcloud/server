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
	exit(0);
}

if (OC::$CLI) {
	if ($argc > 1 && $argv[1] === 'files:scan') {
		require_once 'apps/files/console/scan.php';
	}
}
else
{
	echo "This script can be run from the command line only\n";
}
