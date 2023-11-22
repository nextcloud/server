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
