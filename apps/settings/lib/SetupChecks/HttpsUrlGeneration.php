<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
