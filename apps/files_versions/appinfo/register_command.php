<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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


use OCA\Files_Versions\AppInfo\Application;
use OCA\Files_Versions\Command\CleanUp;
use OCA\Files_Versions\Command\ExpireVersions;

$app = new Application();
$expiration = $app->getContainer()->query('Expiration');
$userManager = OC::$server->getUserManager();
$rootFolder = \OC::$server->getRootFolder();
/** @var Symfony\Component\Console\Application $application */
$application->add(new CleanUp($rootFolder, $userManager));
$application->add(new ExpireVersions($userManager, $expiration));
