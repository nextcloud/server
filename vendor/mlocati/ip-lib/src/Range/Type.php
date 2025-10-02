<?php

namespace IPLib\Range;

/**
 * Types of IP address classes.
 */
class Type
{
    /**
     * Unspecified/unknown address.
     *
     * @var int
     */
    const T_UNSPECIFIED = 1;

    /**
     * Reserved/internal use only.
     *
     * @var int
     */
    const T_RESERVED = 2;

    /**
     * Refer to source hosts on "this" network.
     *
     * @var int
     */
    const T_THISNETWORK = 3;

    /**
     * Internet host loopback address.
     *
     * @var int
     */
    const T_LOOPBACK = 4;

    /**
     * Relay anycast address.
     *
     * @var int
     */
    const T_ANYCASTRELAY = 5;

    /**
     * "Limited broadcast" destination address.
     *
     * @var int
     */
    const T_LIMITEDBROADCAST = 6;

    /**
     * Multicast address assignments - Indentify a group of interfaces.
     *
     * @var int
     */
    const T_MULTICAST = 7;

    /**
     * "Link local" address, allocated for communication between hosts on a single link.
     *
     * @var int
     */
    const T_LINKLOCAL = 8;

    /**
     * Link local unicast / Linked-scoped unicast.
     *
     * @var int
     */
    const T_LINKLOCAL_UNICAST = 9;

    /**
     * Discard-Only address.
     *
     * @var int
     */
    const T_DISCARDONLY = 10;

    /**
     * Discard address.
     *
     * @var int
     */
    const T_DISCARD = 11;

    /**
     * For use in private networks.
     *
     * @var int
     */
    const T_PRIVATENETWORK = 12;

    /**
     * Public address.
     *
     * @var int
     */
    const T_PUBLIC = 13;

    /**
     * Carrier-grade NAT address.
     *
     * @var int
     *
     * @since 1.10.0
     */
    const T_CGNAT = 14;

    /**
     * Get the name of a type.
     *
     * @param int $type
     *
     * @return string
     */
    public static function getName($type)
    {
        switch ($type) {
            case static::T_UNSPECIFIED:
                return 'Unspecified/unknown address';
            case static::T_RESERVED:
                 return 'Reserved/internal use only';
            case static::T_THISNETWORK:
                 return 'Refer to source hosts on "this" network';
            case static::T_LOOPBACK:
                 return 'Internet host loopback address';
            case static::T_ANYCASTRELAY:
                 return 'Relay anycast address';
            case static::T_LIMITEDBROADCAST:
                 return '"Limited broadcast" destination address';
            case static::T_MULTICAST:
                 return 'Multicast address assignments - Indentify a group of interfaces';
            case static::T_LINKLOCAL:
                 return '"Link local" address, allocated for communication between hosts on a single link';
            case static::T_LINKLOCAL_UNICAST:
                return 'Link local unicast / Linked-scoped unicast';
            case static::T_DISCARDONLY:
                 return 'Discard only';
            case static::T_DISCARD:
                 return 'Discard';
            case static::T_PRIVATENETWORK:
                 return 'For use in private networks';
            case static::T_PUBLIC:
                 return 'Public address';
            case static::T_CGNAT:
                return 'Carrier-grade NAT';
            default:
                return $type === null ? 'Unknown type' : sprintf('Unknown type (%s)', $type);
        }
    }
}
