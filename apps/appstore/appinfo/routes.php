<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
return [
	'routes' => [
		['name' => 'AppSettings#getAppDiscoverJSON', 'url' => '/settings/api/apps/discover', 'verb' => 'GET', 'root' => ''],
		['name' => 'AppSettings#getAppDiscoverMedia', 'url' => '/settings/api/apps/media', 'verb' => 'GET', 'root' => ''],
		['name' => 'AppSettings#listCategories', 'url' => '/settings/apps/categories', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#viewApps', 'url' => '/settings/apps', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#listApps', 'url' => '/settings/apps/list', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#enableApp', 'url' => '/settings/apps/enable/{appId}', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#enableApp', 'url' => '/settings/apps/enable/{appId}', 'verb' => 'POST' , 'root' => ''],
		['name' => 'AppSettings#enableApps', 'url' => '/settings/apps/enable', 'verb' => 'POST' , 'root' => ''],
		['name' => 'AppSettings#disableApp', 'url' => '/settings/apps/disable/{appId}', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#disableApps', 'url' => '/settings/apps/disable', 'verb' => 'POST' , 'root' => ''],
		['name' => 'AppSettings#updateApp', 'url' => '/settings/apps/update/{appId}', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#uninstallApp', 'url' => '/settings/apps/uninstall/{appId}', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#viewApps', 'url' => '/settings/apps/{category}', 'verb' => 'GET', 'defaults' => ['category' => ''] , 'root' => ''],
		['name' => 'AppSettings#viewApps', 'url' => '/settings/apps/{category}/{id}', 'verb' => 'GET', 'defaults' => ['category' => '', 'id' => ''] , 'root' => ''],
		['name' => 'AppSettings#force', 'url' => '/settings/apps/force', 'verb' => 'POST' , 'root' => ''],
	],
];
