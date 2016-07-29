<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
use OCA\DAV\AppInfo\Application;
use OCA\DAV\Command\CreateAddressBook;
use OCA\DAV\Command\CreateCalendar;
use OCA\DAV\Command\SyncBirthdayCalendar;
use OCA\DAV\Command\SyncSystemAddressBook;

$dbConnection = \OC::$server->getDatabaseConnection();
$userManager = OC::$server->getUserManager();
$groupManager = OC::$server->getGroupManager();

$app = new Application();

/** @var Symfony\Component\Console\Application $application */
$application->add(new CreateCalendar($userManager, $groupManager, $dbConnection));
$application->add(new CreateAddressBook($userManager, $app->getContainer()->query('CardDavBackend')));
$application->add(new SyncSystemAddressBook($app->getSyncService()));
$application->add(new SyncBirthdayCalendar($userManager, $app->getContainer()->query('BirthdayService')));
