<?php
/**
 * Copyright (c) 2014 Jörn Friedrich Dreyer <jfd@butonic.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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

