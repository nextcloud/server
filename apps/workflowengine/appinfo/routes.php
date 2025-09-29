<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
return [
	'routes' => [
		['name' => 'requestTime#getTimezones', 'url' => '/timezones', 'verb' => 'GET'],
	],
	'ocs-resources' => [
		'global_workflows' => ['url' => '/api/v1/workflows/global'],
		'user_workflows' => ['url' => '/api/v1/workflows/user'],
	],
];
