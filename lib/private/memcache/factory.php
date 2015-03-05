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
	const NULL_CACHE = '\\OC\\Memcache\\Null';

	/**
	 * @var string $globalPrefix
	 */
	private $globalPrefix;

	/**
	 * @var string $localCacheClass
	 */
	private $localCacheClass;

	/**
	 * @var string $distributedCacheClass
	 */
	private $distributedCacheClass;

	/**
	 * @param string $globalPrefix
	 * @param string|null $localCacheClass
	 * @param string|null $distributedCacheClass
	 */
	public function __construct($globalPrefix,
		$localCacheClass = null, $distributedCacheClass = null)
	{
		$this->globalPrefix = $globalPrefix;

		if (!($localCacheClass && $localCacheClass::isAvailable())) {
			$localCacheClass = self::NULL_CACHE;
		}
		if (!($distributedCacheClass && $distributedCacheClass::isAvailable())) {
			$distributedCacheClass = $localCacheClass;
		}
		$this->localCacheClass = $localCacheClass;
		$this->distributedCacheClass = $distributedCacheClass;
	}

	/**
	 * create a distributed cache instance
	 *
	 * @param string $prefix
	 * @return \OC\Memcache\Cache
	 */
	public function createDistributed($prefix = '') {
		return new $this->distributedCacheClass($this->globalPrefix . '/' . $prefix);
	}

	/**
	 * create a local cache instance
	 *
	 * @param string $prefix
	 * @return \OC\Memcache\Cache
	 */
	public function createLocal($prefix = '') {
		return new $this->localCacheClass($this->globalPrefix . '/' . $prefix);
	}

	/**
	 * @see \OC\Memcache\Factory::createDistributed()
	 * @param string $prefix
	 * @return \OC\Memcache\Cache
	 */
	public function create($prefix = '') {
		return $this->createDistributed($prefix);
	}

	/**
	 * check memcache availability
	 *
	 * @return bool
	 */
	public function isAvailable() {
		return ($this->distributedCacheClass !== self::NULL_CACHE);
	}

	/**
	 * @see \OC\Memcache\Factory::createLocal()
	 * @param string $prefix
	 * @return \OC\Memcache\Cache|null
	 */
	public function createLowLatency($prefix = '') {
		return $this->createLocal($prefix);
	}

	/**
	 * check local memcache availability
	 *
	 * @return bool
	 */
	public function isAvailableLowLatency() {
		return ($this->localCacheClass !== self::NULL_CACHE);
	}
}
