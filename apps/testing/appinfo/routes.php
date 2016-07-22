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
