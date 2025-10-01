<?php

namespace IPLib\Service;

use IPLib\Address\AddressInterface;
use IPLib\Factory;
use IPLib\Range\Subnet;

/**
 * Helper class to calculate the subnets describing all (and only all) the addresses between two boundaries.
 *
 * @internal
 */
class RangesFromBoundaryCalculator
{
    /**
     * The BinaryMath instance to be used to perform bitwise operations.
     *
     * @var \IPLib\Service\BinaryMath
     */
    private $math;

    /**
     * The number of bits used to represent addresses.
     *
     * @var int
     *
     * @example 32 for IPv4, 128 for IPv6
     */
    private $numBits;

    /**
     * The bit masks for every bit index.
     *
     * @var string[]
     */
    private $masks;

    /**
     * The bit unmasks for every bit index.
     *
     * @var string[]
     */
    private $unmasks;

    /**
     * Initializes the instance.
     *
     * @param int $numBits the number of bits used to represent addresses (32 for IPv4, 128 for IPv6)
     */
    public function __construct($numBits)
    {
        $this->math = new BinaryMath();
        $this->setNumBits($numBits);
    }

    /**
     * Calculate the subnets describing all (and only all) the addresses between two boundaries.
     *
     * @param \IPLib\Address\AddressInterface $from
     * @param \IPLib\Address\AddressInterface $to
     *
     * @return \IPLib\Range\Subnet[]|null return NULL if the two addresses have an invalid number of bits (that is, different from the one passed to the constructor of this class)
     */
    public function getRanges(AddressInterface $from, AddressInterface $to)
    {
        if ($from->getNumberOfBits() !== $this->numBits || $to->getNumberOfBits() !== $this->numBits) {
            return null;
        }
        if ($from->getComparableString() > $to->getComparableString()) {
            list($from, $to) = array($to, $from);
        }
        $result = array();
        $this->calculate($this->math->reduce($from->getBits()), $this->math->reduce($to->getBits()), $this->numBits, $result);

        return $result;
    }

    /**
     * Set the number of bits used to represent addresses (32 for IPv4, 128 for IPv6).
     *
     * @param int $numBits
     */
    private function setNumBits($numBits)
    {
        $numBits = (int) $numBits;
        $masks = array();
        $unmasks = array();
        for ($bit = 0; $bit < $numBits; $bit++) {
            $masks[$bit] = str_repeat('1', $numBits - $bit) . str_repeat('0', $bit);
            $unmasks[$bit] = $bit === 0 ? '0' : str_repeat('1', $bit);
        }
        $this->numBits = $numBits;
        $this->masks = $masks;
        $this->unmasks = $unmasks;
    }

    /**
     * Calculate the subnets.
     *
     * @param string $start the start address (represented in reduced bit form)
     * @param string $end the end address (represented in reduced bit form)
     * @param int $position the number of bits in the mask we are comparing at this cycle
     * @param \IPLib\Range\Subnet[] $result found ranges will be added to this variable
     */
    private function calculate($start, $end, $position, array &$result)
    {
        if ($start === $end) {
            $result[] = $this->subnetFromBits($start, $this->numBits);

            return;
        }
        for ($index = $position - 1; $index >= 0; $index--) {
            $startMasked = $this->math->andX($start, $this->masks[$index]);
            $endMasked = $this->math->andX($end, $this->masks[$index]);
            if ($startMasked !== $endMasked) {
                $position = $index;
                break;
            }
        }
        if ($startMasked === $start && $this->math->andX($this->math->increment($end), $this->unmasks[$position]) === '0') {
            $result[] = $this->subnetFromBits($start, $this->numBits - 1 - $position);

            return;
        }
        $middleAddress = $this->math->orX($start, $this->unmasks[$position]);
        $this->calculate($start, $middleAddress, $position, $result);
        $this->calculate($this->math->increment($middleAddress), $end, $position, $result);
    }

    /**
     * Create an address instance starting from its bits.
     *
     * @param string $bits the bits of the address (represented in reduced bit form)
     *
     * @return \IPLib\Address\AddressInterface
     */
    private function addressFromBits($bits)
    {
        $bits = str_pad($bits, $this->numBits, '0', STR_PAD_LEFT);
        $bytes = array();
        foreach (explode("\n", trim(chunk_split($bits, 8, "\n"))) as $byteBits) {
            $bytes[] = bindec($byteBits);
        }

        return Factory::addressFromBytes($bytes);
    }

    /**
     * Create an range instance starting from the bits if the address and the length of the network prefix.
     *
     * @param string $bits the bits of the address (represented in reduced bit form)
     * @param int $networkPrefix the length of the network prefix
     *
     * @return \IPLib\Range\Subnet
     */
    private function subnetFromBits($bits, $networkPrefix)
    {
        $startAddress = $this->addressFromBits($bits);
        $numOnes = $this->numBits - $networkPrefix;
        if ($numOnes === 0) {
            return new Subnet($startAddress, $startAddress, $networkPrefix);
        }
        $endAddress = $this->addressFromBits(substr($bits, 0, -$numOnes) . str_repeat('1', $numOnes));

        return new Subnet($startAddress, $endAddress, $networkPrefix);
    }
}
