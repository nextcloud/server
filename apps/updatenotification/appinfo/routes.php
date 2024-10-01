<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
return [
	'routes' => [
		['name' => 'Admin#createCredentials', 'url' => '/credentials', 'verb' => 'GET'],
		['name' => 'Admin#setChannel', 'url' => '/channel', 'verb' => 'POST'],
		// Fallback app changelog information for mobile clients
		['name' => 'Changelog#showChangelog', 'url' => '/changelog/{app}', 'verb' => 'GET'],
	],
	'ocs' => [
		['name' => 'API#getAppList', 'url' => '/api/{apiVersion}/applist/{newVersion}', 'verb' => 'GET', 'requirements' => ['apiVersion' => '(v1)']],
		['name' => 'API#getAppChangelogEntry', 'url' => '/api/{apiVersion}/changelog/{appId}', 'verb' => 'GET', 'requirements' => ['apiVersion' => '(v1)']],
	],
];
