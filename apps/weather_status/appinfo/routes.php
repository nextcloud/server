<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
