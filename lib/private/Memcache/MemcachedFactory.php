<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Memcache;

use OC\SystemConfig;
use OCP\HintException;
use OCP\IConfig;
use OCP\Server;

/**
 * Factory for \Memcached instance, to create a singleton
 */
class MemcachedFactory {
	private ?\Memcached $instance = null;

	public function getInstance(): \Memcached {
		if ($this->instance === null) {
			$this->init();
		}
		return $this->instance;
	}

	/** @psalm-assert \Memcached $this->instance */
	public function init(): void {
		$this->instance = new \Memcached();

		$defaultOptions = [
			// Set connect and polling timeouts to 500 milliseconds
			\Memcached::OPT_CONNECT_TIMEOUT => 500,
			\Memcached::OPT_POLL_TIMEOUT => 500,

			// Set send and receive timeouts to 500000 microseconds
			\Memcached::OPT_SEND_TIMEOUT => 500000,
			\Memcached::OPT_RECV_TIMEOUT => 500000,

			// Enable compression
			\Memcached::OPT_COMPRESSION => true,

			// Turn on consistent hashing
			\Memcached::OPT_LIBKETAMA_COMPATIBLE => true,

			// Enable Binary Protocol
			\Memcached::OPT_BINARY_PROTOCOL => true,

			// Disable Nagle algorithm to speed up connection
			\Memcached::OPT_TCP_NODELAY => true,
		];
		/**
		 * By default enable igbinary serializer if available
		 *
		 * Psalm checks depend on if igbinary is installed or not with memcached
		 * @psalm-suppress RedundantCondition
		 * @psalm-suppress TypeDoesNotContainType
		 */
		if (\Memcached::HAVE_IGBINARY) {
			$defaultOptions[\Memcached::OPT_SERIALIZER]
				= \Memcached::SERIALIZER_IGBINARY;
		}
		$options = Server::get(IConfig::class)->getSystemValue('memcached_options', []);
		if (is_array($options)) {
			$options = $options + $defaultOptions;
			$this->instance->setOptions($options);
		} else {
			throw new HintException("Expected 'memcached_options' config to be an array, got $options");
		}

		$servers = Server::get(SystemConfig::class)->getValue('memcached_servers');
		if (!$servers) {
			$server = Server::get(SystemConfig::class)->getValue('memcached_server');
			if ($server) {
				$servers = [$server];
			} else {
				$servers = [['localhost', 11211]];
			}
		}
		$this->instance->addServers($servers);
	}
}
