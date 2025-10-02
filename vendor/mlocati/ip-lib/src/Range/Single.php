<?php

namespace IPLib\Range;

use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;
use IPLib\Address\Type as AddressType;
use IPLib\Factory;
use IPLib\ParseStringFlag;

/**
 * Represents a single address (eg a range that contains just one address).
 *
 * @example 127.0.0.1
 * @example ::1
 */
class Single extends AbstractRange
{
    /**
     * @var \IPLib\Address\AddressInterface
     */
    protected $address;

    /**
     * Initializes the instance.
     *
     * @param \IPLib\Address\AddressInterface $address
     */
    protected function __construct(AddressInterface $address)
    {
        $this->address = $address;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::__toString()
     */
    public function __toString()
    {
        return $this->address->__toString();
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
     * @see \IPLib\Range\Single::parseString()
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
     * @see \IPLib\ParseStringFlag
     * @since 1.17.0
     */
    public static function parseString($range, $flags = 0)
    {
        $result = null;
        $flags = (int) $flags;
        $address = Factory::parseAddressString($range, $flags);
        if ($address !== null) {
            $result = new static($address);
        }

        return $result;
    }

    /**
     * Create the range instance starting from an address instance.
     *
     * @param \IPLib\Address\AddressInterface $address
     *
     * @return static
     *
     * @since 1.2.0
     */
    public static function fromAddress(AddressInterface $address)
    {
        return new static($address);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::toString()
     */
    public function toString($long = false)
    {
        return $this->address->toString($long);
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getAddressType()
     */
    public function getAddressType()
    {
        return $this->address->getAddressType();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getRangeType()
     */
    public function getRangeType()
    {
        return $this->address->getRangeType();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::contains()
     */
    public function contains(AddressInterface $address)
    {
        $result = false;
        if ($address->getAddressType() === $this->getAddressType()) {
            if ($address->toString(false) === $this->address->toString(false)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getStartAddress()
     */
    public function getStartAddress()
    {
        return $this->address;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getEndAddress()
     */
    public function getEndAddress()
    {
        return $this->address;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getComparableStartString()
     */
    public function getComparableStartString()
    {
        return $this->address->getComparableString();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getComparableEndString()
     */
    public function getComparableEndString()
    {
        return $this->address->getComparableString();
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::asSubnet()
     */
    public function asSubnet()
    {
        return new Subnet($this->address, $this->address, $this->getNetworkPrefix());
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::asPattern()
     */
    public function asPattern()
    {
        return new Pattern($this->address, $this->address, 0);
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

        return IPv4::fromBytes(array(255, 255, 255, 255));
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getReverseDNSLookupName()
     */
    public function getReverseDNSLookupName()
    {
        return array($this->getStartAddress()->getReverseDNSLookupName());
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getSize()
     */
    public function getSize()
    {
        return 1;
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
                return 32;
            case AddressType::T_IPv6:
                return 128;
        }
    }
}
