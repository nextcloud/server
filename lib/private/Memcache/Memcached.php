<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OCP\HintException;
use OCP\IMemcache;

class Memcached extends Cache implements IMemcache {
	use CASTrait;

	/**
	 * @var \Memcached $cache
	 */
	private static $cache = null;

	use CADTrait;

	public function __construct($prefix = '') {
		parent::__construct($prefix);
		if (is_null(self::$cache)) {
			self::$cache = new \Memcached();

			$defaultOptions = [
				\Memcached::OPT_CONNECT_TIMEOUT => 50,
				\Memcached::OPT_RETRY_TIMEOUT => 50,
				\Memcached::OPT_SEND_TIMEOUT => 50,
				\Memcached::OPT_RECV_TIMEOUT => 50,
				\Memcached::OPT_POLL_TIMEOUT => 50,

				// Enable compression
				\Memcached::OPT_COMPRESSION => true,

				// Turn on consistent hashing
				\Memcached::OPT_LIBKETAMA_COMPATIBLE => true,

				// Enable Binary Protocol
				\Memcached::OPT_BINARY_PROTOCOL => true,
			];
			/**
			 * By default enable igbinary serializer if available
			 *
			 * Psalm checks depend on if igbinary is installed or not with memcached
			 * @psalm-suppress RedundantCondition
			 * @psalm-suppress TypeDoesNotContainType
			 */
			if (\Memcached::HAVE_IGBINARY) {
				$defaultOptions[\Memcached::OPT_SERIALIZER] =
					\Memcached::SERIALIZER_IGBINARY;
			}
			$options = \OC::$server->getConfig()->getSystemValue('memcached_options', []);
			if (is_array($options)) {
				$options = $options + $defaultOptions;
				self::$cache->setOptions($options);
			} else {
				throw new HintException("Expected 'memcached_options' config to be an array, got $options");
			}

			$servers = \OC::$server->getSystemConfig()->getValue('memcached_servers');
			if (!$servers) {
				$server = \OC::$server->getSystemConfig()->getValue('memcached_server');
				if ($server) {
					$servers = [$server];
				} else {
					$servers = [['localhost', 11211]];
				}
			}
			self::$cache->addServers($servers);
		}
	}

	/**
	 * entries in XCache gets namespaced to prevent collisions between owncloud instances and users
	 */
	protected function getNameSpace() {
		return $this->prefix;
	}

	public function get($key) {
		$result = self::$cache->get($this->getNameSpace() . $key);
		if ($result === false and self::$cache->getResultCode() == \Memcached::RES_NOTFOUND) {
			return null;
		} else {
			return $result;
		}
	}

	public function set($key, $value, $ttl = 0) {
		if ($ttl > 0) {
			$result = self::$cache->set($this->getNameSpace() . $key, $value, $ttl);
		} else {
			$result = self::$cache->set($this->getNameSpace() . $key, $value);
		}
		return $result || $this->isSuccess();
	}

	public function hasKey($key) {
		self::$cache->get($this->getNameSpace() . $key);
		return self::$cache->getResultCode() === \Memcached::RES_SUCCESS;
	}

	public function remove($key) {
		$result = self::$cache->delete($this->getNameSpace() . $key);
		return $result || $this->isSuccess() || self::$cache->getResultCode() === \Memcached::RES_NOTFOUND;
	}

	public function clear($prefix = '') {
		// Newer Memcached doesn't like getAllKeys(), flush everything
		self::$cache->flush();
		return true;
	}

	/**
	 * Set a value in the cache if it's not already stored
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24
	 * @return bool
	 */
	public function add($key, $value, $ttl = 0) {
		$result = self::$cache->add($this->getPrefix() . $key, $value, $ttl);
		return $result || $this->isSuccess();
	}

	/**
	 * Increase a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function inc($key, $step = 1) {
		$this->add($key, 0);
		$result = self::$cache->increment($this->getPrefix() . $key, $step);

		if (self::$cache->getResultCode() !== \Memcached::RES_SUCCESS) {
			return false;
		}

		return $result;
	}

	/**
	 * Decrease a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function dec($key, $step = 1) {
		$result = self::$cache->decrement($this->getPrefix() . $key, $step);

		if (self::$cache->getResultCode() !== \Memcached::RES_SUCCESS) {
			return false;
		}

		return $result;
	}

	public static function isAvailable(): bool {
		return extension_loaded('memcached');
	}

	private function isSuccess(): bool {
		return self::$cache->getResultCode() === \Memcached::RES_SUCCESS;
	}
}
