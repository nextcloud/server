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

use OCA\Encryption\Command\MigrateKeys;
use Symfony\Component\Console\Helper\QuestionHelper;

$userManager = OC::$server->getUserManager();
$view = new \OC\Files\View();
$config = \OC::$server->getConfig();
$l = \OC::$server->getL10N('encryption');
$userSession = \OC::$server->getUserSession();
$connection = \OC::$server->getDatabaseConnection();
$logger = \OC::$server->getLogger();
$questionHelper = new QuestionHelper();
$crypt = new \OCA\Encryption\Crypto\Crypt($logger, $userSession, $config, $l);
$util = new \OCA\Encryption\Util($view, $crypt, $logger, $userSession, $config, $userManager);

$application->add(new MigrateKeys($userManager, $view, $connection, $config, $logger));
$application->add(new \OCA\Encryption\Command\EnableMasterKey($util, $config, $questionHelper));
