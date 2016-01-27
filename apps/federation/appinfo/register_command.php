<?php

$app = new \OCA\Federation\AppInfo\Application();
$syncService = $app->getSyncService();

/** @var Symfony\Component\Console\Application $application */
$application->add(new \OCA\Federation\Command\SyncFederationAddressBooks($syncService));
