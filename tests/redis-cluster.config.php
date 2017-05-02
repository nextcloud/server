<?php

$CONFIG = [
	'memcache.local' => '\\OC\\Memcache\\Redis',
	'memcache.distributed' => '\\OC\\Memcache\\Redis',
	'memcache.locking' => '\\OC\\Memcache\\Redis',
	'redis.cluster' => [
		'seeds' => [ // provide some/all of the cluster servers to bootstrap discovery, port required
			'localhost:7000',
			'localhost:7001',
			'localhost:7002',
			'localhost:7003',
			'localhost:7004',
			'localhost:7005'
		],
		'timeout' => 0.0,
		'read_timeout' => 0.0,
		'failover_mode' => \RedisCluster::FAILOVER_ERROR
	],
];
