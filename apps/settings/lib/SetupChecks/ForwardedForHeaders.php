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
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class ForwardedForHeaders implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private IRequest $request,
	) {
	}

	public function getCategory(): string {
		return 'security';
	}

	public function getName(): string {
		return $this->l10n->t('Forwared for headers');
	}

	public function run(): SetupResult {
		$trustedProxies = $this->config->getSystemValue('trusted_proxies', []);
		$remoteAddress = $this->request->getHeader('REMOTE_ADDR');

		if (!\is_array($trustedProxies)) {
			return SetupResult::error($this->l10n->t('Your trusted_proxies setting is not correctly set, it should be an array.'));
		}

		if (($remoteAddress === '') && ($this->request->getRemoteAddress() === '')) {
			/* Most likely we were called from CLI */
			return SetupResult::info('Your remote address could not be determined.');
		}

		if (empty($trustedProxies) && $this->request->getHeader('X-Forwarded-Host') !== '') {
			return SetupResult::warning(
				$this->l10n->t('The reverse proxy header configuration is incorrect, or you are accessing Nextcloud from a trusted proxy. If not, this is a security issue and can allow an attacker to spoof their IP address as visible to the Nextcloud.'),
				$this->urlGenerator->linkToDocs('admin-reverse-proxy')
			);
		}

		if (\in_array($remoteAddress, $trustedProxies, true) && ($remoteAddress !== '127.0.0.1')) {
			if ($remoteAddress !== $this->request->getRemoteAddress()) {
				/* Remote address was successfuly fixed */
				return SetupResult::success('Working');
			} else {
				return SetupResult::warning(
					$this->l10n->t('The reverse proxy header configuration is incorrect, or you are accessing Nextcloud from a trusted proxy. If not, this is a security issue and can allow an attacker to spoof their IP address as visible to the Nextcloud.'),
					$this->urlGenerator->linkToDocs('admin-reverse-proxy')
				);
			}
		}

		/* Either not enabled or working correctly */
		return SetupResult::success();
	}
}
