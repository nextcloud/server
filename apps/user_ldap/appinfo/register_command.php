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
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\lib\User\DeletedUsersIndex;

$dbConnection = \OC::$server->getDatabaseConnection();
$userMapping = new UserMapping($dbConnection);
$helper = new Helper();
$ocConfig = \OC::$server->getConfig();
$uBackend = new User_Proxy(
	$helper->getServerConfigurationPrefixes(true),
	new LDAP(),
	$ocConfig
);
$deletedUsersIndex = new DeletedUsersIndex(
	$ocConfig, $dbConnection, $userMapping
);

$application->add(new OCA\user_ldap\Command\ShowConfig());
$application->add(new OCA\user_ldap\Command\SetConfig());
$application->add(new OCA\user_ldap\Command\TestConfig());
$application->add(new OCA\user_ldap\Command\CreateEmptyConfig());
$application->add(new OCA\user_ldap\Command\DeleteConfig());
$application->add(new OCA\user_ldap\Command\Search($ocConfig));
$application->add(new OCA\user_ldap\Command\ShowRemnants(
	$deletedUsersIndex, \OC::$server->getDateTimeFormatter())
);
$application->add(new OCA\user_ldap\Command\CheckUser(
	$uBackend, $helper, $deletedUsersIndex, $userMapping)
);
