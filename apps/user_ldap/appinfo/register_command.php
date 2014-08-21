<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OCA\user_ldap\lib\Helper;
use OCA\user_ldap\lib\LDAP;
use OCA\user_ldap\User_Proxy;

$application->add(new OCA\user_ldap\Command\ShowConfig());
$application->add(new OCA\user_ldap\Command\SetConfig());
$application->add(new OCA\user_ldap\Command\TestConfig());
$application->add(new OCA\user_ldap\Command\CreateEmptyConfig());
$application->add(new OCA\user_ldap\Command\DeleteConfig());
$application->add(new OCA\user_ldap\Command\Search());
$application->add(new OCA\user_ldap\Command\ShowRemnants());
$helper = new OCA\user_ldap\lib\Helper();
$uBackend = new OCA\user_ldap\User_Proxy(
	$helper->getServerConfigurationPrefixes(true),
	new OCA\user_ldap\lib\LDAP()
);
$application->add(new OCA\user_ldap\Command\CheckUser(
	$uBackend, $helper, \OC::$server->getConfig()
));
