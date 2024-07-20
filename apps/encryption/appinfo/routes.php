<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
return [
	'routes' => [
		[
			'name' => 'Recovery#adminRecovery',
			'url' => '/ajax/adminRecovery',
			'verb' => 'POST'
		],
		[
			'name' => 'Settings#updatePrivateKeyPassword',
			'url' => '/ajax/updatePrivateKeyPassword',
			'verb' => 'POST'
		],
		[
			'name' => 'Settings#setEncryptHomeStorage',
			'url' => '/ajax/setEncryptHomeStorage',
			'verb' => 'POST'
		],
		[
			'name' => 'Recovery#changeRecoveryPassword',
			'url' => '/ajax/changeRecoveryPassword',
			'verb' => 'POST'
		],
		[
			'name' => 'Recovery#userSetRecovery',
			'url' => '/ajax/userSetRecovery',
			'verb' => 'POST'
		],
		[
			'name' => 'Status#getStatus',
			'url' => '/ajax/getStatus',
			'verb' => 'GET'
		],
	]
];
