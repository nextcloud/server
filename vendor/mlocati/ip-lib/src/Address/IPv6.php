<?php

namespace IPLib\Address;

use IPLib\ParseStringFlag;
use IPLib\Range\RangeInterface;
use IPLib\Range\Subnet;
use IPLib\Range\Type as RangeType;

/**
 * An IPv6 address.
 */
class IPv6 implements AddressInterface
{
    /**
     * The long string representation of the address.
     *
     * @var string
     *
     * @example '0000:0000:0000:0000:0000:0000:0000:0001'
     */
    protected $longAddress;

    /**
     * The long string representation of the address.
     *
     * @var string|null
     *
     * @example '::1'
     */
    protected $shortAddress;

    /**
     * The byte list of the IP address.
     *
     * @var int[]|null
     */
    protected $bytes;

    /**
     * The word list of the IP address.
     *
     * @var int[]|null
     */
    protected $words;

    /**
     * The type of the range of this IP address.
     *
     * @var int|null
     */
    protected $rangeType;

    /**
     * An array containing RFC designated address ranges.
     *
     * @var array|null
     */
    private static $reservedRanges;

    /**
     * Initializes the instance.
     *
     * @param string $longAddress
     */
    public function __construct($longAddress)
    {
        $this->longAddress = $longAddress;
        $this->shortAddress = null;
        $this->bytes = null;
        $this->words = null;
        $this->rangeType = null;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::__toString()
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getNumberOfBits()
     */
    public static function getNumberOfBits()
    {
        return 128;
    }

    /**
     * @deprecated since 1.17.0: use the parseString() method instead.
     * For upgrading:
     * - if $mayIncludePort is true, use the ParseStringFlag::MAY_INCLUDE_PORT flag
     * - if $mayIncludeZoneID is true, use the ParseStringFlag::MAY_INCLUDE_ZONEID flag
     *
     * @param string|mixed $address
     * @param bool $mayIncludePort
     * @param bool $mayIncludeZoneID
     *
     * @return static|null
     *
     * @see \IPLib\Address\IPv6::parseString()
     * @since 1.1.0 added the $mayIncludePort argument
     * @since 1.3.0 added the $mayIncludeZoneID argument
     */
    public static function fromString($address, $mayIncludePort = true, $mayIncludeZoneID = true)
    {
        return static::parseString($address, 0 | ($mayIncludePort ? ParseStringFlag::MAY_INCLUDE_PORT : 0) | ($mayIncludeZoneID ? ParseStringFlag::MAY_INCLUDE_ZONEID : 0));
    }

    /**
     * Parse a string and returns an IPv6 instance if the string is valid, or null otherwise.
     *
     * @param string|mixed $address the address to parse
     * @param int $flags A combination or zero or more flags
     *
     * @return static|null
     *
     * @see \IPLib\ParseStringFlag
     * @since 1.17.0
     */
    public static function parseString($address, $flags = 0)
    {
        if (!is_string($address)) {
            return null;
        }
        $matches = null;
        $flags = (int) $flags;
        if ($flags & ParseStringFlag::ADDRESS_MAYBE_RDNS) {
            if (preg_match('/^([0-9a-f](?:\.[0-9a-f]){31})\.ip6\.arpa\.?/i', $address, $matches)) {
                $nibbles = array_reverse(explode('.', $matches[1]));
                $quibbles = array();
                foreach (array_chunk($nibbles, 4) as $n) {
                    $quibbles[] = implode('', $n);
                }
                $address = implode(':', $quibbles);
            }
        }
        $result = null;
        if (is_string($address) && strpos($address, ':') !== false && strpos($address, ':::') === false) {
            if ($flags & ParseStringFlag::MAY_INCLUDE_PORT && $address[0] === '[' && preg_match('/^\[(.+)]:\d+$/', $address, $matches)) {
                $address = $matches[1];
            }
            if ($flags & ParseStringFlag::MAY_INCLUDE_ZONEID) {
                $percentagePos = strpos($address, '%');
                if ($percentagePos > 0) {
                    $address = substr($address, 0, $percentagePos);
                }
            }
            if (preg_match('/^((?:[0-9a-f]*:+)+)(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/i', $address, $matches)) {
                $address6 = static::parseString($matches[1] . '0:0');
                if ($address6 !== null) {
                    $address4 = IPv4::parseString($matches[2]);
                    if ($address4 !== null) {
                        $bytes4 = $address4->getBytes();
                        $address6->longAddress = substr($address6->longAddress, 0, -9) . sprintf('%02x%02x:%02x%02x', $bytes4[0], $bytes4[1], $bytes4[2], $bytes4[3]);
                        $result = $address6;
                    }
                }
            } else {
                if (strpos($address, '::') === false) {
                    $chunks = explode(':', $address);
                } else {
                    $chunks = array();
                    $parts = explode('::', $address);
                    if (count($parts) === 2) {
                        $before = ($parts[0] === '') ? array() : explode(':', $parts[0]);
                        $after = ($parts[1] === '') ? array() : explode(':', $parts[1]);
                        $missing = 8 - count($before) - count($after);
                        if ($missing >= 0) {
                            $chunks = $before;
                            if ($missing !== 0) {
                                $chunks = array_merge($chunks, array_fill(0, $missing, '0'));
                            }
                            $chunks = array_merge($chunks, $after);
                        }
                    }
                }
                if (count($chunks) === 8) {
                    $nums = array_map(
                        function ($chunk) {
                            return preg_match('/^[0-9A-Fa-f]{1,4}$/', $chunk) ? hexdec($chunk) : false;
                        },
                        $chunks
                    );
                    if (!in_array(false, $nums, true)) {
                        $longAddress = implode(
                            ':',
                            array_map(
                                function ($num) {
                                    return sprintf('%04x', $num);
                                },
                                $nums
                            )
                        );
                        $result = new static($longAddress);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Parse an array of bytes and returns an IPv6 instance if the array is valid, or null otherwise.
     *
     * @param int[]|array $bytes
     *
     * @return static|null
     */
    public static function fromBytes(array $bytes)
    {
        $result = null;
        if (count($bytes) === 16) {
            $address = '';
            for ($i = 0; $i < 16; $i++) {
                if ($i !== 0 && $i % 2 === 0) {
                    $address .= ':';
                }
                $byte = $bytes[$i];
                if (is_int($byte) && $byte >= 0 && $byte <= 255) {
                    $address .= sprintf('%02x', $byte);
                } else {
                    $address = null;
                    break;
                }
            }
            if ($address !== null) {
                $result = new static($address);
            }
        }

        return $result;
    }

    /**
     * Parse an array of words and returns an IPv6 instance if the array is valid, or null otherwise.
     *
     * @param int[]|array $words
     *
     * @return static|null
     */
    public static function fromWords(array $words)
    {
        $result = null;
        if (count($words) === 8) {
            $chunks = array();
            for ($i = 0; $i < 8; $i++) {
                $word = $words[$i];
                if (is_int($word) && $word >= 0 && $word <= 0xffff) {
                    $chunks[] = sprintf('%04x', $word);
                } else {
                    $chunks = null;
                    break;
                }
            }
            if ($chunks !== null) {
                $result = new static(implode(':', $chunks));
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::toString()
     */
    public function toString($long = false)
    {
        if ($long) {
            $result = $this->longAddress;
        } else {
            if ($this->shortAddress === null) {
                if (strpos($this->longAddress, '0000:0000:0000:0000:0000:ffff:') === 0) {
                    $lastBytes = array_slice($this->getBytes(), -4);
                    $this->shortAddress = '::ffff:' . implode('.', $lastBytes);
                } else {
                    $chunks = array_map(
                        function ($word) {
                            return dechex($word);
                        },
                        $this->getWords()
                    );
                    $shortAddress = implode(':', $chunks);
                    $matches = null;
                    for ($i = 8; $i > 1; $i--) {
                        $search = '(?:^|:)' . rtrim(str_repeat('0:', $i), ':') . '(?:$|:)';
                        if (preg_match('/^(.*?)' . $search . '(.*)$/', $shortAddress, $matches)) {
                            $shortAddress = $matches[1] . '::' . $matches[2];
                            break;
                        }
                    }
                    $this->shortAddress = $shortAddress;
                }
            }
            $result = $this->shortAddress;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getBytes()
     */
    public function getBytes()
    {
        if ($this->bytes === null) {
            $bytes = array();
            foreach ($this->getWords() as $word) {
                $bytes[] = $word >> 8;
                $bytes[] = $word & 0xff;
            }
            $this->bytes = $bytes;
        }

        return $this->bytes;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getBits()
     */
    public function getBits()
    {
        $parts = array();
        foreach ($this->getBytes() as $byte) {
            $parts[] = sprintf('%08b', $byte);
        }

        return implode('', $parts);
    }

    /**
     * Get the word list of the IP address.
     *
     * @return int[]
     */
    public function getWords()
    {
        if ($this->words === null) {
            $this->words = array_map(
                function ($chunk) {
                    return hexdec($chunk);
                },
                explode(':', $this->longAddress)
            );
        }

        return $this->words;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getAddressType()
     */
    public function getAddressType()
    {
        return Type::T_IPv6;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getDefaultReservedRangeType()
     */
    public static function getDefaultReservedRangeType()
    {
        return RangeType::T_RESERVED;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getReservedRanges()
     */
    public static function getReservedRanges()
    {
        if (self::$reservedRanges === null) {
            $reservedRanges = array();
            foreach (array(
                // RFC 4291
                '::/128' => array(RangeType::T_UNSPECIFIED),
                // RFC 4291
                '::1/128' => array(RangeType::T_LOOPBACK),
                // RFC 4291
                '100::/8' => array(RangeType::T_DISCARD, array('100::/64' => RangeType::T_DISCARDONLY)),
                //'2002::/16' => array(RangeType::),
                // RFC 4291
                '2000::/3' => array(RangeType::T_PUBLIC),
                // RFC 4193
                'fc00::/7' => array(RangeType::T_PRIVATENETWORK),
                // RFC 4291
                'fe80::/10' => array(RangeType::T_LINKLOCAL_UNICAST),
                // RFC 4291
                'ff00::/8' => array(RangeType::T_MULTICAST),
                // RFC 4291
                //'::/8' => array(RangeType::T_RESERVED),
                // RFC 4048
                //'200::/7' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'400::/6' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'800::/5' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'1000::/4' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'4000::/3' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'6000::/3' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'8000::/3' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'a000::/3' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'c000::/3' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'e000::/4' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'f000::/5' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'f800::/6' => array(RangeType::T_RESERVED),
                // RFC 4291
                //'fe00::/9' => array(RangeType::T_RESERVED),
                // RFC 3879
                //'fec0::/10' => array(RangeType::T_RESERVED),
            ) as $range => $data) {
                $exceptions = array();
                if (isset($data[1])) {
                    foreach ($data[1] as $exceptionRange => $exceptionType) {
                        $exceptions[] = new AssignedRange(Subnet::parseString($exceptionRange), $exceptionType);
                    }
                }
                $reservedRanges[] = new AssignedRange(Subnet::parseString($range), $data[0], $exceptions);
            }
            self::$reservedRanges = $reservedRanges;
        }

        return self::$reservedRanges;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getRangeType()
     */
    public function getRangeType()
    {
        if ($this->rangeType === null) {
            $ipv4 = $this->toIPv4();
            if ($ipv4 !== null) {
                $this->rangeType = $ipv4->getRangeType();
            } else {
                $rangeType = null;
                foreach (static::getReservedRanges() as $reservedRange) {
                    $rangeType = $reservedRange->getAddressType($this);
                    if ($rangeType !== null) {
                        break;
                    }
                }
                $this->rangeType = $rangeType === null ? static::getDefaultReservedRangeType() : $rangeType;
            }
        }

        return $this->rangeType;
    }

    /**
     * Create an IPv4 representation of this address (if possible, otherwise returns null).
     *
     * @return \IPLib\Address\IPv4|null
     */
    public function toIPv4()
    {
        if (strpos($this->longAddress, '2002:') === 0) {
            // 6to4
            return IPv4::fromBytes(array_slice($this->getBytes(), 2, 4));
        }
        if (strpos($this->longAddress, '0000:0000:0000:0000:0000:ffff:') === 0) {
            // IPv4-mapped IPv6 addresses
            return IPv4::fromBytes(array_slice($this->getBytes(), -4));
        }

        return null;
    }

    /**
     * Render this IPv6 address in the "mixed" IPv6 (first 12 bytes) + IPv4 (last 4 bytes) mixed syntax.
     *
     * @param bool $ipV6Long render the IPv6 part in "long" format?
     * @param bool $ipV4Long render the IPv4 part in "long" format?
     *
     * @return string
     *
     * @example '::13.1.68.3'
     * @example '0000:0000:0000:0000:0000:0000:13.1.68.3' when $ipV6Long is true
     * @example '::013.001.068.003' when $ipV4Long is true
     * @example '0000:0000:0000:0000:0000:0000:013.001.068.003' when $ipV6Long and $ipV4Long are true
     *
     * @see https://tools.ietf.org/html/rfc4291#section-2.2 point 3.
     * @since 1.9.0
     */
    public function toMixedIPv6IPv4String($ipV6Long = false, $ipV4Long = false)
    {
        $myBytes = $this->getBytes();
        $ipv6Bytes = array_merge(array_slice($myBytes, 0, 12), array(0xff, 0xff, 0xff, 0xff));
        $ipv6String = static::fromBytes($ipv6Bytes)->toString($ipV6Long);
        $ipv4Bytes = array_slice($myBytes, 12, 4);
        $ipv4String = IPv4::fromBytes($ipv4Bytes)->toString($ipV4Long);

        return preg_replace('/((ffff:ffff)|(\d+(\.\d+){3}))$/i', $ipv4String, $ipv6String);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getComparableString()
     */
    public function getComparableString()
    {
        return $this->longAddress;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::matches()
     */
    public function matches(RangeInterface $range)
    {
        return $range->contains($this);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getAddressAtOffset()
     */
    public function getAddressAtOffset($n)
    {
        if (!is_int($n)) {
            return null;
        }

        $boundary = 0x10000;
        $mod = $n;
        $words = $this->getWords();
        for ($i = count($words) - 1; $i >= 0; $i--) {
            $tmp = ($words[$i] + $mod) % $boundary;
            $mod = (int) floor(($words[$i] + $mod) / $boundary);
            if ($tmp < 0) {
                $tmp += $boundary;
            }

            $words[$i] = $tmp;
        }

        if ($mod !== 0) {
            return null;
        }

        return static::fromWords($words);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getNextAddress()
     */
    public function getNextAddress()
    {
        return $this->getAddressAtOffset(1);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getPreviousAddress()
     */
    public function getPreviousAddress()
    {
        return $this->getAddressAtOffset(-1);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::getReverseDNSLookupName()
     */
    public function getReverseDNSLookupName()
    {
        return implode(
            '.',
            array_reverse(str_split(str_replace(':', '', $this->toString(true)), 1))
        ) . '.ip6.arpa';
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::shift()
     */
    public function shift($bits)
    {
        $bits = (int) $bits;
        if ($bits === 0) {
            return $this;
        }
        $absBits = abs($bits);
        if ($absBits >= 128) {
            return new self('0000:0000:0000:0000:0000:0000:0000:0000');
        }
        $pad = str_repeat('0', $absBits);
        $paddedBits = $this->getBits();
        if ($bits > 0) {
            $paddedBits = $pad . substr($paddedBits, 0, -$bits);
        } else {
            $paddedBits = substr($paddedBits, $absBits) . $pad;
        }
        $bytes = array_map('bindec', str_split($paddedBits, 16));

        return static::fromWords($bytes);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Address\AddressInterface::add()
     */
    public function add(AddressInterface $other)
    {
        if (!$other instanceof self) {
            return null;
        }
        $myWords = $this->getWords();
        $otherWords = $other->getWords();
        $sum = array_fill(0, 7, 0);
        $carry = 0;
        for ($index = 7; $index >= 0; $index--) {
            $word = $myWords[$index] + $otherWords[$index] + $carry;
            if ($word > 0xFFFF) {
                $carry = $word >> 16;
                $word &= 0xFFFF;
            } else {
                $carry = 0;
            }
            $sum[$index] = $word;
        }
        if ($carry !== 0) {
            return null;
        }

        return static::fromWords($sum);
    }
}
