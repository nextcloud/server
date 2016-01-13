<?php

$dbConnection = \OC::$server->getDatabaseConnection();
$l10n = \OC::$server->getL10N('federation');
$dbHandler = new \OCA\Federation\DbHandler($dbConnection, $l10n);

/** @var Symfony\Component\Console\Application $application */
$application->add(new \OCA\Federation\Command\SyncFederationAddressBooks($dbHandler));
