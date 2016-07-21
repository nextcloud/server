<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Testing\AppInfo;

use OCA\Testing\Config;
use OCA\Testing\Locking\Provisioning;
use OCP\API;

$config = new Config(
	\OC::$server->getConfig(),
	\OC::$server->getRequest()
);

API::register(
	'post',
	'/apps/testing/api/v1/app/{appid}/{configkey}',
	[$config, 'setAppValue'],
	'testing',
	API::ADMIN_AUTH
);

API::register(
	'delete',
	'/apps/testing/api/v1/app/{appid}/{configkey}',
	[$config, 'deleteAppValue'],
	'testing',
	API::ADMIN_AUTH
);

$locking = new Provisioning(
	\OC::$server->getLockingProvider(),
	\OC::$server->getDatabaseConnection(),
	\OC::$server->getConfig(),
	\OC::$server->getRequest()
);
API::register('get', '/apps/testing/api/v1/lockprovisioning', [$locking, 'isLockingEnabled'], 'files_lockprovisioning', API::ADMIN_AUTH);
API::register('get', '/apps/testing/api/v1/lockprovisioning/{type}/{user}', [$locking, 'isLocked'], 'files_lockprovisioning', API::ADMIN_AUTH);
API::register('post', '/apps/testing/api/v1/lockprovisioning/{type}/{user}', [$locking, 'acquireLock'], 'files_lockprovisioning', API::ADMIN_AUTH);
API::register('put', '/apps/testing/api/v1/lockprovisioning/{type}/{user}', [$locking, 'changeLock'], 'files_lockprovisioning', API::ADMIN_AUTH);
API::register('delete', '/apps/testing/api/v1/lockprovisioning/{type}/{user}', [$locking, 'releaseLock'], 'files_lockprovisioning', API::ADMIN_AUTH);
API::register('delete', '/apps/testing/api/v1/lockprovisioning/{type}', [$locking, 'releaseAll'], 'files_lockprovisioning', API::ADMIN_AUTH);
API::register('delete', '/apps/testing/api/v1/lockprovisioning', [$locking, 'releaseAll'], 'files_lockprovisioning', API::ADMIN_AUTH);
