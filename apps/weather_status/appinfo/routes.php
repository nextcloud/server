<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Julien Veyssier
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
		['name' => 'WeatherStatus#setMode', 'url' => '/api/v1/mode', 'verb' => 'PUT'],
		['name' => 'WeatherStatus#usePersonalAddress', 'url' => '/api/v1/use-personal', 'verb' => 'PUT'],
		['name' => 'WeatherStatus#getLocation', 'url' => '/api/v1/location', 'verb' => 'GET'],
		['name' => 'WeatherStatus#setLocation', 'url' => '/api/v1/location', 'verb' => 'PUT'],
		['name' => 'WeatherStatus#getForecast', 'url' => '/api/v1/forecast', 'verb' => 'GET'],
		['name' => 'WeatherStatus#getFavorites', 'url' => '/api/v1/favorites', 'verb' => 'GET'],
		['name' => 'WeatherStatus#setFavorites', 'url' => '/api/v1/favorites', 'verb' => 'PUT'],
	],
];
