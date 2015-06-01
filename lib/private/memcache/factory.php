<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Markus Goetz <markus@woboq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
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
	 * @var string $lockingCacheClass
	 */
	private $lockingCacheClass;

	/**
	 * @param string $globalPrefix
	 * @param string|null $localCacheClass
	 * @param string|null $distributedCacheClass
	 * @param string|null $lockingCacheClass
	 */
	public function __construct($globalPrefix,
		$localCacheClass = null, $distributedCacheClass = null, $lockingCacheClass = null)
	{
		$this->globalPrefix = $globalPrefix;

		if (!($localCacheClass && $localCacheClass::isAvailable())) {
			$localCacheClass = self::NULL_CACHE;
		}
		if (!($distributedCacheClass && $distributedCacheClass::isAvailable())) {
			$distributedCacheClass = $localCacheClass;
		}
		if (!($lockingCacheClass && $lockingCacheClass::isAvailable())) {
			// dont fallback since the fallback might not be suitable for storing lock
			$lockingCacheClass = '\OC\Memcache\Null';
		}
		$this->localCacheClass = $localCacheClass;
		$this->distributedCacheClass = $distributedCacheClass;
		$this->lockingCacheClass = $lockingCacheClass;
	}

	/**
	 * create a cache instance for storing locks
	 *
	 * @param string $prefix
	 * @return \OCP\IMemcache
	 */
	public function createLocking($prefix = '') {
		return new $this->lockingCacheClass($this->globalPrefix . '/' . $prefix);
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
