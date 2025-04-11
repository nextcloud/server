<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
return [
	'routes' => [
		[
			'name' => 'RateLimitTest#userAndAnonProtected',
			'url' => '/userAndAnonProtected',
			'verb' => 'GET',
		],
		[
			'name' => 'RateLimitTest#onlyAnonProtected',
			'url' => '/anonProtected',
			'verb' => 'GET',
		],
	],

	'ocs' => [
		[
			'name' => 'Config#setAppValue',
			'url' => '/api/v1/app/{appid}/{configkey}',
			'verb' => 'POST',
		],
		[
			'name' => 'Config#deleteAppValue',
			'url' => '/api/v1/app/{appid}/{configkey}',
			'verb' => 'DELETE',
		],
		[
			'name' => 'Locking#isLockingEnabled',
			'url' => '/api/v1/lockprovisioning',
			'verb' => 'GET',
		],
		[
			'name' => 'Locking#isLocked',
			'url' => '/api/v1/lockprovisioning/{type}/{user}',
			'verb' => 'GET',
		],
		[
			'name' => 'Locking#acquireLock',
			'url' => '/api/v1/lockprovisioning/{type}/{user}',
			'verb' => 'POST',
		],
		[
			'name' => 'Locking#changeLock',
			'url' => '/api/v1/lockprovisioning/{type}/{user}',
			'verb' => 'PUT',
		],
		[
			'name' => 'Locking#releaseLock',
			'url' => '/api/v1/lockprovisioning/{type}/{user}',
			'verb' => 'DELETE',
		],
		[
			'name' => 'Locking#releaseAll',
			'url' => '/api/v1/lockprovisioning/{type}',
			'verb' => 'DELETE',
			'defaults' => [
				'type' => null
			]
		],
	],
];
