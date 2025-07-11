<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
return [
	'routes' => [
		[
			'name' => 'Settings#addClient',
			'url' => '/clients',
			'verb' => 'POST',
		],
		[
			'name' => 'Settings#deleteClient',
			'url' => '/clients/{id}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'LoginRedirector#authorize',
			'url' => '/authorize',
			'verb' => 'GET',
		],
		[
			'name' => 'OauthApi#getToken',
			'url' => '/api/v1/token',
			'verb' => 'POST'
		],
	],
];
