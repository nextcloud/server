<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OC\Memcache\Redis;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class MemcacheLegacy implements ISetupCheck {
	public function __construct(
		private readonly IL10N $l10n,
		private readonly IConfig $config,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Redis cache');
	}

	#[\Override]
	public function getCategory(): string {
		return 'system';
	}

	#[\Override]
	public function run(): SetupResult {
		if ($this->isLegacyRedisUsed()) {
			return SetupResult::info(
				$this->l10n->t('You are still using the old Redis cache backend. For full support of latest Valkey and Redis features, like clustering and sentinel, please switch to the new KeyValueCache backend.'),
				$this->urlGenerator->linkToDocs('admin-cache')
			);
		} else {
			return SetupResult::success($this->l10n->t('No legacy Redis cache detected'));
		}
	}

	protected function isLegacyRedisUsed(): bool {
		$memcacheDistributedClass = $this->config->getSystemValue('memcache.distributed', null);
		$memcacheLockingClass = $this->config->getSystemValue('memcache.locking', null);

		/** @psalm-suppress DeprecatedClass */
		if ($memcacheDistributedClass === Redis::class || $memcacheLockingClass === Redis::class) {
			return true;
		}
		return false;
	}
}
