<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
$CONFIG = [
	'memcache.local' => '\\OC\\Memcache\\Redis',
	'memcache.distributed' => '\\OC\\Memcache\\Redis',
	'memcache.locking' => '\\OC\\Memcache\\Redis',
	'redis' => [
		'host' => 'localhost',
		'port' => 6379,
		'timeout' => 0,
	],
];
