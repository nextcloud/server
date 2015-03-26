<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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

$application->add(new OCA\user_ldap\Command\ShowConfig($helper));
$application->add(new OCA\user_ldap\Command\SetConfig());
$application->add(new OCA\user_ldap\Command\TestConfig());
$application->add(new OCA\user_ldap\Command\CreateEmptyConfig($helper));
$application->add(new OCA\user_ldap\Command\DeleteConfig($helper));
$application->add(new OCA\user_ldap\Command\Search($ocConfig));
$application->add(new OCA\user_ldap\Command\ShowRemnants(
	$deletedUsersIndex, \OC::$server->getDateTimeFormatter())
);
$application->add(new OCA\user_ldap\Command\CheckUser(
	$uBackend, $helper, $deletedUsersIndex, $userMapping)
);
