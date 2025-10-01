<?php

namespace IPLib\Address;

use IPLib\Range\RangeInterface;

/**
 * Interface of all the IP address types.
 */
interface AddressInterface
{
    /**
     * Get the short string representation of this address.
     *
     * @return string
     */
    public function __toString();

    /**
     * Get the number of bits representing this address type.
     *
     * @return int
     *
     * @since 1.14.0
     *
     * @example 32 for IPv4
     * @example 128 for IPv6
     */
    public static function getNumberOfBits();

    /**
     * Get the string representation of this address.
     *
     * @param bool $long set to true to have a long/full representation, false otherwise
     *
     * @return string
     *
     * @example If $long is true, you'll get '0000:0000:0000:0000:0000:0000:0000:0001', '::1' otherwise.
     */
    public function toString($long = false);

    /**
     * Get the byte list of the IP address.
     *
     * @return int[]
     *
     * @example For localhost: for IPv4 you'll get array(127, 0, 0, 1), for IPv6 array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1)
     */
    public function getBytes();

    /**
     * Get the full bit list the IP address.
     *
     * @return string
     *
     * @since 1.14.0
     *
     * @example For localhost: For IPv4 you'll get '01111111000000000000000000000001' (32 digits), for IPv6 '00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001' (128 digits)
     */
    public function getBits();

    /**
     * Get the type of the IP address.
     *
     * @return int One of the \IPLib\Address\Type::T_... constants
     */
    public function getAddressType();

    /**
     * Get the default RFC reserved range type.
     *
     * @return int One of the \IPLib\Range\Type::T_... constants
     *
     * @since 1.5.0
     */
    public static function getDefaultReservedRangeType();

    /**
     * Get the RFC reserved ranges (except the ones of type getDefaultReservedRangeType).
     *
     * @return \IPLib\Address\AssignedRange[] ranges are sorted
     *
     * @since 1.5.0
     */
    public static function getReservedRanges();

    /**
     * Get the type of range of the IP address.
     *
     * @return int One of the \IPLib\Range\Type::T_... constants
     */
    public function getRangeType();

    /**
     * Get a string representation of this address than can be used when comparing addresses and ranges.
     *
     * @return string
     */
    public function getComparableString();

    /**
     * Check if this address is contained in an range.
     *
     * @param \IPLib\Range\RangeInterface $range
     *
     * @return bool
     */
    public function matches(RangeInterface $range);

    /**
     * Get the address at a certain distance from this address.
     *
     * @param int $n the distance of the address (can be negative)
     *
     * @return \IPLib\Address\AddressInterface|null return NULL if $n is not an integer or if the final address would be invalid
     *
     * @since 1.15.0
     *
     * @example passing 1 to the address 127.0.0.1 will result in 127.0.0.2
     * @example passing -1 to the address 127.0.0.1 will result in 127.0.0.0
     * @example passing -1 to the address 0.0.0.0 will result in NULL
     */
    public function getAddressAtOffset($n);

    /**
     * Get the address right after this IP address (if available).
     *
     * @return \IPLib\Address\AddressInterface|null
     *
     * @see \IPLib\Address\AddressInterface::getAddressAtOffset()
     * @since 1.4.0
     */
    public function getNextAddress();

    /**
     * Get the address right before this IP address (if available).
     *
     * @return \IPLib\Address\AddressInterface|null
     *
     * @see \IPLib\Address\AddressInterface::getAddressAtOffset()
     * @since 1.4.0
     */
    public function getPreviousAddress();

    /**
     * Get the Reverse DNS Lookup Address of this IP address.
     *
     * @return string
     *
     * @since 1.12.0
     *
     * @example for IPv4 it returns something like x.x.x.x.in-addr.arpa
     * @example for IPv6 it returns something like x.x.x.x..x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.x.ip6.arpa
     */
    public function getReverseDNSLookupName();

    /**
     * Shift the bits of the address, padding with zeroes.
     *
     * @param int $bits If negative the bits will be shifted left, if positive the bits will be shifted right
     *
     * @return self
     *
     * @since 1.20.0
     *
     * @example shifting by 1 127.0.0.1 you'll have 63.128.0.0
     * @example shifting by -1 127.0.0.1 you'll have 254.0.0.2
     */
    public function shift($bits);

    /**
     * Create a new IP address by adding to this address another address.
     *
     * @return self|null returns NULL if $other is not compatible with this address, or if it generates an invalid address
     *
     * @since 1.20.0
     *
     * @example adding 0.0.0.10 to 127.0.0.1 generates the IP 127.0.0.11
     * @example adding 255.0.0.10 to 127.0.0.1 generates NULL
     */
    public function add(AddressInterface $other);
}
