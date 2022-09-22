<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021, Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
namespace OC\Http\Client;

use IPLib\Address\IPv6;
use IPLib\Factory;
use IPLib\ParseStringFlag;
use OCP\Http\Client\LocalServerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\IpUtils;

class LocalAddressChecker {
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	public function ThrowIfLocalIp(string $ip) : void {
		$parsedIp = Factory::parseAddressString(
			$ip,
			ParseStringFlag::IPV4_MAYBE_NON_DECIMAL | ParseStringFlag::IPV4ADDRESS_MAYBE_NON_QUAD_DOTTED
		);
		if ($parsedIp === null) {
			/* Not an IP */
			return;
		}
		/* Replace by normalized form */
		if ($parsedIp instanceof IPv6) {
			$ip = (string)($parsedIp->toIPv4() ?? $parsedIp);
		} else {
			$ip = (string)$parsedIp;
		}

		$localRanges = [
			'100.64.0.0/10', // See RFC 6598
			'192.0.0.0/24', // See RFC 6890
		];
		if (
			(bool)filter_var($ip, FILTER_VALIDATE_IP) &&
			(
				!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ||
				IpUtils::checkIp($ip, $localRanges)
			)) {
			$this->logger->warning("Host $ip was not connected to because it violates local access rules");
			throw new LocalServerException('Host violates local access rules');
		}
	}

	public function ThrowIfLocalAddress(string $uri) : void {
		$host = parse_url($uri, PHP_URL_HOST);
		if ($host === false || $host === null) {
			$this->logger->warning("Could not detect any host in $uri");
			throw new LocalServerException('Could not detect any host');
		}

		$host = idn_to_utf8(strtolower(urldecode($host)));
		// Remove brackets from IPv6 addresses
		if (strpos($host, '[') === 0 && substr($host, -1) === ']') {
			$host = substr($host, 1, -1);
		}

		// Disallow local network top-level domains from RFC 6762
		$localTopLevelDomains = ['local','localhost','intranet','internal','private','corp','home','lan'];
		$topLevelDomain = substr((strrchr($host, '.') ?: ''), 1);
		if (in_array($topLevelDomain, $localTopLevelDomains)) {
			$this->logger->warning("Host $host was not connected to because it violates local access rules");
			throw new LocalServerException('Host violates local access rules');
		}

		// Disallow hostname only
		if (substr_count($host, '.') === 0 && !(bool)filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$this->logger->warning("Host $host was not connected to because it violates local access rules");
			throw new LocalServerException('Host violates local access rules');
		}

		$this->ThrowIfLocalIp($host);
	}
}
