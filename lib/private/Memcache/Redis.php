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
		$key = $this->getPrefix() . $key;
		$result = $this->getCache()->get($key);
		if ($result === false) {
			return null;
		}
		return json_decode($result, true);
	}

	public function set($key, $value, $ttl = 0) {
		$key = $this->getPrefix() . $key;
		$value = json_encode($value);
		if ($ttl > 0) {
			return $this->getCache()->set($key, $value, $ttl);
		}
		return $this->getCache()->set($key, $value);
	}

	public function hasKey($key) {
		return (bool)$this->getCache()->exists($this->getPrefix() . $key);
	}

	public function remove($key) {
		return (bool)$this->getCache()->unlink($this->getPrefix() . $key);
	}

	public function clear($prefix = '') {
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
		// don't encode ints for inc/dec
		if (!is_int($value)) {
			$value = json_encode($value);
		}

		$args = ['nx'];
		if ($ttl !== 0 && is_int($ttl)) {
			$args['ex'] = $ttl;
		}

		return $this->getCache()->set($this->getPrefix() . $key, (string)$value, $args);
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
		if (!$this->hasKey($key)) {
			return false;
		}
		return $this->getCache()->decrBy($this->getPrefix() . $key, $step);
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
		if (!is_int($new)) {
			$new = json_encode($new);
		}
		$this->getCache()->watch($this->getPrefix() . $key);
		if ($this->get($key) === $old) {
			$result = $this->getCache()->multi()
				->set($this->getPrefix() . $key, $new)
				->exec();
			return $result !== false;
		}
		$this->getCache()->unwatch();
		return false;
	}

	/**
	 * Compare and delete
	 *
	 * @param string $key
	 * @param mixed $old
	 * @return bool
	 */
	public function cad($key, $old) {
		$this->getCache()->watch($this->getPrefix() . $key);
		if ($this->get($key) === $old) {
			$result = $this->getCache()->multi()
				->unlink($this->getPrefix() . $key)
				->exec();
			return $result !== false;
		}
		$this->getCache()->unwatch();
		return false;
	}

	public function setTTL($key, $ttl) {
		$this->getCache()->expire($this->getPrefix() . $key, $ttl);
	}

	public static function isAvailable(): bool {
		return \OC::$server->getGetRedisFactory()->isAvailable();
	}
}
