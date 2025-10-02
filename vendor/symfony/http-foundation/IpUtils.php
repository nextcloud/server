<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation;

/**
 * Http utility functions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IpUtils
{
    public const PRIVATE_SUBNETS = [
        '127.0.0.0/8',    // RFC1700 (Loopback)
        '10.0.0.0/8',     // RFC1918
        '192.168.0.0/16', // RFC1918
        '172.16.0.0/12',  // RFC1918
        '169.254.0.0/16', // RFC3927
        '0.0.0.0/8',      // RFC5735
        '240.0.0.0/4',    // RFC1112
        '::1/128',        // Loopback
        'fc00::/7',       // Unique Local Address
        'fe80::/10',      // Link Local Address
        '::ffff:0:0/96',  // IPv4 translations
        '::/128',         // Unspecified address
    ];

    private static array $checkedIps = [];

    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
     *
     * @param string|array $ips List of IPs or subnets (can be a string if only a single one)
     */
    public static function checkIp(string $requestIp, string|array $ips): bool
    {
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
    public static function checkIp4(string $requestIp, string $ip): bool
    {
        $cacheKey = $requestIp.'-'.$ip.'-v4';
        if (null !== $cacheValue = self::getCacheResult($cacheKey)) {
            return $cacheValue;
        }

        if (!filter_var($requestIp, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            return self::setCacheResult($cacheKey, false);
        }

        if (str_contains($ip, '/')) {
            [$address, $netmask] = explode('/', $ip, 2);

            if ('0' === $netmask) {
                return self::setCacheResult($cacheKey, false !== filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4));
            }

            if ($netmask < 0 || $netmask > 32) {
                return self::setCacheResult($cacheKey, false);
            }
        } else {
            $address = $ip;
            $netmask = 32;
        }

        if (false === ip2long($address)) {
            return self::setCacheResult($cacheKey, false);
        }

        return self::setCacheResult($cacheKey, 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask));
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
     * @throws \RuntimeException When IPV6 support is not enabled
     */
    public static function checkIp6(string $requestIp, string $ip): bool
    {
        $cacheKey = $requestIp.'-'.$ip.'-v6';
        if (null !== $cacheValue = self::getCacheResult($cacheKey)) {
            return $cacheValue;
        }

        if (!((\extension_loaded('sockets') && \defined('AF_INET6')) || @inet_pton('::1'))) {
            throw new \RuntimeException('Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".');
        }

        // Check to see if we were given a IP4 $requestIp or $ip by mistake
        if (!filter_var($requestIp, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
            return self::setCacheResult($cacheKey, false);
        }

        if (str_contains($ip, '/')) {
            [$address, $netmask] = explode('/', $ip, 2);

            if (!filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
                return self::setCacheResult($cacheKey, false);
            }

            if ('0' === $netmask) {
                return (bool) unpack('n*', @inet_pton($address));
            }

            if ($netmask < 1 || $netmask > 128) {
                return self::setCacheResult($cacheKey, false);
            }
        } else {
            if (!filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
                return self::setCacheResult($cacheKey, false);
            }

            $address = $ip;
            $netmask = 128;
        }

        $bytesAddr = unpack('n*', @inet_pton($address));
        $bytesTest = unpack('n*', @inet_pton($requestIp));

        if (!$bytesAddr || !$bytesTest) {
            return self::setCacheResult($cacheKey, false);
        }

        for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
            $left = $netmask - 16 * ($i - 1);
            $left = ($left <= 16) ? $left : 16;
            $mask = ~(0xFFFF >> $left) & 0xFFFF;
            if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
                return self::setCacheResult($cacheKey, false);
            }
        }

        return self::setCacheResult($cacheKey, true);
    }

    /**
     * Anonymizes an IP/IPv6.
     *
     * Removes the last byte for v4 and the last 8 bytes for v6 IPs
     */
    public static function anonymize(string $ip): string
    {
        $wrappedIPv6 = false;
        if (str_starts_with($ip, '[') && str_ends_with($ip, ']')) {
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

    /**
     * Checks if an IPv4 or IPv6 address is contained in the list of private IP subnets.
     */
    public static function isPrivateIp(string $requestIp): bool
    {
        return self::checkIp($requestIp, self::PRIVATE_SUBNETS);
    }

    private static function getCacheResult(string $cacheKey): ?bool
    {
        if (isset(self::$checkedIps[$cacheKey])) {
            // Move the item last in cache (LRU)
            $value = self::$checkedIps[$cacheKey];
            unset(self::$checkedIps[$cacheKey]);
            self::$checkedIps[$cacheKey] = $value;

            return self::$checkedIps[$cacheKey];
        }

        return null;
    }

    private static function setCacheResult(string $cacheKey, bool $result): bool
    {
        if (1000 < \count(self::$checkedIps)) {
            // stop memory leak if there are many keys
            self::$checkedIps = \array_slice(self::$checkedIps, 500, null, true);
        }

        return self::$checkedIps[$cacheKey] = $result;
    }
}
