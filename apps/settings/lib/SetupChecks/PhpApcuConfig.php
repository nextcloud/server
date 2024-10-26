<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OC\Memcache\APCu;
use OCP\IConfig;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpApcuConfig implements ISetupCheck {
	public const USAGE_RATE_WARNING = 90;
	public const AGE_WARNING = 3600 * 8;

	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
	) {
	}

	public function getCategory(): string {
		return 'php';
	}

	public function getName(): string {
		return $this->l10n->t('PHP APCu configuration');
	}

	public function run(): SetupResult {
		$localIsApcu = ltrim($this->config->getSystemValueString('memcache.local'), '\\') === APCu::class;
		$distributedIsApcu = ltrim($this->config->getSystemValueString('memcache.distributed'), '\\') === APCu::class;
		if (!$localIsApcu && !$distributedIsApcu) {
			return SetupResult::success();
		}

		if (!APCu::isAvailable()) {
			return SetupResult::success();
		}

		$cache = apcu_cache_info(true);
		$mem = apcu_sma_info(true);
		if ($cache === false || $mem === false) {
			return SetupResult::success();
		}

		$expunges = $cache['expunges'];
		$memSize = $mem['num_seg'] * $mem['seg_size'];
		$memAvailable = $mem['avail_mem'];
		$memUsed = $memSize - $memAvailable;
		$usageRate = round($memUsed / $memSize * 100, 0);
		$elapsed = max(time() - $cache['start_time'], 1);

		if ($expunges > 0 && $elapsed < self::AGE_WARNING) {
			return SetupResult::warning($this->l10n->t('Your APCu cache has been running full, consider increasing the apc.shm_size php setting.'));
		}

		if ($usageRate > self::USAGE_RATE_WARNING) {
			return SetupResult::warning($this->l10n->t('Your APCu cache is almost full at %s%%, consider increasing the apc.shm_size php setting.', [$usageRate]));
		}

		return SetupResult::success();
	}
}
