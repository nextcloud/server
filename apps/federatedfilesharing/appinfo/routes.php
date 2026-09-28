<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
return [
	'routes' => [
		['name' => 'MountPublicLink#createFederatedShare', 'url' => '/createFederatedShare', 'verb' => 'POST'],
		['name' => 'MountPublicLink#askForFederatedShare', 'url' => '/askForFederatedShare', 'verb' => 'POST'],
	],
	'ocs' => [
		['root' => '/cloud', 'name' => 'RequestHandler#createShare', 'url' => '/shares', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'RequestHandler#reShare', 'url' => '/shares/{id}/reshare', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'RequestHandler#updatePermissions', 'url' => '/shares/{id}/permissions', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'RequestHandler#acceptShare', 'url' => '/shares/{id}/accept', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'RequestHandler#declineShare', 'url' => '/shares/{id}/decline', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'RequestHandler#unshare', 'url' => '/shares/{id}/unshare', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'RequestHandler#revoke', 'url' => '/shares/{id}/revoke', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'RequestHandler#move', 'url' => '/shares/{id}/move', 'verb' => 'POST'],
	],
];
