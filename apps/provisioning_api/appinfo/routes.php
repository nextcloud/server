<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Tom Needham <tom@owncloud.com>
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

namespace OCA\Provisioning_API\AppInfo;

use OCP\API;

// Users
$users = new \OCA\Provisioning_API\Users(
	\OC::$server->getUserManager(),
	\OC::$server->getConfig(),
	\OC::$server->getGroupManager(),
	\OC::$server->getUserSession()
);
API::register('get', '/cloud/users', [$users, 'getUsers'], 'provisioning_api', API::SUBADMIN_AUTH);
API::register('post', '/cloud/users', [$users, 'addUser'], 'provisioning_api', API::ADMIN_AUTH);
API::register('get', '/cloud/users/{userid}', [$users, 'getUser'], 'provisioning_api', API::USER_AUTH);
API::register('put', '/cloud/users/{userid}', [$users, 'editUser'], 'provisioning_api', API::USER_AUTH);
API::register('delete', '/cloud/users/{userid}', [$users, 'deleteUser'], 'provisioning_api', API::SUBADMIN_AUTH);
API::register('get', '/cloud/users/{userid}/groups', [$users, 'getUsersGroups'], 'provisioning_api', API::USER_AUTH);
API::register('post', '/cloud/users/{userid}/groups', [$users, 'addToGroup'], 'provisioning_api', API::SUBADMIN_AUTH);
API::register('delete', '/cloud/users/{userid}/groups', [$users, 'removeFromGroup'], 'provisioning_api', API::SUBADMIN_AUTH);
API::register('post', '/cloud/users/{userid}/subadmins', [$users, 'addSubAdmin'], 'provisioning_api', API::ADMIN_AUTH);
API::register('delete', '/cloud/users/{userid}/subadmins', [$users, 'removeSubAdmin'], 'provisioning_api', API::ADMIN_AUTH);
API::register('get', '/cloud/users/{userid}/subadmins', [$users, 'getUserSubAdminGroups'], 'provisioning_api', API::ADMIN_AUTH);

// Groups
$groups = new \OCA\Provisioning_API\Groups(
	\OC::$server->getGroupManager(),
	\OC::$server->getUserSession()
);
API::register('get', '/cloud/groups', [$groups, 'getGroups'], 'provisioning_api', API::SUBADMIN_AUTH);
API::register('post', '/cloud/groups', [$groups, 'addGroup'], 'provisioning_api', API::SUBADMIN_AUTH);
API::register('get', '/cloud/groups/{groupid}', [$groups, 'getGroup'], 'provisioning_api', API::SUBADMIN_AUTH);
API::register('delete', '/cloud/groups/{groupid}', [$groups, 'deleteGroup'], 'provisioning_api', API::ADMIN_AUTH);
API::register('get', '/cloud/groups/{groupid}/subadmins', [$groups, 'getSubAdminsOfGroup'], 'provisioning_api', API::ADMIN_AUTH);

// Apps
$apps = new \OCA\Provisioning_API\Apps(
	\OC::$server->getAppManager()
);
API::register('get', '/cloud/apps', [$apps, 'getApps'], 'provisioning_api', API::ADMIN_AUTH);
API::register('get', '/cloud/apps/{appid}', [$apps, 'getAppInfo'], 'provisioning_api', API::ADMIN_AUTH);
API::register('post', '/cloud/apps/{appid}', [$apps, 'enable'], 'provisioning_api', API::ADMIN_AUTH);
API::register('delete', '/cloud/apps/{appid}', [$apps, 'disable'], 'provisioning_api', API::ADMIN_AUTH);
