<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
$CONFIG = [
	'apps_paths' => [
		[
			'path' => '/var/www/html/apps',
			'url' => '/apps',
			'writable' => false,
		],
		[
			'path' => '/var/www/html/extra-apps',
			'url' => '/extra-apps',
			'writable' => true,
		],
	],
];
