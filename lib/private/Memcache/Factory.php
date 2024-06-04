<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OCP\Cache\CappedMemoryCache;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IMemcache;
use OCP\Profiler\IProfiler;
use Psr\Log\LoggerInterface;

class Factory implements ICacheFactory {
	public const NULL_CACHE = NullCache::class;

	private string $globalPrefix;

	private LoggerInterface $logger;

	/**
	 * @var ?class-string<ICache> $localCacheClass
	 */
	private ?string $localCacheClass;

	/**
	 * @var ?class-string<ICache> $distributedCacheClass
	 */
	private ?string $distributedCacheClass;

	/**
	 * @var ?class-string<IMemcache> $lockingCacheClass
	 */
	private ?string $lockingCacheClass;

	private string $logFile;

	private IProfiler $profiler;

	/**
	 * @param string $globalPrefix
	 * @param LoggerInterface $logger
	 * @param ?class-string<ICache> $localCacheClass
	 * @param ?class-string<ICache> $distributedCacheClass
	 * @param ?class-string<IMemcache> $lockingCacheClass
	 * @param string $logFile
	 */
	public function __construct(string $globalPrefix, LoggerInterface $logger, IProfiler $profiler,
		?string $localCacheClass = null, ?string $distributedCacheClass = null, ?string $lockingCacheClass = null, string $logFile = '') {
		$this->logFile = $logFile;
		$this->globalPrefix = $globalPrefix;

		if (!$localCacheClass) {
			$localCacheClass = self::NULL_CACHE;
		}
		$localCacheClass = ltrim($localCacheClass, '\\');
		if (!$distributedCacheClass) {
			$distributedCacheClass = $localCacheClass;
		}
		$distributedCacheClass = ltrim($distributedCacheClass, '\\');

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
			// don't fall back since the fallback might not be suitable for storing lock
			$lockingCacheClass = self::NULL_CACHE;
		}
		$lockingCacheClass = ltrim($lockingCacheClass, '\\');

		$this->localCacheClass = $localCacheClass;
		$this->distributedCacheClass = $distributedCacheClass;
		$this->lockingCacheClass = $lockingCacheClass;
		$this->profiler = $profiler;
	}

	/**
	 * create a cache instance for storing locks
	 *
	 * @param string $prefix
	 * @return IMemcache
	 */
	public function createLocking(string $prefix = ''): IMemcache {
		assert($this->lockingCacheClass !== null);
		$cache = new $this->lockingCacheClass($this->globalPrefix . '/' . $prefix);
		if ($this->lockingCacheClass === Redis::class && $this->profiler->isEnabled()) {
			// We only support the profiler with Redis
			$cache = new ProfilerWrapperCache($cache, 'Locking');
			$this->profiler->add($cache);
		}

		if ($this->lockingCacheClass === Redis::class &&
			$this->logFile !== '' && is_writable(dirname($this->logFile)) && (!file_exists($this->logFile) || is_writable($this->logFile))) {
			$cache = new LoggerWrapperCache($cache, $this->logFile);
		}
		return $cache;
	}

	/**
	 * create a distributed cache instance
	 *
	 * @param string $prefix
	 * @return ICache
	 */
	public function createDistributed(string $prefix = ''): ICache {
		assert($this->distributedCacheClass !== null);
		$cache = new $this->distributedCacheClass($this->globalPrefix . '/' . $prefix);
		if ($this->distributedCacheClass === Redis::class && $this->profiler->isEnabled()) {
			// We only support the profiler with Redis
			$cache = new ProfilerWrapperCache($cache, 'Distributed');
			$this->profiler->add($cache);
		}

		if ($this->distributedCacheClass === Redis::class && $this->logFile !== ''
			&& is_writable(dirname($this->logFile)) && (!file_exists($this->logFile) || is_writable($this->logFile))) {
			$cache = new LoggerWrapperCache($cache, $this->logFile);
		}
		return $cache;
	}

	/**
	 * create a local cache instance
	 *
	 * @param string $prefix
	 * @return ICache
	 */
	public function createLocal(string $prefix = ''): ICache {
		assert($this->localCacheClass !== null);
		$cache = new $this->localCacheClass($this->globalPrefix . '/' . $prefix);
		if ($this->localCacheClass === Redis::class && $this->profiler->isEnabled()) {
			// We only support the profiler with Redis
			$cache = new ProfilerWrapperCache($cache, 'Local');
			$this->profiler->add($cache);
		}

		if ($this->localCacheClass === Redis::class && $this->logFile !== ''
			&& is_writable(dirname($this->logFile)) && (!file_exists($this->logFile) || is_writable($this->logFile))) {
			$cache = new LoggerWrapperCache($cache, $this->logFile);
		}
		return $cache;
	}

	/**
	 * check memcache availability
	 *
	 * @return bool
	 */
	public function isAvailable(): bool {
		return $this->distributedCacheClass !== self::NULL_CACHE;
	}

	public function createInMemory(int $capacity = 512): ICache {
		return new CappedMemoryCache($capacity);
	}

	/**
	 * Check if a local memory cache backend is available
	 *
	 * @return bool
	 */
	public function isLocalCacheAvailable(): bool {
		return $this->localCacheClass !== self::NULL_CACHE;
	}
}
