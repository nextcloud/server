<?php

namespace IPLib;

/**
 * Flags for the parseString() methods.
 *
 * @since 1.17.0
 */
class ParseStringFlag
{
    /**
     * Use this flag if the input string may include the port.
     *
     * @var int
     */
    const MAY_INCLUDE_PORT = 1;

    /**
     * Use this flag if the input string may include a zone ID.
     *
     * @var int
     */
    const MAY_INCLUDE_ZONEID = 2;

    /**
     * Use this flag if IPv4 addresses may be in decimal/octal/hexadecimal format.
     * This notation is accepted by the implementation of inet_aton and inet_addr of the libc implementation of GNU, Windows and Mac (but not Musl), but not by inet_pton and ip2long.
     *
     * @var int
     *
     * @example 1.08.0x10.0 => 5.0.0.1
     * @example 5.256 => 5.0.1.0
     * @example 5.0.256 => 5.0.1.0
     * @example 123456789 => 7.91.205.21
     */
    const IPV4_MAYBE_NON_DECIMAL = 4;

    /**
     * Use this flag if IPv4 subnet ranges may be in compact form.
     *
     * @example 127/24 => 127.0.0.0/24
     * @example 10/8 => 10.0.0.0/8
     * @example 10/24 => 10.0.0.0/24
     * @example 10.10.10/24 => 10.10.10.0/24
     *
     * @var int
     */
    const IPV4SUBNET_MAYBE_COMPACT = 8;

    /**
     * Use this flag if IPv4 addresses may be in non quad-dotted notation.
     * This notation is accepted by the implementation of inet_aton and inet_addr of the libc implementation of GNU, Windows and Mac (but not Musl), but not by inet_pton and ip2long.
     *
     * @var int
     *
     * @example 5.1 => 5.0.0.1
     * @example 5.256 => 5.0.1.0
     * @example 5.0.256 => 5.0.1.0
     * @example 123456789 => 7.91.205.21
     *
     * @see https://man7.org/linux/man-pages/man3/inet_addr.3.html#DESCRIPTION
     * @see https://www.freebsd.org/cgi/man.cgi?query=inet_net&sektion=3&apropos=0&manpath=FreeBSD+12.2-RELEASE+and+Ports#end
     * @see http://git.musl-libc.org/cgit/musl/tree/src/network/inet_aton.c?h=v1.2.2
     */
    const IPV4ADDRESS_MAYBE_NON_QUAD_DOTTED = 16;

    /**
     * Use this flag if you want to accept parsing IPv4/IPv6 addresses in Reverse DNS Lookup Address format.
     *
     * @var int
     *
     * @since 1.18.0
     *
     * @example 140.13.12.10.in-addr.arpa => 10.12.13.140
     * @example b.a.9.8.7.6.5.0.4.0.0.0.3.0.0.0.2.0.0.0.1.0.0.0.0.0.0.0.1.2.3.4.ip6.arpa => 4321:0:1:2:3:4:567:89ab
     */
    const ADDRESS_MAYBE_RDNS = 32;
}
