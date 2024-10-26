<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$requirements = [
	'version' => '1',
];

return [
	'ocs' => [
		['name' => 'Api#get', 'url' => '/api/v{version}/{fileId}', 'verb' => 'GET', 'requirements' => $requirements],
		['name' => 'Api#set', 'url' => '/api/v{version}/{fileId}', 'verb' => 'PUT', 'requirements' => $requirements],
		['name' => 'Api#remove', 'url' => '/api/v{version}/{fileId}', 'verb' => 'DELETE', 'requirements' => $requirements],
	],
];
