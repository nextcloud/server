<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Net;

use IPLib\Address\IPv4;
use IPLib\Address\IPv6;
use IPLib\Factory;
use IPLib\ParseStringFlag;
use IPLib\Range\RangeInterface;
use IPLib\Range\Subnet;
use Symfony\Component\HttpFoundation\IpUtils;
use function filter_var;

/**
 * Classifier for IP addresses
 *
 * @internal
 */
class IpAddressClassifier {
	private const array LOCAL_ADDRESS_RANGES = [
		'100.64.0.0/10', // See RFC 6598
		'192.0.0.0/24', // See RFC 6890
	];

	private RangeInterface $nat64Range;
	private RangeInterface $rfc8215;
	private RangeInterface $teredo;
	private RangeInterface $ipv4Compatible;

	public function __construct() {
		$this->nat64Range = Subnet::parseString('64:ff9b::/96');
		$this->rfc8215 = Subnet::parseString('64:ff9b:1::/48');
		$this->teredo = Subnet::parseString('2001::/32');
		$this->ipv4Compatible = Subnet::parseString('::0:0/96');
	}

	/**
	 * Get the ipv4 that an ipv6 address maps to, if any.
	 *
	 * Note that this is not just ipv6 representations of ipv4 addresses,
	 * but also any NAT or proxy style translation addresses
	 */
	public function getMappedIpv4(IPv6 $ip): ?IPv4 {
		$ipv4 = $ip->toIPv4();
		$ipv6Bytes = $ip->getBytes();
		if ($ipv4) {
			return $ipv4;
		} elseif ($this->nat64Range->contains($ip)) {
			return IPv4::fromBytes(array_slice($ipv6Bytes, -4, 4));
		} elseif ($this->ipv4Compatible->contains($ip)) {
			return IPv4::fromBytes(array_slice($ipv6Bytes, -4, 4));
		} elseif ($this->teredo->contains($ip)) {
			$xorBytes = array_slice($ipv6Bytes, -4, 4);
			return IPv4::fromBytes(array_map(fn (int $byte) => $byte ^ 0xFF, $xorBytes));
		}

		return null;
	}

	/**
	 * Check host identifier for local IPv4 and IPv6 address ranges
	 *
	 * Hostnames are not considered local. Use the HostnameClassifier for those.
	 */
	public function isLocalAddress(string $ip): bool {
		$ip = rtrim($ip, '.');
		$parsedIp = Factory::parseAddressString(
			$ip,
			ParseStringFlag::IPV4_MAYBE_NON_DECIMAL | ParseStringFlag::IPV4ADDRESS_MAYBE_NON_QUAD_DOTTED | ParseStringFlag::MAY_INCLUDE_ZONEID
		);
		if ($parsedIp === null) {
			/* Not an IP */
			return false;
		}
		/* Replace by normalized form */
		if ($parsedIp instanceof IPv6) {
			// rfc8215 is a generic reservation for ipv6/ipv4 translation mechanisms,
			// no assumptions can be made about how ipv4 addresses are encoded within.
			//
			// Thus the only thing we can do is treat them all as local
			if ($this->rfc8215->contains($parsedIp)) {
				return true;
			}
			$ipv4 = $this->getMappedIpv4($parsedIp);
			if ($ipv4) {
				$ip = (string)$ipv4;
			} else {
				$ip = (string)$parsedIp;
			}
		} else {
			$ip = (string)$parsedIp;
		}

		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			/* Range address */
			return true;
		}
		if (IpUtils::checkIp($ip, self::LOCAL_ADDRESS_RANGES)) {
			/* Within local range */
			return true;
		}

		return false;
	}
}
