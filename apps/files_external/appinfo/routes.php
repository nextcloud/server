<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */


$this->create('files_external_oauth1', 'apps/files_external/ajax/oauth1.php')
	->actionInclude('files_external/ajax/oauth1.php');
$this->create('files_external_oauth2', 'apps/files_external/ajax/oauth2.php')
	->actionInclude('files_external/ajax/oauth2.php');


$this->create('files_external_list_applicable', '/apps/files_external/applicable')
	->actionInclude('files_external/ajax/applicable.php');

return [
	'resources' => [
		'global_storages' => ['url' => '/globalstorages'],
		'user_storages' => ['url' => '/userstorages'],
		'user_global_storages' => ['url' => '/userglobalstorages'],
	],
	'routes' => [
		[
			'name' => 'Ajax#getSshKeys',
			'url' => '/ajax/public_key.php',
			'verb' => 'POST',
			'requirements' => [],
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
