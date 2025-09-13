<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OCP\IMemcache;

/**
 * APCu-backed cache implementation for Nextcloud.
 *
 * Primary use case is as a localCache with a data scope of the application server of the running process.
 * Can also be configured as the "distributedCache" but will still have the data scope of the application server of the running process.
 * Can also be configured as the lockingCache but data scope will be limited to the application server of the running process.
 *
 * @see https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/caching_configuration.html
 * @see https://docs.nextcloud.com/server/latest/developer_manual/basics/caching.html
 * @see OC\Memcache\Factory
 * @see registerService() activation in OC\Server for Factory::class
 *
 */
class APCu extends Cache implements IMemcache {
	use CASTrait {
		cas as casEmulated;
	}
	use CADTrait;

	/**
	 * Fetches a value from the cache.
	 *
	 * @param string $key
	 * @return mixed|null Value if found, null otherwise
	 */
	public function get($key) {
		$result = apcu_fetch($this->getPrefix() . $key, $success);
		return $success ? $result : null;
	}

	/**
	 * Stores a value in the cache.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24 (24h)
	 * @return bool
	 */
	public function set($key, $value, $ttl = 0) {
		if ($ttl === 0) {
			$ttl = self::DEFAULT_TTL;
		}
		return apcu_store($this->getPrefix() . $key, $value, $ttl) === true;
	}

	/**
	 * Checks if a given key exists in the cache.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasKey($key) {
		return apcu_exists($this->getPrefix() . $key) === true;
	}
	
	/**
	 * Removes a value from the cache.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function remove($key) {
		return apcu_delete($this->getPrefix() . $key) !== false;
	}

	/**
	 * Clears all cache entries that match the given prefix.
	 *
	 * @param string $prefix
	 * @return bool
	 */
	public function clear($prefix = '') {
		/**
		 * Note: Prefixes/namespaces in caching are currently inconsistent/confusing.
		 * There are multiple levels (instance, user, app/use case) which may overlap.
		 * Also, other implementations (e.g., MemCached) clear everything regardless 
		 * of prefix.
		 * TODO: Standardize prefix naming and document any differences across cache 
		 * backends.
		 */
		$combinedNamespace = preg_quote($this->getPrefix() . $prefix, '/');
		$iterator = new \APCUIterator(
			// only care about keys that start with our $combinedNamespace
			'/^' . $combinedNamespace . '/',
			// only return the key names when interating
			APC_ITER_KEY,
		);
		return apcu_delete($iterator) !== false;
	}

	/**
	 * Adds a key to the cache only if it does not already exist.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24 (24h)
	 * @return bool True if added; False if not added (i.e. key already exists)
	 */
	public function add($key, $value, $ttl = 0) {
		if ($ttl === 0) {
			$ttl = self::DEFAULT_TTL;
		}
		return apcu_add($this->getPrefix() . $key, $value, $ttl) === true;
	}

	/**
	 * Increments a stored number.
	 *
	 * If the key does not exist, it is created and set to `0` before 
	 * performing the increment (i.e. returning a value of `1`).
	 * 
	 * The TTL is left alone on preexisting keys, but newly created keys 
	 * automatically get assigned a TTL of self::DEFAULT_TTL.
	 *
	 * @param string $key
	 * @param int $step
	 * @return int|bool New value on success, false on failure
	 */
	public function inc($key, $step = 1) {
		$success = null; // don't care
		return apcu_inc($this->getPrefix() . $key, $step, $success, self::DEFAULT_TTL);
	}

	/**
	 * Decrements a stored number.
	 *
	 * If the key does not exist, false is returned and the operation 
	 * does not take place. This differs from `inc()` above for unknown reasons, 
	 * but it does match the interface and other implementations.
	 *
	 * @param string $key
	 * @param int $step
	 * @return int|bool New value on success, false if key does not exist
	 */
	public function dec($key, $step = 1) {
		return $this->hasKey($key) ? apcu_dec($this->getPrefix() . $key, $step) : false;
	}

	/**
	 * Compare-and-set.
	 * 
	 * If $key's current value is equal to $expectedValue, set it to $newValue.
	 *
	 * Uses APCu's native CAS for integers, otherwise an "emulated" CAS.
	 *
	 * @param string $key
	 * @param mixed $expectedValue
	 * @param mixed $newValue
	 * @return bool True if value was updated; False if no update occurred
	 */
	public function cas($key, $expectedValue, $newValue) {
		// APCu only does cas for ints
		if (is_int($expectedValue) && is_int($newValue)) {
			return apcu_cas($this->getPrefix() . $key, $expectedValue, $newValue);
		} else {
			return $this->casEmulated($key, $expectedValue, $newValue);
		}
	}

	/**
	 * Checks if APCu is usable.
	 *
	 * @return bool
	 */
	public static function isAvailable(): bool {
		return function_exists('apcu_enabled')
			&& apcu_enabled()
			&& version_compare(phpversion('apcu'), '5.1.19', '>=');
	}
}
