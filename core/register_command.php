<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/** @var $application Symfony\Component\Console\Application */
$application->add(new OC\Core\Command\Status);
$application->add(new OC\Core\Command\Db\GenerateChangeScript());
$application->add(new OC\Core\Command\Upgrade());
