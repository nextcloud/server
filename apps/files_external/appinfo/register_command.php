<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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


use OCA\Files_External\Command\ListCommand;
use OCA\Files_External\Command\Config;
use OCA\Files_External\Command\Option;
use OCA\Files_External\Command\Import;
use OCA\Files_External\Command\Export;

$userManager = OC::$server->getUserManager();
$userSession = OC::$server->getUserSession();

$app = \OC_Mount_Config::$app;

$globalStorageService = $app->getContainer()->query('\OCA\Files_external\Service\GlobalStoragesService');
$userStorageService = $app->getContainer()->query('\OCA\Files_external\Service\UserStoragesService');
$importLegacyStorageService = $app->getContainer()->query('\OCA\Files_external\Service\ImportLegacyStoragesService');
$backendService = $app->getContainer()->query('OCA\Files_External\Service\BackendService');

/** @var Symfony\Component\Console\Application $application */
$application->add(new ListCommand($globalStorageService, $userStorageService, $userSession, $userManager));
$application->add(new Config($globalStorageService));
$application->add(new Option($globalStorageService));
$application->add(new Import($globalStorageService, $userStorageService, $userSession, $userManager, $importLegacyStorageService, $backendService));
$application->add(new Export($globalStorageService, $userStorageService, $userSession, $userManager));
