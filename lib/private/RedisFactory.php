<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Alejandro Varela <epma01@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC;

use OCP\Diagnostics\IEventLogger;

class RedisFactory {
	public const REDIS_MINIMAL_VERSION = '4.0.0';
	public const REDIS_EXTRA_PARAMETERS_MINIMAL_VERSION = '5.3.0';

	/** @var  \Redis|\RedisCluster */
	private $instance;

	private SystemConfig $config;

	private IEventLogger $eventLogger;

	/**
	 * RedisFactory constructor.
	 *
	 * @param SystemConfig $config
	 */
	public function __construct(SystemConfig $config, IEventLogger $eventLogger) {
		$this->config = $config;
		$this->eventLogger = $eventLogger;
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
			$port = $config['port'] ?? ($host[0] !== '/' ? 6379 : null);

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
		return \extension_loaded('redis') &&
			\version_compare(\phpversion('redis'), self::REDIS_MINIMAL_VERSION, '>=');
	}

	/**
	 * Php redis does support configurable extra parameters since version 5.3.0, see: https://github.com/phpredis/phpredis#connect-open.
	 * We need to check if the current version supports extra connection parameters, otherwise the connect method will throw an exception
	 *
	 * @return boolean
	 */
	private function isConnectionParametersSupported(): bool {
		return \extension_loaded('redis') &&
			\version_compare(\phpversion('redis'), self::REDIS_EXTRA_PARAMETERS_MINIMAL_VERSION, '>=');
	}
}
