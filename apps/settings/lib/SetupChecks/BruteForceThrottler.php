<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Security\Bruteforce\IThrottler;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class BruteForceThrottler implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IRequest $request,
		private IThrottler $throttler,
	) {
	}

	public function getCategory(): string {
		return 'system';
	}

	public function getName(): string {
		return $this->l10n->t('Brute-force Throttle');
	}

	public function run(): SetupResult {
		$address = $this->request->getRemoteAddress();
		if ($address === '') {
			if (\OC::$CLI) {
				/* We were called from CLI */
				return SetupResult::info($this->l10n->t('Your remote address could not be determined.'));
			} else {
				/* Should never happen */
				return SetupResult::error($this->l10n->t('Your remote address could not be determined.'));
			}
		} elseif ($this->throttler->showBruteforceWarning($address)) {
			return SetupResult::error(
				$this->l10n->t('Your remote address was identified as "%s" and is brute-force throttled at the moment slowing down the performance of various requests. If the remote address is not your address this can be an indication that a proxy is not configured correctly.', [$address]),
				$this->urlGenerator->linkToDocs('admin-reverse-proxy')
			);
		} else {
			return SetupResult::success(
				$this->l10n->t('Your remote address "%s" is not brute-force throttled.', [$address])
			);
		}
	}
}
