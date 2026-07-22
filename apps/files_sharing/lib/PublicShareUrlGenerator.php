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
use Psr\Log\LoggerInterface;

class PublicShareUrlGenerator {
	private bool $invalidBaseUrlLogged = false;

	public function __construct(
		private IAppConfig $appConfig,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
	) {
	}

	public function getUrl(string $token): string {
		$baseUrl = $this->getBaseUrl();
		if ($baseUrl === null) {
			return $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', ['token' => $token]);
		}

		// Keep the router as the single source of truth for the share link path,
		// including a potential '/index.php' front controller prefix on instances
		// without pretty URLs, and only swap the instance's webroot for the
		// configured base URL.
		$path = $this->urlGenerator->linkToRoute('files_sharing.sharecontroller.showShare', ['token' => $token]);
		$webroot = $this->urlGenerator->getWebroot();
		if ($webroot !== '' && str_starts_with($path, $webroot)) {
			$path = substr($path, strlen($webroot));
		}

		return $baseUrl . $path;
	}

	private function getBaseUrl(): ?string {
		$baseUrl = trim($this->appConfig->getValueString('core', ConfigLexicon::SHARE_LINK_BASE_URL, ''));
		if ($baseUrl === '') {
			return null;
		}

		if (filter_var($baseUrl, FILTER_VALIDATE_URL) !== false) {
			$parts = parse_url($baseUrl);
			if (is_array($parts)
				&& isset($parts['host'])
				&& in_array($parts['scheme'] ?? '', ['http', 'https'], true)
				&& !isset($parts['user'])
				&& !isset($parts['pass'])
				&& !isset($parts['query'])
				&& !isset($parts['fragment'])) {
				return rtrim($baseUrl, '/');
			}
		}

		// Do not log the configured value itself, it may contain credentials
		if (!$this->invalidBaseUrlLogged) {
			$this->invalidBaseUrlLogged = true;
			$this->logger->warning(
				'Ignoring invalid {configKey} app config value, falling back to the default base URL for public share links. Expected an absolute http(s) URL without credentials, query or fragment.',
				['app' => 'files_sharing', 'configKey' => ConfigLexicon::SHARE_LINK_BASE_URL],
			);
		}

		return null;
	}
}
