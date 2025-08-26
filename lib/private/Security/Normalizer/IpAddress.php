<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Normalizer;

use OCP\IConfig;

/**
 * Class IpAddress is used for normalizing IPv4 and IPv6 addresses in security
 * relevant contexts in Nextcloud.
 *
 * @package OC\Security\Normalizer
 */
class IpAddress {
	/**
	 * @param string $ip IP to normalize
	 */
	public function __construct(
		private string $ip,
	) {
	}

	/**
	 * Return the given subnet for an IPv6 address
	 * Rely on security.ipv6_normalized_subnet_size, defaults to 56
	 */
	private function getIPv6Subnet(string $ip): string {
		if ($ip[0] === '[' && $ip[-1] === ']') { // If IP is with brackets, for example [::1]
			$ip = substr($ip, 1, strlen($ip) - 2);
		}
		$pos = strpos($ip, '%'); // if there is an explicit interface added to the IP, e.g. fe80::ae2d:d1e7:fe1e:9a8d%enp2s0
		if ($pos !== false) {
			$ip = substr($ip, 0, $pos - 1);
		}

		$config = \OCP\Server::get(IConfig::class);
		$maskSize = min(64, $config->getSystemValueInt('security.ipv6_normalized_subnet_size', 56));
		$maskSize = max(32, $maskSize);
		if (PHP_INT_SIZE === 4) {
			if ($maskSize === 64) {
				$value = -1;
			} elseif ($maskSize === 63) {
				$value = PHP_INT_MAX;
			} else {
				$value = (1 << $maskSize - 32) - 1;
			}
			// as long as we support 32bit PHP we cannot use the `P` pack formatter (and not overflow 32bit integer)
			$mask = pack('VVVV', -1, $value, 0, 0);
		} else {
			$mask = pack('VVP', (1 << 32) - 1, (1 << $maskSize - 32) - 1, 0);
		}

		$binary = \inet_pton($ip);
		return inet_ntop($binary & $mask) . '/' . $maskSize;
	}

	/**
	 * Returns the IPv4 address embedded in an IPv6 if applicable.
	 * The detected format is "::ffff:x.x.x.x" using the binary form.
	 *
	 * @return string|null embedded IPv4 string or null if none was found
	 */
	private function getEmbeddedIpv4(string $ipv6): ?string {
		$binary = inet_pton($ipv6);
		if (!$binary) {
			return null;
		}

		$mask = inet_pton('::FFFF:FFFF');
		if (($binary & ~$mask) !== inet_pton('::FFFF:0.0.0.0')) {
			return null;
		}

		return inet_ntop(substr($binary, -4));
	}


	/**
	 * Gets either the /32 (IPv4) or the /56 (default for IPv6) subnet of an IP address
	 */
	public function getSubnet(): string {
		if (filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return $this->ip . '/32';
		}

		$ipv4 = $this->getEmbeddedIpv4($this->ip);
		if ($ipv4 !== null) {
			return $ipv4 . '/32';
		}

		return $this->getIPv6Subnet($this->ip);
	}

	/**
	 * Returns the specified IP address
	 */
	public function __toString(): string {
		return $this->ip;
	}
}
