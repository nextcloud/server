<?php
/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OCA\Encryption\Command\MigrateKeys;

$userManager = OC::$server->getUserManager();
$view = new \OC\Files\View();
$config = \OC::$server->getConfig();
$connection = \OC::$server->getDatabaseConnection();
$application->add(new MigrateKeys($userManager, $view, $connection, $config));
