<?php

namespace IPLib;

use IPLib\Address\AddressInterface;
use IPLib\Range\Subnet;
use IPLib\Service\RangesFromBoundaryCalculator;

/**
 * Factory methods to build class instances.
 */
class Factory
{
    /**
     * @deprecated since 1.17.0: use the parseAddressString() method instead.
     * For upgrading:
     * - if $mayIncludePort is true, use the ParseStringFlag::MAY_INCLUDE_PORT flag
     * - if $mayIncludeZoneID is true, use the ParseStringFlag::MAY_INCLUDE_ZONEID flag
     * - if $supportNonDecimalIPv4 is true, use the ParseStringFlag::IPV4_MAYBE_NON_DECIMAL flag
     *
     * @param string|mixed $address
     * @param bool $mayIncludePort
     * @param bool $mayIncludeZoneID
     * @param bool $supportNonDecimalIPv4
     *
     * @return \IPLib\Address\AddressInterface|null
     *
     * @see \IPLib\Factory::parseAddressString()
     * @since 1.1.0 added the $mayIncludePort argument
     * @since 1.3.0 added the $mayIncludeZoneID argument
     * @since 1.10.0 added the $supportNonDecimalIPv4 argument
     */
    public static function addressFromString($address, $mayIncludePort = true, $mayIncludeZoneID = true, $supportNonDecimalIPv4 = false)
    {
        return static::parseAddressString($address, 0 + ($mayIncludePort ? ParseStringFlag::MAY_INCLUDE_PORT : 0) + ($mayIncludeZoneID ? ParseStringFlag::MAY_INCLUDE_ZONEID : 0) + ($supportNonDecimalIPv4 ? ParseStringFlag::IPV4_MAYBE_NON_DECIMAL : 0));
    }

    /**
     * Parse an IP address string.
     *
     * @param string|mixed $address the address to parse
     * @param int $flags A combination or zero or more flags
     *
     * @return \IPLib\Address\AddressInterface|null
     *
     * @see \IPLib\ParseStringFlag
     * @since 1.17.0
     */
    public static function parseAddressString($address, $flags = 0)
    {
        $result = null;
        if ($result === null) {
            $result = Address\IPv4::parseString($address, $flags);
        }
        if ($result === null) {
            $result = Address\IPv6::parseString($address, $flags);
        }

        return $result;
    }

    /**
     * Convert a byte array to an address instance.
     *
     * @param int[]|array $bytes
     *
     * @return \IPLib\Address\AddressInterface|null
     */
    public static function addressFromBytes(array $bytes)
    {
        $result = null;
        if ($result === null) {
            $result = Address\IPv4::fromBytes($bytes);
        }
        if ($result === null) {
            $result = Address\IPv6::fromBytes($bytes);
        }

        return $result;
    }

    /**
     * @deprecated since 1.17.0: use the parseRangeString() method instead.
     * For upgrading:
     * - if $supportNonDecimalIPv4 is true, use the ParseStringFlag::IPV4_MAYBE_NON_DECIMAL flag
     *
     * @param string|mixed $range
     * @param bool $supportNonDecimalIPv4
     *
     * @return \IPLib\Range\RangeInterface|null
     *
     * @see \IPLib\Factory::parseRangeString()
     * @since 1.10.0 added the $supportNonDecimalIPv4 argument
     */
    public static function rangeFromString($range, $supportNonDecimalIPv4 = false)
    {
        return static::parseRangeString($range, $supportNonDecimalIPv4 ? ParseStringFlag::IPV4_MAYBE_NON_DECIMAL : 0);
    }

    /**
     * Parse an IP range string.
     *
     * @param string $range
     * @param int $flags A combination or zero or more flags
     *
     * @return \IPLib\Range\RangeInterface|null
     *
     * @see \IPLib\ParseStringFlag
     * @since 1.17.0
     */
    public static function parseRangeString($range, $flags = 0)
    {
        $result = null;
        if ($result === null) {
            $result = Range\Subnet::parseString($range, $flags);
        }
        if ($result === null) {
            $result = Range\Pattern::parseString($range, $flags);
        }
        if ($result === null) {
            $result = Range\Single::parseString($range, $flags);
        }

        return $result;
    }

    /**
     * @deprecated since 1.17.0: use the getRangeFromBoundaries() method instead.
     * For upgrading:
     * - if $supportNonDecimalIPv4 is true, use the ParseStringFlag::IPV4_MAYBE_NON_DECIMAL flag
     *
     * @param string|\IPLib\Address\AddressInterface|mixed $from
     * @param string|\IPLib\Address\AddressInterface|mixed $to
     * @param bool $supportNonDecimalIPv4
     *
     * @return \IPLib\Address\AddressInterface|null
     *
     * @see \IPLib\Factory::getRangeFromBoundaries()
     * @since 1.2.0
     * @since 1.10.0 added the $supportNonDecimalIPv4 argument
     */
    public static function rangeFromBoundaries($from, $to, $supportNonDecimalIPv4 = false)
    {
        return static::getRangeFromBoundaries($from, $to, ParseStringFlag::MAY_INCLUDE_PORT | ParseStringFlag::MAY_INCLUDE_ZONEID | ($supportNonDecimalIPv4 ? ParseStringFlag::IPV4_MAYBE_NON_DECIMAL : 0));
    }

    /**
     * Create the smallest address range that comprises two addresses.
     *
     * @param string|\IPLib\Address\AddressInterface|mixed $from
     * @param string|\IPLib\Address\AddressInterface|mixed $to
     * @param int $flags A combination or zero or more flags
     *
     * @return \IPLib\Range\RangeInterface|null return NULL if $from and/or $to are invalid addresses, or if both are NULL or empty strings, or if they are addresses of different types
     *
     * @see \IPLib\ParseStringFlag
     * @since 1.17.0
     */
    public static function getRangeFromBoundaries($from, $to, $flags = 0)
    {
        list($from, $to) = self::parseBoundaries($from, $to, $flags);

        return $from === false || $to === false ? null : static::rangeFromBoundaryAddresses($from, $to);
    }

    /**
     * @deprecated since 1.17.0: use the getRangesFromBoundaries() method instead.
     * For upgrading:
     * - if $supportNonDecimalIPv4 is true, use the ParseStringFlag::IPV4_MAYBE_NON_DECIMAL flag
     *
     * @param string|\IPLib\Address\AddressInterface|mixed $from
     * @param string|\IPLib\Address\AddressInterface|mixed $to
     * @param bool $supportNonDecimalIPv4
     *
     * @return \IPLib\Range\Subnet[]|null
     *
     * @see \IPLib\Factory::getRangesFromBoundaries()
     * @since 1.14.0
     */
    public static function rangesFromBoundaries($from, $to, $supportNonDecimalIPv4 = false)
    {
        return static::getRangesFromBoundaries($from, $to, ParseStringFlag::MAY_INCLUDE_PORT | ParseStringFlag::MAY_INCLUDE_ZONEID | ($supportNonDecimalIPv4 ? ParseStringFlag::IPV4_MAYBE_NON_DECIMAL : 0));
    }

    /**
     * Create a list of Range instances that exactly describes all the addresses between the two provided addresses.
     *
     * @param string|\IPLib\Address\AddressInterface $from
     * @param string|\IPLib\Address\AddressInterface $to
     * @param int $flags A combination or zero or more flags
     *
     * @return \IPLib\Range\Subnet[]|null return NULL if $from and/or $to are invalid addresses, or if both are NULL or empty strings, or if they are addresses of different types
     *
     * @see \IPLib\ParseStringFlag
     * @since 1.17.0
     */
    public static function getRangesFromBoundaries($from, $to, $flags = 0)
    {
        list($from, $to) = self::parseBoundaries($from, $to, $flags);
        if ($from === false || $to === false || ($from === null && $to === null)) {
            return null;
        }
        if ($from === null || $to === null) {
            $address = $from ? $from : $to;

            return array(new Subnet($address, $address, $address->getNumberOfBits()));
        }
        $numberOfBits = $from->getNumberOfBits();
        if ($to->getNumberOfBits() !== $numberOfBits) {
            return null;
        }
        $calculator = new RangesFromBoundaryCalculator($numberOfBits);

        return $calculator->getRanges($from, $to);
    }

    /**
     * @param \IPLib\Address\AddressInterface|null $from
     * @param \IPLib\Address\AddressInterface|null $to
     *
     * @return \IPLib\Range\RangeInterface|null
     *
     * @since 1.2.0
     */
    protected static function rangeFromBoundaryAddresses($from = null, $to = null)
    {
        if (!$from instanceof AddressInterface && !$to instanceof AddressInterface) {
            $result = null;
        } elseif (!$to instanceof AddressInterface) {
            $result = Range\Single::fromAddress($from);
        } elseif (!$from instanceof AddressInterface) {
            $result = Range\Single::fromAddress($to);
        } else {
            $result = null;
            $addressType = $from->getAddressType();
            if ($addressType === $to->getAddressType()) {
                $cmp = strcmp($from->getComparableString(), $to->getComparableString());
                if ($cmp === 0) {
                    $result = Range\Single::fromAddress($from);
                } else {
                    if ($cmp > 0) {
                        list($from, $to) = array($to, $from);
                    }
                    $fromBytes = $from->getBytes();
                    $toBytes = $to->getBytes();
                    $numBytes = count($fromBytes);
                    $sameBits = 0;
                    for ($byteIndex = 0; $byteIndex < $numBytes; $byteIndex++) {
                        $fromByte = $fromBytes[$byteIndex];
                        $toByte = $toBytes[$byteIndex];
                        if ($fromByte === $toByte) {
                            $sameBits += 8;
                        } else {
                            $differentBitsInByte = decbin($fromByte ^ $toByte);
                            $sameBits += 8 - strlen($differentBitsInByte);
                            break;
                        }
                    }
                    $result = static::parseRangeString($from->toString() . '/' . (string) $sameBits);
                }
            }
        }

        return $result;
    }

    /**
     * @param string|\IPLib\Address\AddressInterface $from
     * @param string|\IPLib\Address\AddressInterface $to
     * @param int $flags
     *
     * @return \IPLib\Address\AddressInterface[]|null[]|false[]
     */
    private static function parseBoundaries($from, $to, $flags = 0)
    {
        $result = array();
        foreach (array('from', 'to') as $param) {
            $value = $$param;
            if (!($value instanceof AddressInterface)) {
                $value = (string) $value;
                if ($value === '') {
                    $value = null;
                } else {
                    $value = static::parseAddressString($value, $flags);
                    if ($value === null) {
                        $value = false;
                    }
                }
            }
            $result[] = $value;
        }
        if ($result[0] && $result[1] && strcmp($result[0]->getComparableString(), $result[1]->getComparableString()) > 0) {
            $result = array($result[1], $result[0]);
        }

        return $result;
    }
}
