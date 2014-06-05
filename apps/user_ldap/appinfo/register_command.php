<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$application->add(new OCA\user_ldap\Command\ShowConfig());
$application->add(new OCA\user_ldap\Command\SetConfig());
$application->add(new OCA\user_ldap\Command\TestConfig());
