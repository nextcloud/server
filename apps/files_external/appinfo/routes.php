<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
return [
	'resources' => [
		'global_storages' => ['url' => '/globalstorages'],
		'user_storages' => ['url' => '/userstorages'],
		'user_global_storages' => ['url' => '/userglobalstorages'],
	],
	'routes' => [
		[
			'name' => 'Ajax#getApplicableEntities',
			'url' => '/ajax/applicable',
			'verb' => 'GET',
		],
		[
			'name' => 'Ajax#getSshKeys',
			'url' => '/ajax/public_key.php',
			'verb' => 'POST',
		],
		[
			'name' => 'Ajax#saveGlobalCredentials',
			'url' => '/globalcredentials',
			'verb' => 'POST',
		],
	],
	'ocs' => [
		[
			'name' => 'Api#getUserMounts',
			'url' => '/api/v1/mounts',
			'verb' => 'GET',
		],
	],
];
