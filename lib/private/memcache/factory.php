<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

use \OCP\ICacheFactory;

class Factory implements ICacheFactory {
	/** @var string $globalPrefix */
	private $globalPrefix;

	/** @var \OCP\ILogger */
	private $logger;

	/**
	 * @param string $globalPrefix
	 * @param \OCP\ILogger $logger
	 */
	public function __construct($globalPrefix, $logger) {
		$this->globalPrefix = $globalPrefix;
		$this->logger = $logger;
	}

	/**
	 * get a cache instance, or Null backend if no backend available
	 *
	 * @param string $prefix
	 * @return \OC\Memcache\Cache
	 */
	function create($prefix = '') {
		$prefix = $this->globalPrefix . '/' . $prefix;
		if (XCache::isAvailable()) {
			$this->logger->debug("creating XCache instance", array('app' => 'memcache'));
			return new XCache($prefix);
		} elseif (APCu::isAvailable()) {
			$this->logger->debug('creating APCu instance', array('app'=>'memcache'));
			return new APCu($prefix);
		} elseif (APC::isAvailable()) {
			$this->logger->debug('creating APC instance', array('app'=>'memcache'));
			return new APC($prefix);
		} elseif (Redis::isAvailable()) {
			$this->logger->debug('creating redis instance', array('app'=>'memcache'));
			return new Redis($prefix);
		} elseif (Memcached::isAvailable()) {
			$this->logger->debug('creating memcached instance', array('app'=>'memcache'));
			return new Memcached($prefix);
		} else {
			$this->logger->debug('no cache available instance', array('app'=>'memcache'));
			return new ArrayCache($prefix);
		}
	}

	/**
	 * check if there is a memcache backend available
	 *
	 * @return bool
	 */
	public function isAvailable() {
		return XCache::isAvailable() || APCu::isAvailable() || APC::isAvailable() || Redis::isAvailable() || Memcached::isAvailable();
	}

	/**
	 * get a in-server cache instance, will return null if no backend is available
	 *
	 * @param string $prefix
	 * @return null|Cache
	 */
	public function createLowLatency($prefix = '') {
		$prefix = $this->globalPrefix . '/' . $prefix;
		if (XCache::isAvailable()) {
			$this->logger->debug('creating xcache instance for low latency', array('app'=>'memcache'));
			return new XCache($prefix);
		} elseif (APCu::isAvailable()) {
			$this->logger->debug('creating APCu instance for low latency', array('app'=>'memcache'));
			return new APCu($prefix);
		} elseif (APC::isAvailable()) {
			$this->logger->debug('creating APC instance for low latency', array('app'=>'memcache'));
			return new APC($prefix);
		} else {
			$this->logger->debug('no low latency cache available', array('app'=>'memcache'));
			return null;
		}
	}

	/**
	 * check if there is a in-server backend available
	 *
	 * @return bool
	 */
	public function isAvailableLowLatency() {
		return XCache::isAvailable() || APCu::isAvailable() || APC::isAvailable();
	}

}
