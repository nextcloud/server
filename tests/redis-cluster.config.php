<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
$CONFIG = [
	'memcache.local' => '\\OC\\Memcache\\Redis',
	'memcache.distributed' => '\\OC\\Memcache\\Redis',
	'memcache.locking' => '\\OC\\Memcache\\Redis',
	'redis.cluster' => [
		'seeds' => [ // provide some/all of the cluster servers to bootstrap discovery, port required
			'cache-cluster:7000',
			'cache-cluster:7001',
			'cache-cluster:7002',
			'cache-cluster:7003',
			'cache-cluster:7004',
			'cache-cluster:7005'
		],
		'timeout' => 0.0,
		'read_timeout' => 0.0,
		'failover_mode' => \RedisCluster::FAILOVER_ERROR
	],
];
