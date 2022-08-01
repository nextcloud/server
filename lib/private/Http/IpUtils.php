<?php

/*
 * This file comes from Symfony. Copied here to avoid backporting a new dependency.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * SPDX-License-Identifier: MIT
 */

namespace OC\Http;

/**
 * Http utility functions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IpUtils {
	/**
	 * @var array<string,bool>
	 */
	private static $checkedIps = [];

	/**
	 * This class should not be instantiated.
	 */
	private function __construct() {
	}

	/**
	 * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
	 *
	 * @param string|array $ips List of IPs or subnets (can be a string if only a single one)
	 *
	 * @return bool
	 */
	public static function checkIp(string $requestIp, $ips) {
		if (!\is_array($ips)) {
			$ips = [$ips];
		}

		$method = substr_count($requestIp, ':') > 1 ? 'checkIp6' : 'checkIp4';

		foreach ($ips as $ip) {
			if (self::$method($requestIp, $ip)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Compares two IPv4 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * @param string $ip IPv4 address or subnet in CIDR notation
	 *
	 * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet
	 */
	public static function checkIp4(string $requestIp, string $ip) {
		$cacheKey = $requestIp.'-'.$ip;
		if (isset(self::$checkedIps[$cacheKey])) {
			return self::$checkedIps[$cacheKey];
		}

		if (!filter_var($requestIp, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
			return self::$checkedIps[$cacheKey] = false;
		}

		if (str_contains($ip, '/')) {
			[$address, $netmask] = explode('/', $ip, 2);

			if ('0' === $netmask) {
				return self::$checkedIps[$cacheKey] = (bool)filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4);
			}

			$netmask = (int)$netmask;

			if ($netmask < 0 || $netmask > 32) {
				return self::$checkedIps[$cacheKey] = false;
			}
		} else {
			$address = $ip;
			$netmask = 32;
		}

		if (false === ip2long($address)) {
			return self::$checkedIps[$cacheKey] = false;
		}

		return self::$checkedIps[$cacheKey] = 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask);
	}

	/**
	 * Compares two IPv6 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * @author David Soria Parra <dsp at php dot net>
	 *
	 * @see https://github.com/dsp/v6tools
	 *
	 * @param string $ip IPv6 address or subnet in CIDR notation
	 *
	 * @return bool
	 *
	 * @throws \RuntimeException When IPV6 support is not enabled
	 */
	public static function checkIp6(string $requestIp, string $ip) {
		$cacheKey = $requestIp.'-'.$ip;
		if (isset(self::$checkedIps[$cacheKey])) {
			return self::$checkedIps[$cacheKey];
		}

		if (!((\extension_loaded('sockets') && \defined('AF_INET6')) || @inet_pton('::1'))) {
			throw new \RuntimeException('Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".');
		}

		if (str_contains($ip, '/')) {
			[$address, $netmask] = explode('/', $ip, 2);

			if ('0' === $netmask) {
				return (bool) unpack('n*', @inet_pton($address));
			}

			$netmask = (int)$netmask;

			if ($netmask < 1 || $netmask > 128) {
				return self::$checkedIps[$cacheKey] = false;
			}
		} else {
			$address = $ip;
			$netmask = 128;
		}

		$bytesAddr = unpack('n*', @inet_pton($address));
		$bytesTest = unpack('n*', @inet_pton($requestIp));

		if (!$bytesAddr || !$bytesTest) {
			return self::$checkedIps[$cacheKey] = false;
		}

		for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
			$left = $netmask - 16 * ($i - 1);
			$left = ($left <= 16) ? $left : 16;
			$mask = ~(0xFFFF >> $left) & 0xFFFF;
			if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
				return self::$checkedIps[$cacheKey] = false;
			}
		}

		return self::$checkedIps[$cacheKey] = true;
	}

	/**
	 * Anonymizes an IP/IPv6.
	 *
	 * Removes the last byte for v4 and the last 8 bytes for v6 IPs
	 */
	public static function anonymize(string $ip): string {
		$wrappedIPv6 = false;
		if ('[' === substr($ip, 0, 1) && ']' === substr($ip, -1, 1)) {
			$wrappedIPv6 = true;
			$ip = substr($ip, 1, -1);
		}

		$packedAddress = inet_pton($ip);
		if (4 === \strlen($packedAddress)) {
			$mask = '255.255.255.0';
		} elseif ($ip === inet_ntop($packedAddress & inet_pton('::ffff:ffff:ffff'))) {
			$mask = '::ffff:ffff:ff00';
		} elseif ($ip === inet_ntop($packedAddress & inet_pton('::ffff:ffff'))) {
			$mask = '::ffff:ff00';
		} else {
			$mask = 'ffff:ffff:ffff:ffff:0000:0000:0000:0000';
		}
		$ip = inet_ntop($packedAddress & inet_pton($mask));

		if ($wrappedIPv6) {
			$ip = '['.$ip.']';
		}

		return $ip;
	}
}
