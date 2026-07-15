<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing;

use OC\Core\AppInfo\ConfigLexicon;
use OCP\IAppConfig;
use OCP\IURLGenerator;

class PublicShareUrlGenerator {
	public function __construct(
		private IAppConfig $appConfig,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getUrl(string $token): string {
		$baseUrl = $this->getBaseUrl();
		if ($baseUrl === null) {
			return $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', ['token' => $token]);
		}

		return $baseUrl . '/s/' . rawurlencode($token);
	}

	private function getBaseUrl(): ?string {
		$baseUrl = $this->appConfig->getValueString('core', ConfigLexicon::SHARE_LINK_BASE_URL, '');
		if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
			return null;
		}

		$parts = parse_url($baseUrl);
		if (!is_array($parts)
			|| !isset($parts['host'])
			|| !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
			|| isset($parts['user'])
			|| isset($parts['pass'])
			|| isset($parts['query'])
			|| isset($parts['fragment'])) {
			return null;
		}

		return rtrim($baseUrl, '/');
	}
}
