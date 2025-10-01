<?php

namespace Aws\Credentials;

final class CredentialsUtils
{
    /**
     * Determines whether a given host
     * is a loopback address.
     *
     * @param $host
     *
     * @return bool
     */
    public static function isLoopBackAddress($host): bool
    {
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($host === '::1') {
                return true;
            }

            return false;
        }

        $loopbackStart = ip2long('127.0.0.0');
        $loopbackEnd = ip2long('127.255.255.255');
        $ipLong = ip2long($host);

        return ($ipLong >= $loopbackStart && $ipLong <= $loopbackEnd);
    }
}
