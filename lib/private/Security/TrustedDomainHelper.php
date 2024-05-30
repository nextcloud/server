<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Security;

use OC\AppFramework\Http\Request;
use OCP\IConfig;
use OCP\Security\ITrustedDomainHelper;

class TrustedDomainHelper implements ITrustedDomainHelper {
	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * Strips a potential port from a domain (in format domain:port)
	 * @return string $host without appended port
	 */
	private function getDomainWithoutPort(string $host): string {
		$pos = strrpos($host, ':');
		if ($pos !== false) {
			$port = substr($host, $pos + 1);
			if (is_numeric($port)) {
				$host = substr($host, 0, $pos);
			}
		}
		return $host;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isTrustedUrl(string $url): bool {
		$parsedUrl = parse_url($url);
		if (empty($parsedUrl['host'])) {
			return false;
		}

		if (isset($parsedUrl['port']) && $parsedUrl['port']) {
			return $this->isTrustedDomain($parsedUrl['host'] . ':' . $parsedUrl['port']);
		}

		return $this->isTrustedDomain($parsedUrl['host']);
	}

	/**
	 * {@inheritDoc}
	 */
	public function isTrustedDomain(string $domainWithPort): bool {
		// overwritehost is always trusted
		if ($this->config->getSystemValue('overwritehost') !== '') {
			return true;
		}

		$domain = $this->getDomainWithoutPort($domainWithPort);

		// Read trusted domains from config
		$trustedList = $this->config->getSystemValue('trusted_domains', []);
		if (!is_array($trustedList)) {
			return false;
		}

		// Always allow access from localhost
		if (preg_match(Request::REGEX_LOCALHOST, $domain) === 1) {
			return true;
		}
		// Reject malformed domains in any case
		if (str_starts_with($domain, '-') || str_contains($domain, '..')) {
			return false;
		}
		// Match, allowing for * wildcards
		foreach ($trustedList as $trusted) {
			if (gettype($trusted) !== 'string') {
				break;
			}
			$regex = '/^' . implode('[-\.a-zA-Z0-9]*', array_map(function ($v) {
				return preg_quote($v, '/');
			}, explode('*', $trusted))) . '$/i';
			if (preg_match($regex, $domain) || preg_match($regex, $domainWithPort)) {
				return true;
			}
		}
		return false;
	}
}
