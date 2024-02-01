<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
return [
	'ocs' => [
		// Routes for querying statuses
		['name' => 'Statuses#findAll', 'url' => '/api/v1/statuses', 'verb' => 'GET'],
		['name' => 'Statuses#find', 'url' => '/api/v1/statuses/{userId}', 'verb' => 'GET'],
		// Routes for manipulating your own status
		['name' => 'UserStatus#getStatus', 'url' => '/api/v1/user_status', 'verb' => 'GET'],
		['name' => 'UserStatus#setStatus', 'url' => '/api/v1/user_status/status', 'verb' => 'PUT'],
		['name' => 'UserStatus#setPredefinedMessage', 'url' => '/api/v1/user_status/message/predefined', 'verb' => 'PUT'],
		['name' => 'UserStatus#setCustomMessage', 'url' => '/api/v1/user_status/message/custom', 'verb' => 'PUT'],
		['name' => 'UserStatus#clearMessage', 'url' => '/api/v1/user_status/message', 'verb' => 'DELETE'],
		['name' => 'UserStatus#revertStatus', 'url' => '/api/v1/user_status/revert/{messageId}', 'verb' => 'DELETE'],
		// Routes for listing default routes
		['name' => 'PredefinedStatus#findAll', 'url' => '/api/v1/predefined_statuses/', 'verb' => 'GET'],
		// Route for doing heartbeats
		['name' => 'Heartbeat#heartbeat', 'url' => '/api/v1/heartbeat', 'verb' => 'PUT'],
	],
];
