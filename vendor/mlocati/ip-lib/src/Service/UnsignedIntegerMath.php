<?php

namespace IPLib\Service;

/**
 * Helper class to work with unsigned integers.
 *
 * @internal
 */
class UnsignedIntegerMath
{
    /**
     * Convert a string containing a decimal, octal or hexadecimal number into its bytes.
     *
     * @param string $value
     * @param int $numBytes the wanted number of bytes
     * @param bool $onlyDecimal Only parse decimal numbers
     *
     * @return int[]|null
     */
    public function getBytes($value, $numBytes, $onlyDecimal = false)
    {
        $m = null;
        if ($onlyDecimal) {
            if (preg_match('/^0*(\d+)$/', $value, $m)) {
                return $this->getBytesFromDecimal($m[1], $numBytes);
            }
        } else {
            if (preg_match('/^0[Xx]0*([0-9A-Fa-f]+)$/', $value, $m)) {
                return $this->getBytesFromHexadecimal($m[1], $numBytes);
            }
            if (preg_match('/^0+([0-7]*)$/', $value, $m)) {
                return $this->getBytesFromOctal($m[1], $numBytes);
            }
            if (preg_match('/^[1-9][0-9]*$/', $value)) {
                return $this->getBytesFromDecimal($value, $numBytes);
            }
        }

        // Not a valid number
        return null;
    }

    /**
     * @return int
     */
    protected function getMaxSignedInt()
    {
        return PHP_INT_MAX;
    }

    /**
     * @param string $value never zero-length, never extra leading zeroes
     * @param int $numBytes
     *
     * @return int[]|null
     */
    private function getBytesFromBits($value, $numBytes)
    {
        $valueLength = strlen($value);
        if ($valueLength > $numBytes << 3) {
            // overflow
            return null;
        }
        $remainderBits = $valueLength % 8;
        if ($remainderBits !== 0) {
            $value = str_pad($value, $valueLength + 8 - $remainderBits, '0', STR_PAD_LEFT);
        }
        $bytes = array_map('bindec', str_split($value, 8));

        return array_pad($bytes, -$numBytes, 0);
    }

    /**
     * @param string $value may be zero-length, never extra leading zeroes
     * @param int $numBytes
     *
     * @return int[]|null
     */
    private function getBytesFromOctal($value, $numBytes)
    {
        if ($value === '') {
            return array_fill(0, $numBytes, 0);
        }
        $bits = implode(
            '',
            array_map(
                function ($octalDigit) {
                    return str_pad(decbin(octdec($octalDigit)), 3, '0', STR_PAD_LEFT);
                },
                str_split($value, 1)
            )
        );
        $bits = ltrim($bits, '0');

        return $bits === '' ? array_fill(0, $numBytes, 0) : static::getBytesFromBits($bits, $numBytes);
    }

    /**
     * @param string $value never zero-length, never extra leading zeroes
     * @param int $numBytes
     *
     * @return int[]|null
     */
    private function getBytesFromDecimal($value, $numBytes)
    {
        $valueLength = strlen($value);
        $maxSignedIntLength = strlen((string) $this->getMaxSignedInt());
        if ($valueLength < $maxSignedIntLength) {
            return $this->getBytesFromBits(decbin((int) $value), $numBytes);
        }
        // Divide by two, so that we have 1 less bit
        $carry = 0;
        $halfValue = ltrim(
            implode(
                '',
                array_map(
                    function ($digit) use (&$carry) {
                        $number = $carry + (int) $digit;
                        $carry = ($number % 2) * 10;

                        return (string) $number >> 1;
                    },
                    str_split($value, 1)
                )
            ),
            '0'
        );
        $halfValueBytes = $this->getBytesFromDecimal($halfValue, $numBytes);
        if ($halfValueBytes === null) {
            return null;
        }
        $carry = $carry === 0 ? 0 : 1;
        $result = array_fill(0, $numBytes, 0);
        for ($index = $numBytes - 1; $index >= 0; $index--) {
            $byte = $carry + ($halfValueBytes[$index] << 1);
            if ($byte <= 0xFF) {
                $carry = 0;
            } else {
                $carry = ($byte & ~0xFF) >> 8;
                $byte -= 0x100;
            }
            $result[$index] = $byte;
        }
        if ($carry !== 0) {
            // Overflow
            return null;
        }

        return $result;
    }

    /**
     * @param string $value never zero-length, never extra leading zeroes
     * @param int $numBytes
     *
     * @return int[]|null
     */
    private function getBytesFromHexadecimal($value, $numBytes)
    {
        $valueLength = strlen($value);
        if ($valueLength > $numBytes << 1) {
            // overflow
            return null;
        }
        $value = str_pad($value, $valueLength + $valueLength % 2, '0', STR_PAD_LEFT);
        $bytes = array_map('hexdec', str_split($value, 2));

        return array_pad($bytes, -$numBytes, 0);
    }
}
