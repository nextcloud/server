<?php

namespace IPLib\Range;

use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;
use IPLib\Address\IPv6;
use IPLib\Address\Type as AddressType;
use IPLib\ParseStringFlag;

/**
 * Represents an address range in pattern format (only ending asterisks are supported).
 *
 * @example 127.0.*.*
 * @example ::/8
 */
class Pattern extends AbstractRange
{
    /**
     * Starting address of the range.
     *
     * @var \IPLib\Address\AddressInterface
     */
    protected $fromAddress;

    /**
     * Final address of the range.
     *
     * @var \IPLib\Address\AddressInterface
     */
    protected $toAddress;

    /**
     * Number of ending asterisks.
     *
     * @var int
     */
    protected $asterisksCount;

    /**
     * The type of the range of this IP range.
     *
     * @var int|false|null false if this range crosses multiple range types, null if yet to be determined
     *
     * @since 1.5.0
     */
    protected $rangeType;

    /**
     * Initializes the instance.
     *
     * @param \IPLib\Address\AddressInterface $fromAddress
     * @param \IPLib\Address\AddressInterface $toAddress
     * @param int $asterisksCount
     */
    public function __construct(AddressInterface $fromAddress, AddressInterface $toAddress, $asterisksCount)
    {
        $this->fromAddress = $fromAddress;
        $this->toAddress = $toAddress;
        $this->asterisksCount = $asterisksCount;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::__toString()
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @deprecated since 1.17.0: use the parseString() method instead.
     * For upgrading:
     * - if $supportNonDecimalIPv4 is true, use the ParseStringFlag::IPV4_MAYBE_NON_DECIMAL flag
     *
     * @param string|mixed $range
     * @param bool $supportNonDecimalIPv4
     *
     * @return static|null
     *
     * @see \IPLib\Range\Pattern::parseString()
     * @since 1.10.0 added the $supportNonDecimalIPv4 argument
     */
    public static function fromString($range, $supportNonDecimalIPv4 = false)
    {
        return static::parseString($range, ParseStringFlag::MAY_INCLUDE_PORT | ParseStringFlag::MAY_INCLUDE_ZONEID | ($supportNonDecimalIPv4 ? ParseStringFlag::IPV4_MAYBE_NON_DECIMAL : 0));
    }

    /**
     * Try get the range instance starting from its string representation.
     *
     * @param string|mixed $range
     * @param int $flags A combination or zero or more flags
     *
     * @return static|null
     *
     * @since 1.17.0
     * @see \IPLib\ParseStringFlag
     */
    public static function parseString($range, $flags = 0)
    {
        if (!is_string($range) || strpos($range, '*') === false) {
            return null;
        }
        if ($range === '*.*.*.*') {
            return new static(IPv4::parseString('0.0.0.0'), IPv4::parseString('255.255.255.255'), 4);
        }
        if ($range === '*:*:*:*:*:*:*:*') {
            return new static(IPv6::parseString('::'), IPv6::parseString('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'), 8);
        }
        $matches = null;
        if (strpos($range, '.') !== false && preg_match('/^[^*]+((?:\.\*)+)$/', $range, $matches)) {
            $asterisksCount = strlen($matches[1]) >> 1;
            if ($asterisksCount > 0) {
                $missingDots = 3 - substr_count($range, '.');
                if ($missingDots > 0) {
                    $range .= str_repeat('.*', $missingDots);
                    $asterisksCount += $missingDots;
                }
            }
            $fromAddress = IPv4::parseString(str_replace('*', '0', $range), $flags);
            if ($fromAddress === null) {
                return null;
            }
            $fixedBytes = array_slice($fromAddress->getBytes(), 0, -$asterisksCount);
            $otherBytes = array_fill(0, $asterisksCount, 255);
            $toAddress = IPv4::fromBytes(array_merge($fixedBytes, $otherBytes));

            return new static($fromAddress, $toAddress, $asterisksCount);
        }
        if (strpos($range, ':') !== false && preg_match('/^[^*]+((?::\*)+)$/', $range, $matches)) {
            $asterisksCount = strlen($matches[1]) >> 1;
            $fromAddress = IPv6::parseString(str_replace('*', '0', $range));
            if ($fromAddress === null) {
                return null;
            }
            $fixedWords = array_slice($fromAddress->getWords(), 0, -$asterisksCount);
            $otherWords = array_fill(0, $asterisksCount, 0xffff);
            $toAddress = IPv6::fromWords(array_merge($fixedWords, $otherWords));

            return new static($fromAddress, $toAddress, $asterisksCount);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::toString()
     */
    public function toString($long = false)
    {
        if ($this->asterisksCount === 0) {
            return $this->fromAddress->toString($long);
        }
        switch (true) {
            case $this->fromAddress instanceof \IPLib\Address\IPv4:
                $chunks = explode('.', $this->fromAddress->toString());
                $chunks = array_slice($chunks, 0, -$this->asterisksCount);
                $chunks = array_pad($chunks, 4, '*');
                $result = implode('.', $chunks);
                break;
            case $this->fromAddress instanceof \IPLib\Address\IPv6:
                if ($long) {
                    $chunks = explode(':', $this->fromAddress->toString(true));
                    $chunks = array_slice($chunks, 0, -$this->asterisksCount);
                    $chunks = array_pad($chunks, 8, '*');
                    $result = implode(':', $chunks);
                } elseif ($this->asterisksCount === 8) {
                    $result = '*:*:*:*:*:*:*:*';
                } else {
                    $bytes = $this->toAddress->getBytes();
                    $bytes = array_slice($bytes, 0, -$this->asterisksCount * 2);
                    $bytes = array_pad($bytes, 16, 1);
                    $address = IPv6::fromBytes($bytes);
                    $before = substr($address->toString(false), 0, -strlen(':101') * $this->asterisksCount);
                    $result = $before . str_repeat(':*', $this->asterisksCount);
                }
                break;
            default:
                throw new \Exception('@todo'); // @codeCoverageIgnore
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getAddressType()
     */
    public function getAddressType()
    {
        return $this->fromAddress->getAddressType();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getStartAddress()
     */
    public function getStartAddress()
    {
        return $this->fromAddress;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getEndAddress()
     */
    public function getEndAddress()
    {
        return $this->toAddress;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getComparableStartString()
     */
    public function getComparableStartString()
    {
        return $this->fromAddress->getComparableString();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getComparableEndString()
     */
    public function getComparableEndString()
    {
        return $this->toAddress->getComparableString();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::asSubnet()
     * @since 1.8.0
     */
    public function asSubnet()
    {
        return new Subnet($this->getStartAddress(), $this->getEndAddress(), $this->getNetworkPrefix());
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::asPattern()
     */
    public function asPattern()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getSubnetMask()
     */
    public function getSubnetMask()
    {
        if ($this->getAddressType() !== AddressType::T_IPv4) {
            return null;
        }
        switch ($this->asterisksCount) {
            case 0:
                $bytes = array(255, 255, 255, 255);
                break;
            case 4:
                $bytes = array(0, 0, 0, 0);
                break;
            default:
                $bytes = array_pad(array_fill(0, 4 - $this->asterisksCount, 255), 4, 0);
                break;
        }

        return IPv4::fromBytes($bytes);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getReverseDNSLookupName()
     */
    public function getReverseDNSLookupName()
    {
        return $this->asterisksCount === 0 ? array($this->getStartAddress()->getReverseDNSLookupName()) : $this->asSubnet()->getReverseDNSLookupName();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getSize()
     */
    public function getSize()
    {
        $fromAddress = $this->fromAddress;
        $maxPrefix = $fromAddress::getNumberOfBits();
        $prefix = $this->getNetworkPrefix();

        return pow(2, ($maxPrefix - $prefix));
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getNetworkPrefix()
     */
    public function getNetworkPrefix()
    {
        switch ($this->getAddressType()) {
            case AddressType::T_IPv4:
                return 8 * (4 - $this->asterisksCount);
            case AddressType::T_IPv6:
                return 16 * (8 - $this->asterisksCount);
        }
    }
}
