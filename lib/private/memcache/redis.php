<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Telatynski <7t3chguy@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Memcache;

use OCP\IMemcacheTTL;
use Predis\Client;
use Predis\Response\ServerException;
use Predis\Transaction\MultiExec;

class Redis extends Cache implements IMemcacheTTL {
	/** @var  Client */
	private $instance;

	public function __construct($prefix = '') {
		parent::__construct($prefix);
		$this->instance = \OC::$server->getRedisFactory()->getInstance();
	}

	/**
	 * entries in redis get namespaced to prevent collisions between ownCloud instances and users
	 */
	protected function getNameSpace() {
		return $this->prefix;
	}

	public function get($key) {
		$result = $this->instance->get($this->getNameSpace() . $key);
		if ($result === null && !$this->instance->exists($this->getNameSpace() . $key)) {
			return null;
		} else {
			return json_decode($result, true);
		}
	}

	public function set($key, $value, $ttl = 0) {
		if ($ttl > 0) {
			return $this->instance->setex($this->getNameSpace() . $key, $ttl, json_encode($value));
		} else {
			return $this->instance->set($this->getNameSpace() . $key, json_encode($value));
		}
	}

	public function hasKey($key) {
		return $this->instance->exists($this->getNameSpace() . $key);
	}

	public function remove($key) {
		if ($this->instance->del([$this->getNameSpace() . $key])) {
			return true;
		} else {
			return false;
		}
	}

	public function clear($prefix = '') {
		$prefix = $this->getNameSpace() . $prefix . '*';
		$keys = $this->instance->keys($prefix);
		foreach ($keys as $key) {
			$this->instance->del([$key]);
		}
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
		// don't encode ints for inc/dec
		if (!is_int($value)) {
			$value = json_encode($value);
		}
		return $this->instance->setnx($this->getPrefix() . $key, $value);
	}

	/**
	 * Increase a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function inc($key, $step = 1) {
		try {
			return $this->instance->incrby($this->getNameSpace() . $key, $step);
		} catch (ServerException $e) { // not an int
			return false;
		}
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
		try {
			return $this->instance->decrby($this->getNameSpace() . $key, $step);
		} catch (ServerException $e) { // not an int
			return false;
		}
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
		$fullKey = $this->getNameSpace() . $key;

		$this->instance->watch($fullKey);
		$existing = json_decode($this->instance->get($fullKey), true);
		if ($existing === $old) {
			$this->instance->multi();
			$this->instance->set($fullKey, $new);
			return !is_null($this->instance->exec());
		}
		$this->instance->unwatch();
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
		$fullKey = $this->getNameSpace() . $key;

		$this->instance->watch($fullKey);
		$existing = json_decode($this->instance->get($fullKey), true);
		if ($existing === $old) {
			$this->instance->multi();
			$this->instance->del([$fullKey]);
			return !is_null($this->instance->exec());
		}
		$this->instance->unwatch();
		return false;
	}

	public function setTTL($key, $ttl) {
		$this->instance->expire($this->getNameSpace() . $key, $ttl);
	}

	static public function isAvailable() {
		return true;
	}
}

