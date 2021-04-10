<?php

$CONFIG = [
	'memcache.local' => '\\OC\\Memcache\\Redis',
	'memcache.distributed' => '\\OC\\Memcache\\Redis',
	'memcache.locking' => '\\OC\\Memcache\\Redis',
	'redis' => [
		'host' => 'cache',
		'port' => 6379,
		'timeout' => 0,
	],
];
