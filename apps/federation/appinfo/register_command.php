<?php

$dbConnection = \OC::$server->getDatabaseConnection();

/** @var Symfony\Component\Console\Application $application */
$application->add(new \OCA\Federation\Command\SyncFederationAddressBooks($dbConnection));
