<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Markus Goetz <markus@woboq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Vincent Petry <vincent@nextcloud.com>
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

use OCP\ICache;
use OCP\ICacheFactory;
use OCP\ILogger;
use OCP\IMemcache;

class Factory implements ICacheFactory {
	public const NULL_CACHE = NullCache::class;

	/**
	 * @var string $globalPrefix
	 */
	private $globalPrefix;

	/**
	 * @var ILogger $logger
	 */
	private $logger;

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

	/** @var string */
	private $logFile;

	/**
	 * @param string $globalPrefix
	 * @param ILogger $logger
	 * @param string|null $localCacheClass
	 * @param string|null $distributedCacheClass
	 * @param string|null $lockingCacheClass
	 * @param string $logFile
	 */
	public function __construct(string $globalPrefix, ILogger $logger,
		$localCacheClass = null, $distributedCacheClass = null, $lockingCacheClass = null, string $logFile = '') {
		$this->logger = $logger;
		$this->logFile = $logFile;
		$this->globalPrefix = $globalPrefix;

		if (!$localCacheClass) {
			$localCacheClass = self::NULL_CACHE;
		}
		if (!$distributedCacheClass) {
			$distributedCacheClass = $localCacheClass;
		}

		$missingCacheMessage = 'Memcache {class} not available for {use} cache';
		$missingCacheHint = 'Is the matching PHP module installed and enabled?';
		if (!class_exists($localCacheClass) || !$localCacheClass::isAvailable()) {
			throw new \OCP\HintException(strtr($missingCacheMessage, [
				'{class}' => $localCacheClass, '{use}' => 'local'
			]), $missingCacheHint);
		}
		if (!class_exists($distributedCacheClass) || !$distributedCacheClass::isAvailable()) {
			throw new \OCP\HintException(strtr($missingCacheMessage, [
				'{class}' => $distributedCacheClass, '{use}' => 'distributed'
			]), $missingCacheHint);
		}
		if (!($lockingCacheClass && class_exists($lockingCacheClass) && $lockingCacheClass::isAvailable())) {
			// don't fallback since the fallback might not be suitable for storing lock
			$lockingCacheClass = self::NULL_CACHE;
		}

		$this->localCacheClass = $localCacheClass;
		$this->distributedCacheClass = $distributedCacheClass;
		$this->lockingCacheClass = $lockingCacheClass;
	}

	/**
	 * create a cache instance for storing locks
	 *
	 * @param string $prefix
	 * @return IMemcache
	 */
	public function createLocking(string $prefix = ''): IMemcache {
		return new $this->lockingCacheClass($this->globalPrefix . '/' . $prefix, $this->logFile);
	}

	/**
	 * create a distributed cache instance
	 *
	 * @param string $prefix
	 * @return ICache
	 */
	public function createDistributed(string $prefix = ''): ICache {
		return new $this->distributedCacheClass($this->globalPrefix . '/' . $prefix, $this->logFile);
	}

	/**
	 * create a local cache instance
	 *
	 * @param string $prefix
	 * @return ICache
	 */
	public function createLocal(string $prefix = ''): ICache {
		return new $this->localCacheClass($this->globalPrefix . '/' . $prefix, $this->logFile);
	}

	/**
	 * check memcache availability
	 *
	 * @return bool
	 */
	public function isAvailable(): bool {
		return ($this->distributedCacheClass !== self::NULL_CACHE);
	}

	/**
	 * @see \OC\Memcache\Factory::createLocal()
	 * @param string $prefix
	 * @return ICache
	 */
	public function createLowLatency(string $prefix = ''): ICache {
		return $this->createLocal($prefix);
	}

	/**
	 * Check if a local memory cache backend is available
	 *
	 * @return bool
	 */
	public function isLocalCacheAvailable(): bool {
		return ($this->localCacheClass !== self::NULL_CACHE);
	}
}
