<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
return [
	'routes' => [
		[
			'name' => 'RequestHandler#addShare',
			'url' => '/shares',
			'verb' => 'POST',
			'root' => '/ocm',
		],
		[
			'name' => 'RequestHandler#receiveNotification',
			'url' => '/notifications',
			'verb' => 'POST',
			'root' => '/ocm',
		],
		[
			'name' => 'RequestHandler#inviteAccepted',
			'url' => '/invite-accepted',
			'verb' => 'POST',
			'root' => '/ocm',
		]
	],
];
