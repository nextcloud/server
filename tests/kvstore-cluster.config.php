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
		'seeds' => [
			['host' => '127.0.0.1', 'port' => 7000],
			['host' => '127.0.0.1', 'port' => 7001],
			['host' => '127.0.0.1', 'port' => 7002],
			['host' => '127.0.0.1', 'port' => 7003],
			['host' => '127.0.0.1', 'port' => 7004],
			['host' => '127.0.0.1', 'port' => 7005],
		],
	],
];
