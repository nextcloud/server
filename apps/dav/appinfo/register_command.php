<?php

use OCA\DAV\Command\CreateAddressBook;

$dbConnection = \OC::$server->getDatabaseConnection();
$userManager = OC::$server->getUserManager();
/** @var Symfony\Component\Console\Application $application */
$application->add(new CreateAddressBook($userManager, $dbConnection));
