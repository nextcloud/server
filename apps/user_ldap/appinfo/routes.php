<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

return [
	'routes' => [
		['name' => 'renewPassword#tryRenewPassword', 'url' => '/renewpassword', 'verb' => 'POST'],
		['name' => 'renewPassword#showRenewPasswordForm', 'url' => '/renewpassword/{user}', 'verb' => 'GET'],
		['name' => 'renewPassword#cancel', 'url' => '/renewpassword/cancel', 'verb' => 'GET'],
		['name' => 'renewPassword#showLoginFormInvalidPassword', 'url' => '/renewpassword/invalidlogin/{user}', 'verb' => 'GET'],
	],
];
