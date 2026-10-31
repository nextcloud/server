<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpSAPI implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('PHP-FPM process limit');
	}

	#[\Override]
	public function getCategory(): string {
		return 'php';
	}

	#[\Override]
	public function run(): SetupResult {
		if (php_sapi_name() !== 'fpm-fcgi') {
			return SetupResult::success();
		}

		if (!function_exists('fpm_get_status')) {
			return SetupResult::warning(
				$this->l10n->t('PHP-FPM status is unavailable, so the max children check could not be completed.'),
				$this->urlGenerator->linkToDocs('admin-php-fpm')
			);
		}

		$status = fpm_get_status();
		if ($status === false) {
			return SetupResult::warning(
				$this->l10n->t('PHP-FPM status could not be read, so the max children check could not be completed.'),
				$this->urlGenerator->linkToDocs('admin-php-fpm')
			);
		}

		$maxChildrenReached = $status['max-children-reached'] ?? null;
		$maxActiveProcesses = $status['max-active-processes'] ?? null;
		$startTimeEpoch = $status['start-time'] ?? null;

		if (!is_int($maxChildrenReached) || !is_int($maxActiveProcesses) || !is_int($startTimeEpoch)) {
			return SetupResult::warning(
				$this->l10n->t('PHP-FPM status is incomplete, so the max children check could not be completed.'),
				$this->urlGenerator->linkToDocs('admin-php-fpm')
			);
		}

		if ($maxChildrenReached > 0) {
			$startTime = date('Y-m-d H:i:s T', $startTimeEpoch);
			$timesReached = $this->l10n->n('once', '%n times', $maxChildrenReached);

			$message = $this->l10n->t(
				'PHP-FPM reached its configured "pm.max_children" limit %s since its last restart at %s (peak active processes: %s).',
				[$timesReached, $startTime, (string)$maxActiveProcesses],
			);
			$message .= ' ' . $this->l10n->t(
				'When the pool is saturated, additional requests may be queued, time out, or fail. This can cause slow responses, client connection errors, or 502/504 gateway errors.'
			);
			$message .= "\n\n" . $this->l10n->t(
				'The counter resets when PHP-FPM restarts. If saturation is ongoing or recurring, review pool configuration and server capacity and consider increasing "pm.max_children".'
			);

			return SetupResult::warning(
				$message,
				$this->urlGenerator->linkToDocs('admin-php-fpm'),
			);
		}

		return SetupResult::success();
	}
}
