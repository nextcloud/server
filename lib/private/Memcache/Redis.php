<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OCP\IMemcacheTTL;

class Redis extends Cache implements IMemcacheTTL {
	/** name => [script, sha1] */
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

	private const MAX_TTL = 30 * 24 * 60 * 60; // 1 month

	/**
	 * @var \Redis|\RedisCluster $cache
	 */
	private static $cache = null;

	public function __construct($prefix = '', string $logFile = '') {
		parent::__construct($prefix);
	}

	/**
	 * @return \Redis|\RedisCluster|null
	 * @throws \Exception
	 */
	public function getCache() {
		if (is_null(self::$cache)) {
			self::$cache = \OC::$server->get('RedisFactory')->getInstance();
		}
		return self::$cache;
	}

	public function get($key) {
		$result = $this->getCache()->get($this->getPrefix() . $key);
		if ($result === false) {
			return null;
		}

		return self::decodeValue($result);
	}

	public function set($key, $value, $ttl = 0) {
		$value = self::encodeValue($value);
		if ($ttl === 0) {
			// having infinite TTL can lead to leaked keys as the prefix changes with version upgrades
			$ttl = self::DEFAULT_TTL;
		}
		$ttl = min($ttl, self::MAX_TTL);
		return $this->getCache()->setex($this->getPrefix() . $key, $ttl, $value);
	}

	public function hasKey($key) {
		return (bool)$this->getCache()->exists($this->getPrefix() . $key);
	}

	public function remove($key) {
		if ($this->getCache()->unlink($this->getPrefix() . $key)) {
			return true;
		} else {
			return false;
		}
	}

	public function clear($prefix = '') {
		// TODO: this is slow and would fail with Redis cluster
		$prefix = $this->getPrefix() . $prefix . '*';
		$keys = $this->getCache()->keys($prefix);
		$deleted = $this->getCache()->del($keys);

		return (is_array($keys) && (count($keys) === $deleted));
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
		$value = self::encodeValue($value);
		if ($ttl === 0) {
			// having infinite TTL can lead to leaked keys as the prefix changes with version upgrades
			$ttl = self::DEFAULT_TTL;
		}
		$ttl = min($ttl, self::MAX_TTL);

		$args = ['nx'];
		$args['ex'] = $ttl;

		return $this->getCache()->set($this->getPrefix() . $key, $value, $args);
	}

	/**
	 * Increase a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function inc($key, $step = 1) {
		return $this->getCache()->incrBy($this->getPrefix() . $key, $step);
	}

	/**
	 * Decrease a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function dec($key, $step = 1) {
		$res = $this->evalLua('dec', [$key], [$step]);
		return ($res === 'NEX') ? false : $res;
	}

	/**
	 * Compare and set
	 *
	 * @param string $key
	 * @param mixed $old
	 * @param mixed $new
	 * @return bool
	 */
	public function cas($key, $old, $new) {
		$old = self::encodeValue($old);
		$new = self::encodeValue($new);

		return $this->evalLua('cas', [$key], [$old, $new]) > 0;
	}

	/**
	 * Compare and delete
	 *
	 * @param string $key
	 * @param mixed $old
	 * @return bool
	 */
	public function cad($key, $old) {
		$old = self::encodeValue($old);

		return $this->evalLua('cad', [$key], [$old]) > 0;
	}

	public function ncad(string $key, mixed $old): bool {
		$old = self::encodeValue($old);

		return $this->evalLua('ncad', [$key], [$old]) > 0;
	}

	public function setTTL($key, $ttl) {
		if ($ttl === 0) {
			// having infinite TTL can lead to leaked keys as the prefix changes with version upgrades
			$ttl = self::DEFAULT_TTL;
		}
		$ttl = min($ttl, self::MAX_TTL);
		$this->getCache()->expire($this->getPrefix() . $key, $ttl);
	}

	public function getTTL(string $key): int|false {
		$ttl = $this->getCache()->ttl($this->getPrefix() . $key);
		return $ttl > 0 ? (int)$ttl : false;
	}

	public function compareSetTTL(string $key, mixed $value, int $ttl): bool {
		$value = self::encodeValue($value);

		return $this->evalLua('caSetTtl', [$key], [$value, $ttl]) > 0;
	}

	public static function isAvailable(): bool {
		return \OC::$server->get('RedisFactory')->isAvailable();
	}

	protected function evalLua(string $scriptName, array $keys, array $args) {
		$keys = array_map(fn ($key) => $this->getPrefix() . $key, $keys);
		$args = array_merge($keys, $args);
		$script = self::LUA_SCRIPTS[$scriptName];

		$result = $this->getCache()->evalSha($script[1], $args, count($keys));
		if ($result === false) {
			$result = $this->getCache()->eval($script[0], $args, count($keys));
		}

		return $result;
	}

	protected static function encodeValue(mixed $value): string {
		return is_int($value) ? (string)$value : json_encode($value);
	}

	protected static function decodeValue(string $value): mixed {
		return is_numeric($value) ? (int)$value : json_decode($value, true);
	}
}
