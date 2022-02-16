<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

use OCA\User_LDAP\Command\UpdateUUID;
use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\UserPluginManager;
use OCP\IUserManager;

$dbConnection = \OC::$server->getDatabaseConnection();
$userMapping = new UserMapping($dbConnection);
$groupMapping = \OC::$server->get(GroupMapping::class);
$helper = new Helper(\OC::$server->getConfig());
$ocConfig = \OC::$server->getConfig();
$activeConfigurationPrefixes = $helper->getServerConfigurationPrefixes(true);
$uBackend = new User_Proxy(
	$activeConfigurationPrefixes,
	new LDAP(),
	$ocConfig,
	\OC::$server->getNotificationManager(),
	\OC::$server->getUserSession(),
	\OC::$server->query(UserPluginManager::class)
);
$groupBackend = new Group_Proxy($activeConfigurationPrefixes, new LDAP(), \OC::$server->get(\OCA\User_LDAP\GroupPluginManager::class));
$deletedUsersIndex = new DeletedUsersIndex(
	$ocConfig, $dbConnection, $userMapping
);
$logger = \OC::$server->get(\Psr\Log\LoggerInterface::class);

$application->add(new OCA\User_LDAP\Command\ShowConfig($helper));
$application->add(new OCA\User_LDAP\Command\SetConfig());
$application->add(new OCA\User_LDAP\Command\TestConfig());
$application->add(new OCA\User_LDAP\Command\CreateEmptyConfig($helper));
$application->add(new OCA\User_LDAP\Command\DeleteConfig($helper));
$application->add(new OCA\User_LDAP\Command\ResetUser(
	$deletedUsersIndex,
	\OC::$server->get(IUserManager::class),
	\OC::$server->get(UserPluginManager::class)
));
$application->add(new OCA\User_LDAP\Command\Search($ocConfig));
$application->add(new OCA\User_LDAP\Command\ShowRemnants(
	$deletedUsersIndex, \OC::$server->getDateTimeFormatter())
);
$application->add(new OCA\User_LDAP\Command\CheckUser(
	$uBackend, $helper, $deletedUsersIndex, $userMapping)
);
$application->add(new UpdateUUID($userMapping, $groupMapping, $uBackend, $groupBackend, $logger));
