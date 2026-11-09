<?php

declare(strict_types=1);

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

	private \Redis|\RedisCluster|null $instance = null;
	private bool $clusterConfigInitialized = false;
	private bool $clusterConfigured = false;
	private bool $redisCapabilitiesInitialized = false;
	private bool $redisAvailable = false;
	private bool $connectionParametersSupported = false;
	private ?string $redisVersion = null;

	public function __construct(
		private readonly SystemConfig $config,
		private readonly IEventLogger $eventLogger,
	) {
	}

	private function create(): void {
		$connectionConfig = $this->getConnectionConfig();
		$timeout = (float)($connectionConfig['timeout'] ?? 0.0);
		$readTimeout = (float)($connectionConfig['read_timeout'] ?? 0.0);
		$auth = $this->buildAuth($connectionConfig);
		// redis.persistent is supported but not documented in config.sample.php
		$persistent = $this->config->getValue('redis.persistent', true);
		// TLS support, see https://github.com/phpredis/phpredis/issues/1600
		$sslConfig = $this->getSslContext($connectionConfig);

		if ($this->isClusterConfigured()) {
			$clusterArgs = [
				null,
				$connectionConfig['seeds'],
				$timeout,
				$readTimeout,
				$persistent,
				$auth,
			];

			if ($sslConfig !== null) {
				$clusterArgs[] = $sslConfig;
			}

			$this->instance = new \RedisCluster(...$clusterArgs);

			if (isset($connectionConfig['failover_mode'])) {
				$this->instance->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, $connectionConfig['failover_mode']);
			}

			return;
		}

		$this->connectStandalone($connectionConfig, $timeout, $readTimeout, $persistent, $auth, $sslConfig);
	}

	/**
	 * Establishes a standalone Redis connection and applies authentication and DB selection.
	 */
	private function connectStandalone(
		array $connectionConfig,
		float $timeout,
		float $readTimeout,
		bool $persistent,
		array|string|null $auth,
		?array $sslConfig,
	): void {
		$this->eventLogger->start('connect:redis', 'Connect to redis (standalone) and send AUTH, SELECT');

		$this->instance = new \Redis();

		$host = $connectionConfig['host'] ?? '127.0.0.1';
		$port = $connectionConfig['port'] ?? ($host[0] !== '/' ? 6379 : 0);
		$connectMethod = $persistent ? 'pconnect' : 'connect';
		$connectArgs = [$host, $port, $timeout, null, 0, $readTimeout];

		if ($sslConfig !== null) {
			// Redis standalone requires connection parameters to be wrapped inside `stream`
			$connectArgs[] = ['stream' => $sslConfig];
		}

		$this->instance->$connectMethod(...$connectArgs);

		if ($auth !== null) {
			$this->instance->auth($auth);
		}

		if (isset($connectionConfig['dbindex'])) {
			$this->instance->select($connectionConfig['dbindex']);
		}

		$this->eventLogger->end('connect:redis');
	}

	/**
	 * Builds the auth argument expected by phpredis from the configured user/password.
	 */
	private function buildAuth(array $connectionConfig): array|string|null {
		$password = (string)($connectionConfig['password'] ?? '');
		$user = (string)($connectionConfig['user'] ?? '');

		if ($password === '') {
			return null;
		}

		if ($user === '') {
			return $password;
		}

		return [$user, $password];
	}

	/**
	 * Returns the effective Redis connection configuration.
	 *
	 * If both redis.cluster and redis standalone configurations are present,
	 * redis.cluster takes precedence.
	 *
	 * @throws \Exception if Redis cluster support is required but unavailable
	 * @throws \UnexpectedValueException if the Redis cluster configuration is invalid
	 */
	private function getConnectionConfig(): array {
		if (!$this->isClusterConfigured()) {
			return $this->config->getValue('redis', []);
		}

		$clusterConfig = $this->config->getValue('redis.cluster', []);

		if (!class_exists('RedisCluster')) {
			throw new \Exception('Redis support is not available: Redis Cluster is configured but RedisCluster support is missing');
		}

		if (!isset($clusterConfig['seeds'])) {
			throw new \UnexpectedValueException('Redis cluster config is missing the "seeds" attribute');
		}

		return $clusterConfig;
	}

	/**
	 * Returns the SSL context configuration for the current Redis connection.
	 *
	 * @throws \UnexpectedValueException if ssl_context is configured but unsupported
	 */
	private function getSslContext(array $connectionConfig): ?array {
		if (!isset($connectionConfig['ssl_context'])) {
			return null;
		}

		if (!$this->isConnectionParametersSupported()) {
			throw new \UnexpectedValueException(\sprintf(
				'Redis support is not available: php-redis extension version %s or higher is required for ssl_context; %s',
				self::REDIS_EXTRA_PARAMETERS_MINIMAL_VERSION,
				$this->getRedisVersionDescription(),
			));
		}

		return $connectionConfig['ssl_context'];
	}

	public function getInstance(): \Redis|\RedisCluster {
		if ($this->instance !== null) {
			return $this->instance;
		}

		if (!$this->isAvailable()) {
			throw new \Exception(\sprintf(
				'Redis support is not available: php-redis extension version %s or higher is required; %s',
				self::REDIS_MINIMAL_VERSION,
				$this->getRedisVersionDescription(),
			));
		}

		$this->create();

		// Should never happen; if create() fails, it will usually throw earlier so this is merely defensive
		if ($this->instance === null) {
			throw new \LogicException('Failed to initialize a Redis instance although redis support is available');
		}

		return $this->instance;
	}

	private function initializeRedisCapabilities(): void {
		if ($this->redisCapabilitiesInitialized) {
			return;
		}

		if (!\extension_loaded('redis')) {
			$this->redisVersion = null;
			$this->redisAvailable = false;
			$this->connectionParametersSupported = false;
			$this->redisCapabilitiesInitialized = true;
			return;
		}

		$this->redisVersion = \phpversion('redis') ?: null;
		$this->redisAvailable = $this->redisVersion !== null
			&& \version_compare($this->redisVersion, self::REDIS_MINIMAL_VERSION, '>=');

		// phpredis supports configurable extra parameters since version 5.3.0
		// required for ssl_context support.
		// see: https://github.com/phpredis/phpredis#connect-open.
		$this->connectionParametersSupported = $this->redisVersion !== null
			&& \version_compare($this->redisVersion, self::REDIS_EXTRA_PARAMETERS_MINIMAL_VERSION, '>=');

		$this->redisCapabilitiesInitialized = true;
	}

	public function isAvailable(): bool {
		$this->initializeRedisCapabilities();
		return $this->redisAvailable;
	}

	/**
	 * Returns whether the installed phpredis version supports extra connection parameters,
	 * which are required for features such as ssl_context support.
	 */
	private function isConnectionParametersSupported(): bool {
		$this->initializeRedisCapabilities();
		return $this->connectionParametersSupported;
	}

	private function isClusterConfigured(): bool {
		if ($this->clusterConfigInitialized) {
			return $this->clusterConfigured;
		}

		$this->clusterConfigured = $this->config->getValue('redis.cluster', null) !== null;
		$this->clusterConfigInitialized = true;

		return $this->clusterConfigured;
	}

	/**
	 * Returns a human-readable description of the installed phpredis version state.
	 */
	private function getRedisVersionDescription(): string {
		$this->initializeRedisCapabilities();

		return $this->redisVersion === null
			? 'php-redis extension is not installed'
			: \sprintf('detected %s', $this->redisVersion);
	}
}
