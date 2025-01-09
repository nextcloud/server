<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
return [
	'ocs' => [
		// old endpoints, only used by Nextcloud and ownCloud
		[
			'name' => 'OCSAuthAPI#getSharedSecretLegacy',
			'url' => '/api/v1/shared-secret',
			'verb' => 'GET',
		],
		[
			'name' => 'OCSAuthAPI#requestSharedSecretLegacy',
			'url' => '/api/v1/request-shared-secret',
			'verb' => 'POST',
		],
		// new endpoints, published as public api
		[
			'name' => 'OCSAuthAPI#getSharedSecret',
			'root' => '/cloud',
			'url' => '/shared-secret',
			'verb' => 'GET',
		],
		[
			'name' => 'OCSAuthAPI#requestSharedSecret',
			'root' => '/cloud',
			'url' => '/shared-secret',
			'verb' => 'POST',
		],
	],
];
