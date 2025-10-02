<?php

namespace IPLib\Service;

/**
 * Helper class to work with unsigned binary integers.
 *
 * @internal
 */
class BinaryMath
{
    /**
     * Trim the leading zeroes from a non-negative integer represented in binary form.
     *
     * @param string $value
     *
     * @return string
     */
    public function reduce($value)
    {
        $value = ltrim($value, '0');

        return $value === '' ? '0' : $value;
    }

    /**
     * Compare two non-negative integers represented in binary form.
     *
     * @param string $a
     * @param string $b
     *
     * @return int 1 if $a is greater than $b, -1 if $b is greater than $b, 0 if they are the same
     */
    public function compare($a, $b)
    {
        list($a, $b) = $this->toSameLength($a, $b);

        return $a < $b ? -1 : ($a > $b ? 1 : 0);
    }

    /**
     * Add 1 to a non-negative integer represented in binary form.
     *
     * @param string $value
     *
     * @return string
     */
    public function increment($value)
    {
        $lastZeroIndex = strrpos($value, '0');
        if ($lastZeroIndex === false) {
            return '1' . str_repeat('0', strlen($value));
        }

        return ltrim(substr($value, 0, $lastZeroIndex), '0') . '1' . str_repeat('0', strlen($value) - $lastZeroIndex - 1);
    }

    /**
     * Calculate the bitwise AND of two non-negative integers represented in binary form.
     *
     * @param string $operand1
     * @param string $operand2
     *
     * @return string
     */
    public function andX($operand1, $operand2)
    {
        $operand1 = $this->reduce($operand1);
        $operand2 = $this->reduce($operand2);
        $numBits = min(strlen($operand1), strlen($operand2));
        $operand1 = substr(str_pad($operand1, $numBits, '0', STR_PAD_LEFT), -$numBits);
        $operand2 = substr(str_pad($operand2, $numBits, '0', STR_PAD_LEFT), -$numBits);
        $result = '';
        for ($index = 0; $index < $numBits; $index++) {
            $result .= $operand1[$index] === '1' && $operand2[$index] === '1' ? '1' : '0';
        }

        return $this->reduce($result);
    }

    /**
     * Calculate the bitwise OR of two non-negative integers represented in binary form.
     *
     * @param string $operand1
     * @param string $operand2
     *
     * @return string
     */
    public function orX($operand1, $operand2)
    {
        list($operand1, $operand2, $numBits) = $this->toSameLength($operand1, $operand2);
        $result = '';
        for ($index = 0; $index < $numBits; $index++) {
            $result .= $operand1[$index] === '1' || $operand2[$index] === '1' ? '1' : '0';
        }

        return $result;
    }

    /**
     * Zero-padding of two non-negative integers represented in binary form, so that they have the same length.
     *
     * @param string $num1
     * @param string $num2
     *
     * @return string[],int[] The first array element is $num1 (padded), the first array element is $num2 (padded), the third array element is the number of bits
     */
    private function toSameLength($num1, $num2)
    {
        $num1 = $this->reduce($num1);
        $num2 = $this->reduce($num2);
        $numBits = max(strlen($num1), strlen($num2));

        return array(
            str_pad($num1, $numBits, '0', STR_PAD_LEFT),
            str_pad($num2, $numBits, '0', STR_PAD_LEFT),
            $numBits,
        );
    }
}
