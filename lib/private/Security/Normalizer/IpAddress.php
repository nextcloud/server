<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Normalizer;

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
	 * Return the given subnet for an IPv6 address (48 first bits)
	 */
	private function getIPv6Subnet(string $ip): string {
		if ($ip[0] === '[' && $ip[-1] === ']') { // If IP is with brackets, for example [::1]
			$ip = substr($ip, 1, strlen($ip) - 2);
		}
		$pos = strpos($ip, '%'); // if there is an explicit interface added to the IP, e.g. fe80::ae2d:d1e7:fe1e:9a8d%enp2s0
		if ($pos !== false) {
			$ip = substr($ip, 0, $pos - 1);
		}

		$binary = \inet_pton($ip);
		$mask = inet_pton('FFFF:FFFF:FFFF::');

		return inet_ntop($binary & $mask) . '/48';
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
	 * Gets either the /32 (IPv4) or the /48 (IPv6) subnet of an IP address
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
