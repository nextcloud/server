<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OC\Memcache\Memcached;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class MemcacheConfigured implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private ICacheFactory $cacheFactory,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Memcache');
	}

	public function getCategory(): string {
		return 'system';
	}

	public function run(): SetupResult {
		$memcacheDistributedClass = $this->config->getSystemValue('memcache.distributed', null);
		$memcacheLockingClass = $this->config->getSystemValue('memcache.locking', null);
		$memcacheLocalClass = $this->config->getSystemValue('memcache.local', null);
		$caches = array_filter([$memcacheDistributedClass,$memcacheLockingClass,$memcacheLocalClass]);
		if (in_array(Memcached::class, array_map(fn (string $class) => ltrim($class, '\\'), $caches))) {
			// wrong PHP module is installed
			if (extension_loaded('memcache') && !extension_loaded('memcached')) {
				return SetupResult::warning(
					$this->l10n->t('Memcached is configured as distributed cache, but the wrong PHP module ("memcache") is installed. Please install the PHP module "memcached".')
				);
			}
			// required PHP module is missing
			if (!extension_loaded('memcached')) {
				return SetupResult::warning(
					$this->l10n->t('Memcached is configured as distributed cache, but the PHP module "memcached" is not installed. Please install the PHP module "memcached".')
				);
			}
		}
		if ($memcacheLocalClass === null) {
			return SetupResult::info(
				$this->l10n->t('No memory cache has been configured. To enhance performance, please configure a memcache, if available.'),
				$this->urlGenerator->linkToDocs('admin-performance')
			);
		}

		if ($this->cacheFactory->isLocalCacheAvailable()) {
			$random = bin2hex(random_bytes(64));
			$local = $this->cacheFactory->createLocal('setupcheck.local');
			try {
				$local->set('test', $random);
				$local2 = $this->cacheFactory->createLocal('setupcheck.local');
				$actual = $local2->get('test');
				$local->remove('test');
			} catch (\Throwable) {
				$actual = null;
			}

			if ($actual !== $random) {
				return SetupResult::error($this->l10n->t('Failed to write and read a value from local cache.'));
			}
		}

		if ($this->cacheFactory->isAvailable()) {
			$random = bin2hex(random_bytes(64));
			$distributed = $this->cacheFactory->createDistributed('setupcheck');
			try {
				$distributed->set('test', $random);
				$distributed2 = $this->cacheFactory->createDistributed('setupcheck');
				$actual = $distributed2->get('test');
				$distributed->remove('test');
			} catch (\Throwable) {
				$actual = null;
			}

			if ($actual !== $random) {
				return SetupResult::error($this->l10n->t('Failed to write and read a value from distributed cache.'));
			}
		}

		return SetupResult::success($this->l10n->t('Configured'));
	}
}
