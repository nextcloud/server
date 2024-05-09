<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\SetupChecks;

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
		if (in_array(\OC\Memcache\Memcached::class, array_map(fn (string $class) => ltrim($class, '\\'), $caches))) {
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
