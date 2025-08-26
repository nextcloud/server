<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OC\SystemConfig;
use OCP\Cache\CappedMemoryCache;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IMemcache;
use OCP\Profiler\IProfiler;
use OCP\ServerVersion;
use Psr\Log\LoggerInterface;

class Factory implements ICacheFactory {
	public const NULL_CACHE = NullCache::class;

	protected ?string $globalPrefix = null;
	/**
	 * @var class-string<ICache> $localCacheClass
	 */
	protected string $localCacheClass;

	/**
	 * @var class-string<ICache> $distributedCacheClass
	 */
	protected string $distributedCacheClass;

	/**
	 * @var class-string<IMemcache> $lockingCacheClass
	 */
	protected string $lockingCacheClass;

	/**
	 * @param ?class-string<ICache> $localCacheClass
	 * @param ?class-string<ICache> $distributedCacheClass
	 * @param ?class-string<IMemcache> $lockingCacheClass
	 */
	public function __construct(
		protected LoggerInterface $logger,
		protected IProfiler $profiler,
		protected ServerVersion $serverVersion,
		?string $localCacheClass = null,
		?string $distributedCacheClass = null,
		?string $lockingCacheClass = null,
		protected string $logFile = '',
	) {
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
		if (!class_exists($localCacheClass)
			|| !is_a($localCacheClass, ICache::class, true)
			|| !$localCacheClass::isAvailable()
		) {
			if (\OC::$CLI && !defined('PHPUNIT_RUN') && $localCacheClass === APCu::class) {
				// CLI should not fail if APCu is not available but fallback to NullCache.
				// This can be the case if APCu is used without apc.enable_cli=1.
				// APCu however cannot be shared between PHP instances (CLI and web) anyway.
				$localCacheClass = self::NULL_CACHE;
			} else {
				throw new \OCP\HintException(strtr($missingCacheMessage, [
					'{class}' => $localCacheClass, '{use}' => 'local'
				]), $missingCacheHint);
			}
		}

		if (!class_exists($distributedCacheClass)
			|| !is_a($distributedCacheClass, ICache::class, true)
			|| !$distributedCacheClass::isAvailable()
		) {
			if (\OC::$CLI && !defined('PHPUNIT_RUN') && $distributedCacheClass === APCu::class) {
				// CLI should not fail if APCu is not available but fallback to NullCache.
				// This can be the case if APCu is used without apc.enable_cli=1.
				// APCu however cannot be shared between Nextcloud (PHP) instances anyway.
				$distributedCacheClass = self::NULL_CACHE;
			} else {
				throw new \OCP\HintException(strtr($missingCacheMessage, [
					'{class}' => $distributedCacheClass, '{use}' => 'distributed'
				]), $missingCacheHint);
			}
		}

		if (!$lockingCacheClass
			|| !class_exists($lockingCacheClass)
			|| !is_a($lockingCacheClass, IMemcache::class, true)
			|| !$lockingCacheClass::isAvailable()
		) {
			// don't fall back since the fallback might not be suitable for storing lock
			$lockingCacheClass = self::NULL_CACHE;
		}
		/** @var class-string<IMemcache> */
		$lockingCacheClass = ltrim($lockingCacheClass, '\\');

		$this->localCacheClass = $localCacheClass;
		$this->distributedCacheClass = $distributedCacheClass;
		$this->lockingCacheClass = $lockingCacheClass;
	}

	protected function getGlobalPrefix(): string {
		if ($this->globalPrefix === null) {
			$config = \OCP\Server::get(SystemConfig::class);
			$versions = [];
			if ($config->getValue('installed', false)) {
				$appConfig = \OCP\Server::get(IAppConfig::class);
				$versions = $appConfig->getAppInstalledVersions();
			}
			$versions['core'] = implode('.', $this->serverVersion->getVersion());
			$this->globalPrefix = hash('xxh128', implode(',', $versions));
		}
		return $this->globalPrefix;
	}

	/**
	 * Override the global prefix for a specific closure.
	 * This should only be used internally for bootstrapping purpose!
	 *
	 * @param \Closure $closure - The closure with the cache factory as the first parameter
	 */
	public function withServerVersionPrefix(\Closure $closure): void {
		$backupPrefix = $this->globalPrefix;
		$this->globalPrefix = hash('xxh128', implode('.', $this->serverVersion->getVersion()));
		$closure($this);
		$this->globalPrefix = $backupPrefix;
	}

	/**
	 * create a cache instance for storing locks
	 *
	 * @param string $prefix
	 * @return IMemcache
	 */
	public function createLocking(string $prefix = ''): IMemcache {
		$cache = new $this->lockingCacheClass($this->getGlobalPrefix() . '/' . $prefix);
		if ($this->lockingCacheClass === Redis::class) {
			if ($this->profiler->isEnabled()) {
				// We only support the profiler with Redis
				$cache = new ProfilerWrapperCache($cache, 'Locking');
				$this->profiler->add($cache);
			}

			if ($this->logFile !== '' && is_writable(dirname($this->logFile)) && (!file_exists($this->logFile) || is_writable($this->logFile))) {
				$cache = new LoggerWrapperCache($cache, $this->logFile);
			}
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
		$cache = new $this->distributedCacheClass($this->getGlobalPrefix() . '/' . $prefix);
		if ($this->distributedCacheClass === Redis::class) {
			if ($this->profiler->isEnabled()) {
				// We only support the profiler with Redis
				$cache = new ProfilerWrapperCache($cache, 'Distributed');
				$this->profiler->add($cache);
			}

			if ($this->logFile !== '' && is_writable(dirname($this->logFile)) && (!file_exists($this->logFile) || is_writable($this->logFile))) {
				$cache = new LoggerWrapperCache($cache, $this->logFile);
			}
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
		$cache = new $this->localCacheClass($this->getGlobalPrefix() . '/' . $prefix);
		if ($this->localCacheClass === Redis::class) {
			if ($this->profiler->isEnabled()) {
				// We only support the profiler with Redis
				$cache = new ProfilerWrapperCache($cache, 'Local');
				$this->profiler->add($cache);
			}

			if ($this->logFile !== '' && is_writable(dirname($this->logFile)) && (!file_exists($this->logFile) || is_writable($this->logFile))) {
				$cache = new LoggerWrapperCache($cache, $this->logFile);
			}
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

	public function clearAll(): void {
		$this->createLocal()->clear();
		$this->createDistributed()->clear();
		$this->createLocking()->clear();
		$this->createInMemory()->clear();
	}
}
