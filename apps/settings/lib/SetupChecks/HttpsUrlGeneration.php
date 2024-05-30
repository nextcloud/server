<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Côme Chilliet <come.chilliet@nextcloud.com>
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
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class HttpsUrlGeneration implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IRequest $request,
	) {
	}

	public function getCategory(): string {
		return 'security';
	}

	public function getName(): string {
		return $this->l10n->t('HTTPS access and URLs');
	}

	public function run(): SetupResult {
		if (!\OC::$CLI && $this->request->getServerProtocol() !== 'https') {
			if (!preg_match('/(?:^(?:localhost|127\.0\.0\.1|::1)|\.onion)$/', $this->request->getInsecureServerHost())) {
				return SetupResult::error(
					$this->l10n->t('Accessing site insecurely via HTTP. You are strongly advised to set up your server to require HTTPS instead. Without it some important web functionality like "copy to clipboard" or "service workers" will not work!'),
					$this->urlGenerator->linkToDocs('admin-security')
				);
			} else {
				return SetupResult::info(
					$this->l10n->t('Accessing site insecurely via HTTP.'),
					$this->urlGenerator->linkToDocs('admin-security')
				);
			}
		}
		$generatedUrl = $this->urlGenerator->getAbsoluteURL('index.php');
		if (!str_starts_with($generatedUrl, 'https://')) {
			if (!\OC::$CLI) {
				return SetupResult::warning(
					$this->l10n->t('You are accessing your instance over a secure connection, however your instance is generating insecure URLs. This likely means that your instance is behind a reverse proxy and the Nextcloud `overwrite*` config values are not set correctly.'),
					$this->urlGenerator->linkToDocs('admin-reverse-proxy')
				);
				/* We were called from CLI so we can't be 100% sure which scenario is applicable */
			} else {
				return SetupResult::info(
					$this->l10n->t('Your instance is generating insecure URLs. If you access your instance over HTTPS, this likely means that your instance is behind a reverse proxy and the Nextcloud `overwrite*` config values are not set correctly.'),
					$this->urlGenerator->linkToDocs('admin-reverse-proxy')
				);
			}
		}
		return SetupResult::success($this->l10n->t('You are accessing your instance over a secure connection, and your instance is generating secure URLs.'));
	}
}
