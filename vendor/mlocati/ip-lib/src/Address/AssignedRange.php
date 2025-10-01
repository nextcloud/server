<?php

namespace IPLib\Address;

use IPLib\Range\RangeInterface;

/**
 * Represents an IP address range with an assigned range type.
 *
 * @since 1.5.0
 */
class AssignedRange
{
    /**
     * The range definition.
     *
     * @var \IPLib\Range\RangeInterface
     */
    protected $range;

    /**
     * The range type.
     *
     * @var int one of the \IPLib\Range\Type::T_ constants
     */
    protected $type;

    /**
     * The list of exceptions for this range type.
     *
     * @var \IPLib\Address\AssignedRange[]
     */
    protected $exceptions;

    /**
     * Initialize the instance.
     *
     * @param \IPLib\Range\RangeInterface $range the range definition
     * @param int $type The range type (one of the \IPLib\Range\Type::T_ constants)
     * @param \IPLib\Address\AssignedRange[] $exceptions the list of exceptions for this range type
     */
    public function __construct(RangeInterface $range, $type, array $exceptions = array())
    {
        $this->range = $range;
        $this->type = $type;
        $this->exceptions = $exceptions;
    }

    /**
     * Get the range definition.
     *
     * @return \IPLib\Range\RangeInterface
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * Get the range type.
     *
     * @return int one of the \IPLib\Range\Type::T_ constants
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the list of exceptions for this range type.
     *
     * @return \IPLib\Address\AssignedRange[]
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * Get the assigned type for a specific address.
     *
     * @param \IPLib\Address\AddressInterface $address
     *
     * @return int|null return NULL of the address is outside this address; a \IPLib\Range\Type::T_ constant otherwise
     */
    public function getAddressType(AddressInterface $address)
    {
        $result = null;
        if ($this->range->contains($address)) {
            foreach ($this->exceptions as $exception) {
                $result = $exception->getAddressType($address);
                if ($result !== null) {
                    break;
                }
            }
            if ($result === null) {
                $result = $this->type;
            }
        }

        return $result;
    }

    /**
     * Get the assigned type for a specific address range.
     *
     * @param \IPLib\Range\RangeInterface $range
     *
     * @return int|false|null return NULL of the range is fully outside this range; false if it's partly crosses this range (or it contains mixed types); a \IPLib\Range\Type::T_ constant otherwise
     */
    public function getRangeType(RangeInterface $range)
    {
        $myStart = $this->range->getComparableStartString();
        $rangeEnd = $range->getComparableEndString();
        if ($myStart > $rangeEnd) {
            $result = null;
        } else {
            $myEnd = $this->range->getComparableEndString();
            $rangeStart = $range->getComparableStartString();
            if ($myEnd < $rangeStart) {
                $result = null;
            } elseif ($rangeStart < $myStart || $rangeEnd > $myEnd) {
                $result = false;
            } else {
                $result = null;
                foreach ($this->exceptions as $exception) {
                    $result = $exception->getRangeType($range);
                    if ($result !== null) {
                        break;
                    }
                }
                if ($result === null) {
                    $result = $this->getType();
                }
            }
        }

        return $result;
    }
}
