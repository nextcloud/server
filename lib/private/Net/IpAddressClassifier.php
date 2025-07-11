<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Net;

use IPLib\Address\IPv6;
use IPLib\Factory;
use IPLib\ParseStringFlag;
use Symfony\Component\HttpFoundation\IpUtils;
use function filter_var;

/**
 * Classifier for IP addresses
 *
 * @internal
 */
class IpAddressClassifier {
	private const LOCAL_ADDRESS_RANGES = [
		'100.64.0.0/10', // See RFC 6598
		'192.0.0.0/24', // See RFC 6890
	];

	/**
	 * Check host identifier for local IPv4 and IPv6 address ranges
	 *
	 * Hostnames are not considered local. Use the HostnameClassifier for those.
	 */
	public function isLocalAddress(string $ip): bool {
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
			$ip = (string)($parsedIp->toIPv4() ?? $parsedIp);
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
