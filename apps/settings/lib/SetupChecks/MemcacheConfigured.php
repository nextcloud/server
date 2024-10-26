<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OC\Memcache\Memcached;
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
		return SetupResult::success($this->l10n->t('Configured'));
	}
}
