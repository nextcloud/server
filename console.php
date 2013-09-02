
<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OC\Core\Command\GreetCommand;
use Symfony\Component\Console\Application;

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

$defaults = new OC_Defaults;
$application = new Application($defaults->getName(), \OC_Util::getVersionString());
$application->add(new OC\Core\Command\Status);
$application->add(new OCA\Files\Command\Scan(OC_User::getManager()));
$application->run();
