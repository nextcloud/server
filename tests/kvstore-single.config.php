<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
$CONFIG = [
	'memcache.local' => '\\OC\\Memcache\\KeyValueCache',
	'memcache.distributed' => '\\OC\\Memcache\\KeyValueCache',
	'memcache.locking' => '\\OC\\Memcache\\KeyValueCache',
	'memcache.kvstore' => [
		'server' => [
			'host' => 'localhost',
			'port' => 6379,
			'protocol' => 'tcp',
		],
	],
];
