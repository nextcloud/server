<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

class Redis extends Cache {

	/**
	 * @var \Redis $cache
	 */
	private static $cache = null;

	public function __construct($prefix = '') {
		parent::__construct($prefix);
		if (is_null(self::$cache)) {
			// TODO allow configuring a RedisArray, see https://github.com/nicolasff/phpredis/blob/master/arrays.markdown#redis-arrays
			self::$cache = new \Redis();
			$config = \OC::$server->getSystemConfig()->getValue('redis', array());
			if (isset($config['host'])) {
				$host = $config['host'];
			} else {
				$host = '127.0.0.1';
			}
			if (isset($config['port'])) {
				$port = $config['port'];
			} else {
				$port = 6379;
			}
			if (isset($config['timeout'])) {
				$timeout = $config['timeout'];
			} else {
				$timeout = 0.0; // unlimited
			}

			self::$cache->connect( $host, $port, $timeout );

			if (isset($config['dbindex'])) {
				self::$cache->select( $config['dbindex'] );
			}
		}
	}

	/**
	 * entries in redis get namespaced to prevent collisions between ownCloud instances and users
	 */
	protected function getNameSpace() {
		return $this->prefix;
	}

	public function get($key) {
		$result = self::$cache->get($this->getNamespace() . $key);
		if ($result === false && !self::$cache->exists($this->getNamespace() . $key)) {
			return null;
		} else {
			return json_decode($result, true);
		}
	}

	public function set($key, $value, $ttl = 0) {
		if ($ttl > 0) {
			return self::$cache->setex($this->getNamespace() . $key, $ttl, json_encode($value));
		} else {
			return self::$cache->set($this->getNamespace() . $key, json_encode($value));
		}
	}

	public function hasKey($key) {
		return self::$cache->exists($this->getNamespace() . $key);
	}

	public function remove($key) {
		if (self::$cache->delete($this->getNamespace() . $key)) {
			return true;
		} else {
			return false;
		}

	}

	public function clear($prefix = '') {
		$prefix = $this->getNamespace() . $prefix.'*';
		$it = null;
		self::$cache->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
		while($keys = self::$cache->scan($it, $prefix)) {
			self::$cache->delete($keys);
		}
		return true;
	}

	static public function isAvailable() {
		return extension_loaded('redis');
	}
}

