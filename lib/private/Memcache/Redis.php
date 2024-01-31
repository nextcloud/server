<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weil <sw@weilnetz.de>
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
		'caSetTtl' => [
			'if redis.call("get", KEYS[1]) == ARGV[1] then redis.call("expire", KEYS[1], ARGV[2]) return 1 else return 0 end',
			'fa4acbc946d23ef41d7d3910880b60e6e4972d72',
		],
	];

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
			self::$cache = \OC::$server->getGetRedisFactory()->getInstance();
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
		if ($ttl > 0) {
			return $this->getCache()->setex($this->getPrefix() . $key, $ttl, $value);
		} else {
			return $this->getCache()->set($this->getPrefix() . $key, $value);
		}
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

		$args = ['nx'];
		if ($ttl !== 0 && is_int($ttl)) {
			$args['ex'] = $ttl;
		}

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

	public function setTTL($key, $ttl) {
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
		return \OC::$server->getGetRedisFactory()->isAvailable();
	}

	protected function evalLua(string $scriptName, array $keys, array $args) {
		$keys = array_map(fn ($key) => $this->getPrefix() . $key, $keys);
		$args = array_merge($keys, $args);
		$script = self::LUA_SCRIPTS[$scriptName];

		$result = $this->getCache()->evalSha($script[1], $args, count($keys));
		if (false === $result) {
			$result = $this->getCache()->eval($script[0], $args, count($keys));
		}

		return $result;
	}

	protected static function encodeValue(mixed $value): string {
		return is_int($value) ? (string) $value : json_encode($value);
	}

	protected static function decodeValue(string $value): mixed {
		return is_numeric($value) ? (int) $value : json_decode($value, true);
	}
}
