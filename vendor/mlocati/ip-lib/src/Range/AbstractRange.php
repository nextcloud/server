<?php

namespace IPLib\Range;

use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;
use IPLib\Address\IPv6;
use IPLib\Address\Type as AddressType;
use IPLib\Factory;
use OutOfBoundsException;

/**
 * Base class for range classes.
 */
abstract class AbstractRange implements RangeInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getRangeType()
     */
    public function getRangeType()
    {
        if ($this->rangeType === null) {
            $addressType = $this->getAddressType();
            if ($addressType === AddressType::T_IPv6 && Subnet::get6to4()->containsRange($this)) {
                $this->rangeType = Factory::getRangeFromBoundaries($this->fromAddress->toIPv4(), $this->toAddress->toIPv4())->getRangeType();
            } else {
                switch ($addressType) {
                    case AddressType::T_IPv4:
                        $defaultType = IPv4::getDefaultReservedRangeType();
                        $reservedRanges = IPv4::getReservedRanges();
                        break;
                    case AddressType::T_IPv6:
                        $defaultType = IPv6::getDefaultReservedRangeType();
                        $reservedRanges = IPv6::getReservedRanges();
                        break;
                    default:
                        throw new \Exception('@todo'); // @codeCoverageIgnore
                }
                $rangeType = null;
                foreach ($reservedRanges as $reservedRange) {
                    $rangeType = $reservedRange->getRangeType($this);
                    if ($rangeType !== null) {
                        break;
                    }
                }
                $this->rangeType = $rangeType === null ? $defaultType : $rangeType;
            }
        }

        return $this->rangeType === false ? null : $this->rangeType;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getAddressAtOffset()
     */
    public function getAddressAtOffset($n)
    {
        if (!is_int($n)) {
            return null;
        }

        $address = null;
        if ($n >= 0) {
            $start = Factory::parseAddressString($this->getComparableStartString());
            $address = $start->getAddressAtOffset($n);
        } else {
            $end = Factory::parseAddressString($this->getComparableEndString());
            $address = $end->getAddressAtOffset($n + 1);
        }

        if ($address === null) {
            return null;
        }

        return $this->contains($address) ? $address : null;
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
            $cmp = $address->getComparableString();
            $from = $this->getComparableStartString();
            if ($cmp >= $from) {
                $to = $this->getComparableEndString();
                if ($cmp <= $to) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::containsRange()
     */
    public function containsRange(RangeInterface $range)
    {
        $result = false;
        if ($range->getAddressType() === $this->getAddressType()) {
            $myStart = $this->getComparableStartString();
            $itsStart = $range->getComparableStartString();
            if ($itsStart >= $myStart) {
                $myEnd = $this->getComparableEndString();
                $itsEnd = $range->getComparableEndString();
                if ($itsEnd <= $myEnd) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::split()
     */
    public function split($networkPrefix, $forceSubnet = false)
    {
        $networkPrefix = (int) $networkPrefix;
        $myNetworkPrefix = $this->getNetworkPrefix();
        if ($networkPrefix === $myNetworkPrefix) {
            return array(
                $forceSubnet ? $this->asSubnet() : $this,
            );
        }
        if ($networkPrefix < $myNetworkPrefix) {
            throw new OutOfBoundsException("The value of the \$networkPrefix parameter can't be smaller than the network prefix of the range ({$myNetworkPrefix})");
        }
        $startIp = $this->getStartAddress();
        $maxPrefix = $startIp::getNumberOfBits();
        if ($networkPrefix > $maxPrefix) {
            throw new OutOfBoundsException("The value of the \$networkPrefix parameter can't be larger than the maximum network prefix of the range ({$maxPrefix})");
        }
        if ($startIp instanceof IPv4) {
            $one = IPv4::fromBytes(array(0, 0, 0, 1));
        } elseif ($startIp instanceof IPv6) {
            $one = IPv6::fromBytes(array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1));
        }
        $delta = $one->shift($networkPrefix - $maxPrefix);
        $result = array();
        while (true) {
            $range = Subnet::parseString("{$startIp}/{$networkPrefix}");
            if (!$forceSubnet && $this instanceof Pattern) {
                $range = $range->asPattern() ?: $range;
            }
            $result[] = $range;
            $startIp = $startIp->add($delta);
            if ($startIp === null || !$this->contains($startIp)) {
                break;
            }
        }

        return $result;
    }
}
