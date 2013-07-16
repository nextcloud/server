<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

class Memcached extends Cache {
	/**
	 * @var \Memcached $cache
	 */
	private static $cache = null;

	public function __construct($prefix = '') {
		parent::__construct($prefix);
		if (is_null(self::$cache)) {
			self::$cache = new \Memcached();
			list($host, $port) = \OC_Config::getValue('memcached_server', array('localhost', 11211));
			self::$cache->addServer($host, $port);
		}
	}

	/**
	 * entries in XCache gets namespaced to prevent collisions between owncloud instances and users
	 */
	protected function getNameSpace() {
		return $this->prefix;
	}

	public function get($key) {
		$result = self::$cache->get($this->getNamespace() . $key);
		if ($result === false and self::$cache->getResultCode() == \Memcached::RES_NOTFOUND) {
			return null;
		} else {
			return $result;
		}
	}

	public function set($key, $value, $ttl = 0) {
		if ($ttl > 0) {
			return self::$cache->set($this->getNamespace() . $key, $value, $ttl);
		} else {
			return self::$cache->set($this->getNamespace() . $key, $value);
		}
	}

	public function hasKey($key) {
		self::$cache->get($this->getNamespace() . $key);
		return self::$cache->getResultCode() !== \Memcached::RES_NOTFOUND;
	}

	public function remove($key) {
		return self::$cache->delete($this->getNamespace() . $key);
	}

	public function clear($prefix = '') {
		$prefix = $this->getNamespace() . $prefix;
		$allKeys = self::$cache->getAllKeys();
		$keys = array();
		$prefixLength = strlen($prefix);
		foreach ($allKeys as $key) {
			if (substr($key, 0, $prefixLength) === $prefix) {
				$keys[] = $key;
			}
		}
		self::$cache->deleteMulti($keys);
		return true;
	}

	static public function isAvailable() {
		return extension_loaded('memcached');
	}
}
