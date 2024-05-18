<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
	'routes' => [
		['name' => 'dashboard#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'dashboard#updateLayout', 'url' => '/layout', 'verb' => 'POST'],
		['name' => 'dashboard#updateStatuses', 'url' => '/statuses', 'verb' => 'POST'],
	],
	'ocs' => [
		['name' => 'dashboardApi#getWidgets', 'url' => '/api/v1/widgets', 'verb' => 'GET'],
		['name' => 'dashboardApi#getWidgetItems', 'url' => '/api/v1/widget-items', 'verb' => 'GET'],
		['name' => 'dashboardApi#getWidgetItemsV2', 'url' => '/api/v2/widget-items', 'verb' => 'GET'],
	]
];
