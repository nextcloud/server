<?php

namespace IPLib\Range;

use IPLib\Address\AddressInterface;

/**
 * Interface of all the range types.
 */
interface RangeInterface
{
    /**
     * Get the short string representation of this address.
     *
     * @return string
     */
    public function __toString();

    /**
     * Get the string representation of this address.
     *
     * @param bool $long set to true to have a long/full representation, false otherwise
     *
     * @return string
     *
     * @example If $long is true, you'll get '0000:0000:0000:0000:0000:0000:0000:0001/128', '::1/128' otherwise.
     */
    public function toString($long = false);

    /**
     * Get the type of the IP addresses contained in this range.
     *
     * @return int One of the \IPLib\Address\Type::T_... constants
     */
    public function getAddressType();

    /**
     * Get the type of range of the IP address.
     *
     * @return int One of the \IPLib\Range\Type::T_... constants
     *
     * @since 1.5.0
     */
    public function getRangeType();

    /**
     * Get the address at a certain offset of this range.
     *
     * @param int $n the offset of the address (support negative offset)
     *
     * @return \IPLib\Address\AddressInterface|null return NULL if $n is not an integer or if the offset out of range
     *
     * @since 1.15.0
     *
     * @example passing 256 to the range 127.0.0.0/16 will result in 127.0.1.0
     * @example passing -1 to the range 127.0.1.0/16 will result in 127.0.255.255
     * @example passing 256 to the range 127.0.0.0/24 will result in NULL
     */
    public function getAddressAtOffset($n);

    /**
     * Check if this range contains an IP address.
     *
     * @param \IPLib\Address\AddressInterface $address
     *
     * @return bool
     */
    public function contains(AddressInterface $address);

    /**
     * Check if this range contains another range.
     *
     * @param \IPLib\Range\RangeInterface $range
     *
     * @return bool
     *
     * @since 1.5.0
     */
    public function containsRange(RangeInterface $range);

    /**
     * Get the initial address contained in this range.
     *
     * @return \IPLib\Address\AddressInterface
     *
     * @since 1.4.0
     */
    public function getStartAddress();

    /**
     * Get the final address contained in this range.
     *
     * @return \IPLib\Address\AddressInterface
     *
     * @since 1.4.0
     */
    public function getEndAddress();

    /**
     * Get a string representation of the starting address of this range than can be used when comparing addresses and ranges.
     *
     * @return string
     */
    public function getComparableStartString();

    /**
     * Get a string representation of the final address of this range than can be used when comparing addresses and ranges.
     *
     * @return string
     */
    public function getComparableEndString();

    /**
     * Get the subnet mask representing this range (only for IPv4 ranges).
     *
     * @return \IPLib\Address\IPv4|null return NULL if the range is an IPv6 range, the subnet mask otherwise
     *
     * @since 1.8.0
     */
    public function getSubnetMask();

    /**
     * Get the subnet/CIDR representation of this range.
     *
     * @return \IPLib\Range\Subnet
     *
     * @since 1.13.0
     */
    public function asSubnet();

    /**
     * Get the pattern/asterisk representation (if applicable) of this range.
     *
     * @return \IPLib\Range\Pattern|null return NULL if this range can't be represented by a pattern notation
     *
     * @since 1.13.0
     */
    public function asPattern();

    /**
     * Get the Reverse DNS Lookup Addresses of this IP range.
     *
     * @return string[]
     *
     * @since 1.13.0
     *
     * @example for IPv4 it returns something like array('x.x.x.x.in-addr.arpa', 'x.x.x.x.in-addr.arpa') (where the number of 'x.' ranges from 1 to 4)
     * @example for IPv6 it returns something like array('x.x.x.x..x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.ip6.arpa', 'x.x.x.x..x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.ip6.arpa') (where the number of 'x.' ranges from 1 to 32)
     */
    public function getReverseDNSLookupName();

    /**
     * Get the count of addresses this IP range contains.
     *
     * @return int|float Return float as for huge IPv6 networks, int is not enough
     *
     * @since 1.16.0
     */
    public function getSize();

    /**
     * Get the "network prefix", that is how many bits of the address are dedicated to the network portion.
     *
     * @return int
     *
     * @since 1.19.0
     *
     * @example for 10.0.0.0/24 it's 24
     * @example for 10.0.0.* it's 24
     */
    public function getNetworkPrefix();

    /**
     * Split the range into smaller ranges.
     *
     * @param int $networkPrefix
     * @param bool $forceSubnet set to true to always have ranges in "subnet format" (ie 1.2.3.4/5), to false to try to keep the original format if possible (that is, pattern to pattern, single to single)
     *
     * @throws \OutOfBoundsException if $networkPrefix is not valid
     *
     * @return \IPLib\Range\RangeInterface[]
     *
     * @since 1.19.0
     */
    public function split($networkPrefix, $forceSubnet = false);
}
