<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Johannes Ernst <jernst@indiecomputing.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Security;

use OC\AppFramework\Http\Request;
use OCP\IConfig;
use OCP\Security\ITrustedDomainHelper;

class TrustedDomainHelper implements ITrustedDomainHelper {
	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Strips a potential port from a domain (in format domain:port)
	 * @param string $host
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
		if (strpos($domain, '-') === 0 || strpos($domain, '..') !== false) {
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
