<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Memcache;

use OC\SystemConfig;
use OCP\Diagnostics\IEventLogger;
use Predis\Client;

/**
 * Factory that builds a key-value store client (e.g. Valkey or Redis) from the
 * `memcache.kvstore` configuration.
 *
 * The client is backed by the brand-independent predis library and supports
 * three topologies:
 *  - a single server (optionally selecting a numbered database),
 *  - a Sentinel managed replication set,
 *  - a server cluster.
 *
 * @since 34.0.2
 */
final class KeyValueCacheFactory {
	private ?Client $client = null;

	public function __construct(
		private SystemConfig $config,
		private IEventLogger $eventLogger,
	) {
	}

	/**
	 * The key-value store cache is available once predis is installed and a
	 * `memcache.kvstore` configuration is present.
	 */
	public function isAvailable(): bool {
		return class_exists(Client::class)
			&& $this->config->getValue('memcache.kvstore', []) !== [];
	}

	/**
	 * Get the (lazily connecting) predis client for the configured topology.
	 *
	 * @throws \RuntimeException if the cache is not configured
	 */
	public function getInstance(): Client {
		if ($this->client === null) {
			if (!$this->isAvailable()) {
				throw new \RuntimeException('Key-value store cache is not available');
			}
			$this->client = $this->create();
		}

		return $this->client;
	}

	private function create(): Client {
		/** @var array $config */
		$config = $this->config->getValue('memcache.kvstore', []);

		$this->eventLogger->start('connect:kvstore', 'Connect to the key-value store cache server');
		[$parameters, $options] = $this->buildConnectionConfig($config);
		$client = new Client($parameters, $options);
		$this->eventLogger->end('connect:kvstore');

		return $client;
	}

	/**
	 * Translate the `memcache.kvstore` configuration into predis connection
	 * parameters and client options.
	 *
	 * This method is pure (it does not connect) so that the mapping can be
	 * verified without a running cache server.
	 *
	 * @param array $config The `memcache.kvstore` configuration
	 * @return array{0: array, 1: array} tuple of `[$parameters, $options]`
	 * @throws \RuntimeException on invalid configuration
	 */
	public function buildConnectionConfig(array $config): array {
		if (isset($config['sentinel'])) {
			return $this->buildSentinelConfig($config);
		}

		if (isset($config['seeds'])) {
			return $this->buildClusterConfig($config);
		}

		return $this->buildSingleServerConfig($config);
	}

	/**
	 * @return array{0: array, 1: array}
	 */
	private function buildSingleServerConfig(array $config): array {
		if (!isset($config['server']) || !is_array($config['server'])) {
			throw new \RuntimeException('memcache.kvstore is missing the "server" configuration');
		}

		$parameters = $this->createServerParameters($config['server'], $config);

		// Numbered databases are only supported on single servers for now, as
		// predis does not support selecting a database on a cluster yet.
		if (isset($config['dbindex'])) {
			$parameters['database'] = (int)$config['dbindex'];
		}

		return [$parameters, []];
	}

	/**
	 * @return array{0: array, 1: array}
	 */
	private function buildClusterConfig(array $config): array {
		$seeds = $config['seeds'];
		if (!is_array($seeds) || $seeds === []) {
			throw new \RuntimeException('memcache.kvstore cluster configuration is missing the "seeds" attribute');
		}

		$parameters = array_map(
			fn (array $seed): array => $this->createServerParameters($seed, $config),
			array_values($seeds),
		);

		$options = ['cluster' => 'redis'];
		$nodeParameters = $this->createSharedParameters($config);
		if ($nodeParameters !== []) {
			// applied to the nodes discovered while talking to the cluster
			$options['parameters'] = $nodeParameters;
		}

		return [$parameters, $options];
	}

	/**
	 * @return array{0: array, 1: array}
	 */
	private function buildSentinelConfig(array $config): array {
		$sentinel = $config['sentinel'];
		if (!is_array($sentinel) || empty($sentinel['service'])) {
			throw new \RuntimeException('memcache.kvstore sentinel configuration is missing the "service" attribute');
		}

		$seeds = $sentinel['seeds'] ?? [];
		if (!is_array($seeds) || $seeds === []) {
			throw new \RuntimeException('memcache.kvstore sentinel configuration is missing the "seeds" attribute');
		}

		$parameters = array_map(
			fn (array $seed): array => $this->createServerParameters($seed, $config),
			array_values($seeds),
		);

		$options = [
			'replication' => 'sentinel',
			'service' => (string)$sentinel['service'],
		];
		$nodeParameters = $this->createSharedParameters($config);
		if (isset($config['dbindex'])) {
			$nodeParameters['database'] = (int)$config['dbindex'];
		}
		if ($nodeParameters !== []) {
			// applied to the master / replica connections resolved via Sentinel
			$options['parameters'] = $nodeParameters;
		}

		return [$parameters, $options];
	}

	/**
	 * Build the predis parameters for a single server entry, merging in the
	 * shared authentication, TLS and timeout options.
	 */
	public function createServerParameters(array $server, array $shared = []): array {
		$host = (string)($server['host'] ?? '127.0.0.1');
		$protocol = $server['protocol'] ?? null;
		if ($protocol === null) {
			// A leading slash indicates a Unix domain socket
			$protocol = ($host !== '' && $host[0] === '/') ? 'unix' : 'tcp';
		}

		$parameters = ['scheme' => (string)$protocol];
		if ($protocol === 'unix') {
			$parameters['path'] = $host;
		} else {
			$parameters['host'] = $host;
			$parameters['port'] = (int)($server['port'] ?? 6379);
		}

		if ($protocol === 'tls' && isset($shared['ssl_context'])) {
			// SSL context options, see https://www.php.net/manual/en/context.ssl.php
			$parameters['ssl'] = $shared['ssl_context'];
		}

		return $parameters + $this->createSharedParameters($shared);
	}

	/**
	 * Authentication, timeout and persistence parameters shared by every node.
	 */
	private function createSharedParameters(array $config): array {
		$parameters = [];

		if (isset($config['password']) && (string)$config['password'] !== '') {
			$parameters['password'] = (string)$config['password'];
			// A username is only sent when ACLs are in use
			if (isset($config['user']) && (string)$config['user'] !== '') {
				$parameters['username'] = (string)$config['user'];
			}
		}

		if (isset($config['timeout'])) {
			$parameters['timeout'] = (float)$config['timeout'];
		}
		if (isset($config['read_timeout'])) {
			$parameters['read_write_timeout'] = (float)$config['read_timeout'];
		}
		if (isset($config['persistent'])) {
			$parameters['persistent'] = (bool)$config['persistent'];
		}

		return $parameters;
	}
}
