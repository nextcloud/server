<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\Diagnostics\IEventLogger;

class RedisFactory {
	public const REDIS_MINIMAL_VERSION = '4.0.0';
	public const REDIS_EXTRA_PARAMETERS_MINIMAL_VERSION = '5.3.0';

	/** @var \Redis|\RedisCluster */
	private $instance;

	/**
	 * RedisFactory constructor.
	 *
	 * @param SystemConfig $config
	 */
	public function __construct(
		private SystemConfig $config,
		private IEventLogger $eventLogger,
	) {
	}

	private function create() {
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
		// # https://github.com/phpredis/phpredis/issues/1600
		$connectionParameters = $this->getSslContext($config);
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
					'stream' => $this->getSslContext($config)
				];
				if ($persistent) {
					/**
					 * even though the stubs and documentation don't want you to know this,
					 * pconnect does have the same $connectionParameters argument connect has
					 *
					 * https://github.com/phpredis/phpredis/blob/0264de1824b03fb2d0ad515b4d4ec019cd2dae70/redis.c#L710-L730
					 *
					 * @psalm-suppress TooManyArguments
					 */
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

	/**
	 * Get the ssl context config
	 *
	 * @param array $config the current config
	 * @return array|null
	 * @throws \UnexpectedValueException
	 */
	private function getSslContext($config) {
		if (isset($config['ssl_context'])) {
			if (!$this->isConnectionParametersSupported()) {
				throw new \UnexpectedValueException(\sprintf(
					'php-redis extension must be version %s or higher to support ssl context',
					self::REDIS_EXTRA_PARAMETERS_MINIMAL_VERSION
				));
			}
			return $config['ssl_context'];
		}
		return null;
	}

	public function getInstance() {
		if (!$this->isAvailable()) {
			throw new \Exception('Redis support is not available');
		}
		if (!$this->instance instanceof \Redis) {
			$this->create();
		}

		return $this->instance;
	}

	public function isAvailable(): bool {
		return \extension_loaded('redis')
			&& \version_compare(\phpversion('redis'), self::REDIS_MINIMAL_VERSION, '>=');
	}

	/**
	 * Php redis does support configurable extra parameters since version 5.3.0, see: https://github.com/phpredis/phpredis#connect-open.
	 * We need to check if the current version supports extra connection parameters, otherwise the connect method will throw an exception
	 *
	 * @return boolean
	 */
	private function isConnectionParametersSupported(): bool {
		return \extension_loaded('redis')
			&& \version_compare(\phpversion('redis'), self::REDIS_EXTRA_PARAMETERS_MINIMAL_VERSION, '>=');
	}
}
