<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC;

use OCP\Diagnostics\IEventLogger;

/**
 * @deprecated 34.0.2 - use {@see \OC\Memcache\KeyValueCacheFactory} instead
 */
class RedisFactory {
	public const REDIS_MINIMAL_VERSION = '5.3.0';

	private \Redis|\RedisCluster|null $instance = null;

	public function __construct(
		private SystemConfig $config,
		private IEventLogger $eventLogger,
	) {
	}

	private function create(): void {
		$isCluster = in_array('redis.cluster', $this->config->getKeys(), true);
		$config = $isCluster
			? $this->config->getValue('redis.cluster', [])
			: $this->config->getValue('redis', []);

		if ($isCluster && !class_exists('RedisCluster')) {
			throw new \Exception('Redis Cluster support is not available');
		}

		$timeout = $config['timeout'] ?? 0.0;
		$readTimeout = $config['read_timeout'] ?? 0.0;

		$auth = null;
		if (isset($config['password']) && (string)$config['password'] !== '') {
			if (isset($config['user']) && (string)$config['user'] !== '') {
				$auth = [$config['user'], $config['password']];
			} else {
				$auth = $config['password'];
			}
		}

		// # TLS support
		$connectionParameters = $config['ssl_context'] ?? null;
		$persistent = $this->config->getValue('redis.persistent', true);

		// cluster config
		if ($isCluster) {
			if (!isset($config['seeds'])) {
				throw new \Exception('Redis cluster config is missing the "seeds" attribute');
			}

			// Support for older phpredis versions not supporting connectionParameters
			if ($connectionParameters !== null) {
				$this->instance = new \RedisCluster(null, $config['seeds'], $timeout, $readTimeout, $persistent, $auth, $connectionParameters);
			} else {
				$this->instance = new \RedisCluster(null, $config['seeds'], $timeout, $readTimeout, $persistent, $auth);
			}

			if (isset($config['failover_mode'])) {
				$this->instance->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, $config['failover_mode']);
			}
		} else {
			$this->instance = new \Redis();

			$host = $config['host'] ?? '127.0.0.1';
			$port = $config['port'] ?? ($host[0] !== '/' ? 6379 : 0);

			$this->eventLogger->start('connect:redis', 'Connect to redis and send AUTH, SELECT');
			// Support for older phpredis versions not supporting connectionParameters
			if ($connectionParameters !== null) {
				// Non-clustered redis requires connection parameters to be wrapped inside `stream`
				$connectionParameters = [
					'stream' => $config['ssl_context'] ?? null
				];
				if ($persistent) {
					$this->instance->pconnect($host, $port, $timeout, null, 0, $readTimeout, $connectionParameters);
				} else {
					$this->instance->connect($host, $port, $timeout, null, 0, $readTimeout, $connectionParameters);
				}
			} else {
				if ($persistent) {
					$this->instance->pconnect($host, $port, $timeout, null, 0, $readTimeout);
				} else {
					$this->instance->connect($host, $port, $timeout, null, 0, $readTimeout);
				}
			}

			// Auth if configured
			if ($auth !== null) {
				$this->instance->auth($auth);
			}

			if (isset($config['dbindex'])) {
				$this->instance->select($config['dbindex']);
			}
			$this->eventLogger->end('connect:redis');
		}
	}

	public function getInstance(): \Redis|\RedisCluster {
		if ($this->instance === null) {
			if (!$this->isAvailable()) {
				throw new \Exception('Redis support is not available');
			}
			$this->create();
			if ($this->instance === null) {
				throw new \Exception('Redis support is not available');
			}
		}

		return $this->instance;
	}

	public function isAvailable(): bool {
		return \extension_loaded('redis')
			&& \version_compare(\phpversion('redis'), self::REDIS_MINIMAL_VERSION, '>=');
	}
}
