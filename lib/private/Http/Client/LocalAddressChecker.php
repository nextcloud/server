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

use OCP\ILogger;
use OCP\Http\Client\LocalServerException;

class LocalAddressChecker {
	/** @var ILogger */
	private $logger;

	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	public function ThrowIfLocalIp(string $ip) : void {
		if ((bool)filter_var($ip, FILTER_VALIDATE_IP) && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			$this->logger->warning("Host $ip was not connected to because it violates local access rules");
			throw new LocalServerException('Host violates local access rules');
		}

		// Also check for IPv6 IPv4 nesting, because that's not covered by filter_var
		if ((bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && substr_count($ip, '.') > 0) {
			$delimiter = strrpos($ip, ':'); // Get last colon
			$ipv4Address = substr($ip, $delimiter + 1);

			if (!filter_var($ipv4Address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
				$this->logger->warning("Host $ip was not connected to because it violates local access rules");
				throw new LocalServerException('Host violates local access rules');
			}
		}
	}

	public function ThrowIfLocalAddress(string $uri) : void {
		$host = parse_url($uri, PHP_URL_HOST);
		if ($host === false || $host === null) {
			$this->logger->warning("Could not detect any host in $uri");
			throw new LocalServerException('Could not detect any host');
		}

		$host = strtolower($host);
		// Remove brackets from IPv6 addresses
		if (strpos($host, '[') === 0 && substr($host, -1) === ']') {
			$host = substr($host, 1, -1);
		}

		// Disallow localhost and local network
		if ($host === 'localhost' || substr($host, -6) === '.local' || substr($host, -10) === '.localhost') {
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
