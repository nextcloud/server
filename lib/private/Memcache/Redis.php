<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OCP\IMemcacheTTL;

/**
 * Redis-backed cache implementation for Nextcloud.
 */
class Redis extends Cache implements IMemcacheTTL {
	/** Lua scripts used for atomic operations: name => [script, sha1] */
	public const LUA_SCRIPTS = [
		'dec' => [
			'if redis.call("exists", KEYS[1]) == 1 then return redis.call("decrby", KEYS[1], ARGV[1]) else return "NEX" end',
			'720b40cb66cef1579f2ef16ec69b3da8c85510e9',
		],
		'cas' => [
			'if redis.call("get", KEYS[1]) == ARGV[1] then redis.call("set", KEYS[1], ARGV[2]) return 1 else return 0 end',
			'94eac401502554c02b811e3199baddde62d976d4',
		],
		'cad' => [
			'if redis.call("get", KEYS[1]) == ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end',
			'cf0e94b2e9ffc7e04395cf88f7583fc309985910',
		],
		'ncad' => [
			'if redis.call("get", KEYS[1]) ~= ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end',
			'75526f8048b13ce94a41b58eee59c664b4990ab2',
		],
		'caSetTtl' => [
			'if redis.call("get", KEYS[1]) == ARGV[1] then redis.call("expire", KEYS[1], ARGV[2]) return 1 else return 0 end',
			'fa4acbc946d23ef41d7d3910880b60e6e4972d72',
		],
	];

	private const MAX_TTL = 30 * 24 * 60 * 60; // 30 days
	
	/** @var \Redis|\RedisCluster|null $cacheInstance */
	private static $cacheInstance = null;

	public function __construct($prefix = '', string $logFile = '') {
		parent::__construct($prefix);
		if (self::$cacheInstance === null) {
			self::$cacheInstance = \OC::$server->get('RedisFactory')->getInstance();
		}
	}

	/**
	 * Fetches a value from the cache.
	 *
	 * @param string $key
	 * @return mixed|null Value if found, null otherwise
	 */
	public function get($key) {
		$result = self::$cacheInstance->get($this->getPrefix() . $key);
		return $result === false ? null : self::decodeValue($result);
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
		$value = self::encodeValue($value);
		$ttl = $ttl === 0 ? self::DEFAULT_TTL : min($ttl, self::MAX_TTL);
		return self::$cacheInstance->setex($this->getPrefix() . $key, $ttl, $value);
	}

	/**
	 * Checks if a given key exists in the cache.
	 *
	 * @param string $key
	 * @return bool True if key exists, false if not
	 */
	public function hasKey($key) {
		return self::$cacheInstance->exists($this->getPrefix() . $key) > false;
	}

	/**
	 * Removes a value from the cache.
	 *
	 * @param string $key
	 * @return bool True if key was removed (or never existed to start with)
  	 *				False only upon unexpected failure (i.e. connection matter)
	 */
	public function remove($key) {
		return self::$cacheInstance->unlink($this->getPrefix() . $key) !== false;
	}

	/**
	 * Clears all cache entries that match the given prefix.
  	 * NOTE: This is slow and may fail with Redis Cluster.
	 *
	 * @param string $prefix
	 * @return bool True if all matching keys were cleared or if no matching keys were found
  	 * 				False if not all keys were successfully cleared
	 */
	public function clear($prefix = '') {
		/**
		 * Note: Prefixes/namespaces variable naming is inconsistent/confusing.
   		 * @see \OC\Memcache\APCu::clear()
		 */
		$pattern = $this->getPrefix() . $prefix . '*';
		/** @var array|false */
		$keys = self::$cacheInstance->keys($pattern);		
		if (!is_array($keys) || count($keys) === 0) { // no matching keys
			return true; // nothing to do; consider operation done
		}
		$deleted = self::$cacheInstance->unlink($keys);
		return count($keys) === $deleted;
	}

	/**
	 * Adds a key to the cache only if it does not already exist.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24 (24h)
	 * @return bool False if key already exists (new value is ignored)
	 */
	public function add($key, $value, $ttl = 0) {
		$encodedValue = self::encodeValue($value);
		$ttl = $ttl === 0 ? self::DEFAULT_TTL : min($ttl, self::MAX_TTL);
		$options = ['nx', 'ex' => $ttl];
		return self::$cacheInstance->set($this->getPrefix() . $key, $encodedValue, $options);
	}

	/**
	 * Increments a stored number.
	 *
	 * If the key does not exist, it is created and set to `0` before 
	 * performing the increment (i.e. returning a value of `1`).
	 * 
	 * The TTL is left alone on preexisting keys, but newly created keys 
     * will not have a TTL.
	 * Note: This is different from our APCu implementation, which sets a
	 * TTL of self::DEFAULT_TTL. 
  	 * TODO: Our interface is silent on the topic, but the inconsistency
	 * should probably be fixed.
	 * @see \OC\Memcache\APCu::inc()
  	 * @see \OCP\IMemcache::inc()
	 *
	 * @param string $key
	 * @param int $step
	 * @return int|bool New value on success, false on failure
	 */
	public function inc($key, $step = 1) {
		return self::$cacheInstance->incrBy($this->getPrefix() . $key, $step);
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
		// 'if redis.call("exists", KEYS[1]) == 1 then return redis.call("decrby", KEYS[1], ARGV[1]) else return "NEX" end',
		$result = $this->evalLua('dec', [$key], [$step]);
		return ($result === 'NEX') ? false : $result;
	}

	/**
	 * Compare-and-set.
	 * 
	 * If $key's current value is equal to $expectedValue, set it to $newValue.
	 *
	 * @param string $key
	 * @param mixed $expectedValue
	 * @param mixed $newValue
	 * @return bool True if value was updated; False if no update occurred
	 */
	public function cas($key, $expectedValue, $newValue) {
		$expectedValueEncoded = self::encodeValue($expectedValue);
		$newValueEncoded = self::encodeValue($newValue);
		// 'if redis.call("get", KEYS[1]) == ARGV[1] then redis.call("set", KEYS[1], ARGV[2]) return 1 else return 0 end',
		return $this->evalLua('cas', [$key], [$expectedValueEncoded, $newValueEncoded]) > 0;
	}

	/**
	 * Compare-and-delete.
  	 *
	 * If $key's current value is equal to $expectedValue, delete $key.
  	 * 
  	 * TODO: Compare against using built-in `GETDEL` for simple strings (requires >=v6.2.0).
	 *
	 * @param string $key
	 * @param mixed $expectedValue
	 * @return bool True if key was deleted; False if no deletion occurred
	 */
	public function cad($key, $expectedValue) {
		$expectedValueEncoded = self::encodeValue($expectedValue);
		// 'if redis.call("get", KEYS[1]) == ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end',
		return $this->evalLua('cad', [$key], [$expectedValueEncoded]) > 0;
	}

	/**
 	 * Nonequal-compare-and-delete.
   	 *
	 * If $key's current value is not equal to $expectedValue, delete $key.
  	 *
	 * @param string $key
	 * @param mixed $expectedValue
	 * @return bool True if key was deleted; False if no deletion occurred
	 */
	public function ncad(string $key, mixed $expectedValue): bool {
		$expectedValueEncoded = self::encodeValue($expectedValue);
		// 'if redis.call("get", KEYS[1]) ~= ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end',
		return $this->evalLua('ncad', [$key], [$expectedValueEncoded]) > 0;
	}

	/**
	 * Check if Redis is available (wrapper)
  	 *
	 * @return bool
	 */
	public static function isAvailable(): bool {
		return \OC::$server->get('RedisFactory')->isAvailable();
	}

	//
	// Implements for OCP\IMemcacheTTL
	//

	/**
	 * Set TTL for a cache key.
  	 *
	 * @param string $key
  	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24 (24h)
	 */
	public function setTTL($key, $ttl) {
		$ttl = $ttl === 0 ? self::DEFAULT_TTL : min($ttl, self::MAX_TTL);
		self::$cacheInstance->expire($this->getPrefix() . $key, $ttl);
	}

	/**
	 * Get TTL for a cache key.
	 */
	public function getTTL(string $key): int|false {
		$ttl = self::$cacheInstance->ttl($this->getPrefix() . $key);
		return $ttl > 0 ? (int)$ttl : false;
	}

	/**
	 * Compare and set TTL atomically.
	 */
	public function compareSetTTL(string $key, mixed $value, int $ttl): bool {
		$value = self::encodeValue($value);
		// 'if redis.call("get", KEYS[1]) == ARGV[1] then redis.call("expire", KEYS[1], ARGV[2]) return 1 else return 0 end',
		return $this->evalLua('caSetTtl', [$key], [$value, $ttl]) > 0;
	}

	//
	// Utility functions
	//

	/**
	 * Evaluate a Lua script for atomic operations.
	 */
	protected function evalLua(string $scriptName, array $keys, array $args) {
		$keysWithPrefix = array_map(fn ($key) => $this->getPrefix() . $key, $keys);
		$keysCount = count($keysWithPrefix);
		$args = array_merge($keysWithPrefix, $args);
		
		$script = self::LUA_SCRIPTS[$scriptName];
		
		// Try running cached script by SHA1 first, fallback to sending the script if not cached
		$result = self::$cacheInstance->evalSha($script[1], $args, $keysCount);
		if ($result === false) {
			$result = self::$cacheInstance->eval($script[0], $args, $keysCount);
		}
		return $result;
	}

	/**
	 * Encode a value for Redis storage.
	 */
	protected static function encodeValue(mixed $value): string {
		return is_int($value) ? (string)$value : json_encode($value);
	}

	/**
	 * Decode a value from Redis storage.
	 */
	protected static function decodeValue(string $value): mixed {
		return is_numeric($value) ? (int)$value : json_decode($value, true);
	}
}
