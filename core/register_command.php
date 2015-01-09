<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$repair = new \OC\Repair(\OC\Repair::getRepairSteps());

/** @var $application Symfony\Component\Console\Application */
$application->add(new OC\Core\Command\Status);
$application->add(new OC\Core\Command\Db\GenerateChangeScript());
$application->add(new OC\Core\Command\Db\ConvertType(\OC::$server->getConfig(), new \OC\DB\ConnectionFactory()));
$application->add(new OC\Core\Command\Upgrade(\OC::$server->getConfig()));
$application->add(new OC\Core\Command\Maintenance\SingleUser());
$application->add(new OC\Core\Command\Maintenance\Mode(\OC::$server->getConfig()));
$application->add(new OC\Core\Command\App\Disable());
$application->add(new OC\Core\Command\App\Enable());
$application->add(new OC\Core\Command\App\ListApps());
$application->add(new OC\Core\Command\Maintenance\Repair($repair, \OC::$server->getConfig()));
$application->add(new OC\Core\Command\User\Report());
$application->add(new OC\Core\Command\User\ResetPassword(\OC::$server->getUserManager()));
$application->add(new OC\Core\Command\User\LastSeen());
$application->add(new OC\Core\Command\User\Delete(\OC::$server->getUserManager()));
$application->add(new OC\Core\Command\L10n\CreateJs());

