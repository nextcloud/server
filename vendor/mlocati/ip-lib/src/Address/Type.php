<?php

namespace IPLib\Address;

/**
 * Types of IP addresses.
 */
class Type
{
    /**
     * IPv4 address.
     *
     * @var int
     */
    const T_IPv4 = 4;

    /**
     * IPv6 address.
     *
     * @var int
     */
    const T_IPv6 = 6;

    /**
     * Get the name of a type.
     *
     * @param int $type
     *
     * @return string
     *
     * @since 1.1.0
     */
    public static function getName($type)
    {
        switch ($type) {
            case static::T_IPv4:
                return 'IP v4';
            case static::T_IPv6:
                return 'IP v6';
            default:
                return sprintf('Unknown type (%s)', $type);
        }
    }
}
