<?php

use OCA\DAV\Command\CreateAddressBook;
use OCA\DAV\Command\CreateCalendar;
use OCA\DAV\Command\SyncSystemAddressBook;

$config = \OC::$server->getConfig();
$dbConnection = \OC::$server->getDatabaseConnection();
$userManager = OC::$server->getUserManager();
$config = \OC::$server->getConfig();
$logger = \OC::$server->getLogger();

/** @var Symfony\Component\Console\Application $application */
$application->add(new CreateAddressBook($userManager, $dbConnection, $config, $logger));
$application->add(new CreateCalendar($userManager, $dbConnection));
$application->add(new SyncSystemAddressBook($userManager, $dbConnection, $config));
