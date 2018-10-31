<?php
declare(strict_types=1);

namespace OC\Net;

use OC\Net\IpAddressV4;
use OC\Net\IpAddressV6;

class IpAddressFactory {
	public static function isIpv6(string $address): bool {
		return strpos($address, ':') !== false;
	}

	public static function new($address) {
		if (self::isIpv6($address)) {
			return new IpAddressV6($address);
		} else {
			return new IpAddressV4($address);
		}
	}
}

