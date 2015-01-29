<?php
/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OCA\Files_Encryption\Command\MigrateKeys;

$userManager = OC::$server->getUserManager();
$application->add(new MigrateKeys($userManager));
