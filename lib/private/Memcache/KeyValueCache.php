<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Memcache;

use OCP\IMemcacheTTL;
use OCP\Server;
use Predis\Client;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Response\ServerException;

/**
 * Brand-independent key-value store cache backend (e.g. Valkey or Redis)
 * implemented on top of the predis library.
 *
 * @since 34.0.2
 */
class KeyValueCache extends Cache implements IMemcacheTTL {
	/** name => [script, sha1] */
	private const array LUA_SCRIPTS = [
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

	private const int MAX_TTL = 30 * 24 * 60 * 60; // 1 month

	private ?Client $cache = null;

	public function getCache(): Client {
		return $this->cache ??= Server::get(KeyValueCacheFactory::class)->getInstance();
	}

	#[\Override]
	public function get($key) {
		$result = $this->getCache()->get($this->getPrefix() . $key);
		if ($result === null) {
			return null;
		}

		return self::decodeValue($result);
	}

	#[\Override]
	public function set($key, $value, $ttl = 0) {
		$value = self::encodeValue($value);
		$ttl = $this->normalizeTtl($ttl);
		return (bool)$this->getCache()->setex($this->getPrefix() . $key, $ttl, $value);
	}

	#[\Override]
	public function hasKey($key) {
		return (bool)$this->getCache()->exists($this->getPrefix() . $key);
	}

	#[\Override]
	public function remove($key) {
		return (bool)$this->getCache()->del($this->getPrefix() . $key);
	}

	#[\Override]
	public function clear($prefix = '') {
		$pattern = $this->getPrefix() . $prefix . '*';
		$client = $this->getCache();

		// On a cluster the key space is spread across the nodes, so we have to
		// scan every node individually. Other topologies route writes to the
		// primary on their own.
		if ($client->getConnection() instanceof ClusterInterface) {
			$success = true;
			/** @var Client $node */
			foreach ($client as $node) {
				// Keys of a single node can still span multiple hash slots, so
				// delete them individually to avoid CROSSSLOT errors.
				$success = $this->clearNode($node, $pattern, true) && $success;
			}
			return $success;
		}

		return $this->clearNode($client, $pattern, false);
	}

	private function clearNode(Client $node, string $pattern, bool $perKey): bool {
		$keys = $node->keys($pattern);
		if ($keys === []) {
			return true;
		}

		if ($perKey) {
			$deleted = 0;
			foreach ($keys as $key) {
				$deleted += $node->del($key);
			}
		} else {
			$deleted = $node->del($keys);
		}

		return count($keys) === $deleted;
	}

	#[\Override]
	public function add($key, $value, $ttl = 0) {
		$value = self::encodeValue($value);
		$ttl = $this->normalizeTtl($ttl);

		return $this->getCache()->set($this->getPrefix() . $key, $value, 'EX', $ttl, 'NX') !== null;
	}

	#[\Override]
	public function inc($key, $step = 1) {
		try {
			return $this->getCache()->incrby($this->getPrefix() . $key, $step);
		} catch (ServerException) {
			// The stored value is not an integer
			return false;
		}
	}

	#[\Override]
	public function dec($key, $step = 1) {
		try {
			$res = $this->evalLua('dec', [$key], [$step]);
		} catch (ServerException) {
			// The stored value is not an integer
			return false;
		}
		return ($res === 'NEX') ? false : $res;
	}

	#[\Override]
	public function cas($key, $old, $new) {
		$old = self::encodeValue($old);
		$new = self::encodeValue($new);

		return $this->evalLua('cas', [$key], [$old, $new]) > 0;
	}

	#[\Override]
	public function cad($key, $old) {
		$old = self::encodeValue($old);

		return $this->evalLua('cad', [$key], [$old]) > 0;
	}

	#[\Override]
	public function ncad(string $key, mixed $old): bool {
		$old = self::encodeValue($old);

		return $this->evalLua('ncad', [$key], [$old]) > 0;
	}

	#[\Override]
	public function setTTL($key, $ttl) {
		$ttl = $this->normalizeTtl($ttl);
		$this->getCache()->expire($this->getPrefix() . $key, $ttl);
	}

	#[\Override]
	public function getTTL(string $key): int|false {
		$ttl = $this->getCache()->ttl($this->getPrefix() . $key);
		return $ttl > 0 ? (int)$ttl : false;
	}

	#[\Override]
	public function compareSetTTL(string $key, mixed $value, int $ttl): bool {
		$value = self::encodeValue($value);

		return $this->evalLua('caSetTtl', [$key], [$value, $ttl]) > 0;
	}

	#[\Override]
	public static function isAvailable(): bool {
		return Server::get(KeyValueCacheFactory::class)->isAvailable();
	}

	/**
	 * Run one of the predefined LUA scripts on the cache server.
	 *
	 * The given keys are prefixed and passed to the script as `KEYS`, followed
	 * by the raw `$args` as `ARGV`. The script is invoked via `EVALSHA` using its
	 * precomputed SHA1; if the server has not cached the script yet (`NOSCRIPT`)
	 * the full body is sent once via `EVAL`. Any other server error is rethrown.
	 *
	 * @param key-of<self::LUA_SCRIPTS> $scriptName The script to run
	 * @param list<string> $keys Unprefixed keys, passed as `KEYS` to the script
	 * @param list<mixed> $args Extra arguments passed as `ARGV` to the script
	 * @return mixed The raw value returned by the script
	 * @throws ServerException on a server-side error other than `NOSCRIPT`
	 */
	protected function evalLua(string $scriptName, array $keys, array $args) {
		$keys = array_map(fn ($key) => $this->getPrefix() . $key, $keys);
		$numKeys = count($keys);
		$arguments = array_merge($keys, $args);
		$script = self::LUA_SCRIPTS[$scriptName];

		try {
			return $this->getCache()->evalsha($script[1], $numKeys, ...$arguments);
		} catch (ServerException $e) {
			// The script is not cached on the server yet, send the full body
			if ($e->getErrorType() === 'NOSCRIPT') {
				return $this->getCache()->eval($script[0], $numKeys, ...$arguments);
			}
			throw $e;
		}
	}

	/**
	 * An infinite TTL can leak keys as the prefix changes with version upgrades,
	 * so fall back to the default and cap it to the maximum.
	 */
	private function normalizeTtl(int $ttl): int {
		if ($ttl <= 0) {
			$ttl = self::DEFAULT_TTL;
		}
		return min($ttl, self::MAX_TTL);
	}

	/**
	 * Serialize a value for storage in the key-value store.
	 */
	protected static function encodeValue(mixed $value): string {
		return is_int($value) ? (string)$value : json_encode($value, JSON_THROW_ON_ERROR);
	}

	/**
	 * Unserialize a value from the key-value store.
	 */
	protected static function decodeValue(string $value): mixed {
		return is_numeric($value) ? (int)$value : json_decode($value, true);
	}
}
