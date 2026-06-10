<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
$CONFIG = [
	'memcache.local' => '\\OC\\Memcache\\Memcached',
	'memcache.distributed' => '\\OC\\Memcache\\Memcached',
	'memcache.locking' => '\\OC\\Memcache\\Memcached',
	'memcached_servers' => [
		['localhost', 11211],
	],
];
