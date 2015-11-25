<?php

use OCA\DAV\Command\CreateAddressBook;
use OCA\DAV\Command\CreateCalendar;

$dbConnection = \OC::$server->getDatabaseConnection();
$userManager = OC::$server->getUserManager();
$config = \OC::$server->getConfig();

/** @var Symfony\Component\Console\Application $application */
$application->add(new CreateAddressBook($userManager, $dbConnection, $config));
$application->add(new CreateCalendar($userManager, $dbConnection));
