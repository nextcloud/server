<?php

/**
 * Pure-PHP 64-bit BigInteger Engine
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Math\BigInteger\Engines;

/**
 * Pure-PHP 64-bit Engine.
 *
 * Uses 64-bit integers if int size is 8 bits
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class PHP64 extends PHP
{
    // Constants used by PHP.php
    const BASE = 31;
    const BASE_FULL = 0x80000000;
    const MAX_DIGIT = 0x7FFFFFFF;
    const MSB = 0x40000000;

    /**
     * MAX10 in greatest MAX10LEN satisfying
     * MAX10 = 10**MAX10LEN <= 2**BASE.
     */
    const MAX10 = 1000000000;

    /**
     * MAX10LEN in greatest MAX10LEN satisfying
     * MAX10 = 10**MAX10LEN <= 2**BASE.
     */
    const MAX10LEN = 9;
    const MAX_DIGIT2 = 4611686018427387904;

    /**
     * Initialize a PHP64 BigInteger Engine instance
     *
     * @param int $base
     * @see parent::initialize()
     */
    protected function initialize($base)
    {
        if ($base != 256 && $base != -256) {
            return parent::initialize($base);
        }

        $val = $this->value;
        $this->value = [];
        $vals = &$this->value;
        $i = strlen($val);
        if (!$i) {
            return;
        }

        while (true) {
            $i -= 4;
            if ($i < 0) {
                if ($i == -4) {
                    break;
                }
                $val = substr($val, 0, 4 + $i);
                $val = str_pad($val, 4, "\0", STR_PAD_LEFT);
                if ($val == "\0\0\0\0") {
                    break;
                }
                $i = 0;
            }
            list(, $digit) = unpack('N', substr($val, $i, 4));
            $step = count($vals) & 7;
            if (!$step) {
                $digit &= static::MAX_DIGIT;
                $i++;
            } else {
                $shift = 8 - $step;
                $digit >>= $shift;
                $shift = 32 - $shift;
                $digit &= (1 << $shift) - 1;
                $temp = $i > 0 ? ord($val[$i - 1]) : 0;
                $digit |= ($temp << $shift) & 0x7F000000;
            }
            $vals[] = $digit;
        }
        while (end($vals) === 0) {
            array_pop($vals);
        }
        reset($vals);
    }

    /**
     * Test for engine validity
     *
     * @see parent::__construct()
     * @return bool
     */
    public static function isValidEngine()
    {
        return PHP_INT_SIZE >= 8 && !self::testJITOnWindows();
    }

    /**
     * Adds two BigIntegers.
     *
     * @param PHP64 $y
     * @return PHP64
     */
    public function add(PHP64 $y)
    {
        $temp = self::addHelper($this->value, $this->is_negative, $y->value, $y->is_negative);

        return $this->convertToObj($temp);
    }

    /**
     * Subtracts two BigIntegers.
     *
     * @param PHP64 $y
     * @return PHP64
     */
    public function subtract(PHP64 $y)
    {
        $temp = self::subtractHelper($this->value, $this->is_negative, $y->value, $y->is_negative);

        return $this->convertToObj($temp);
    }

    /**
     * Multiplies two BigIntegers.
     *
     * @param PHP64 $y
     * @return PHP64
     */
    public function multiply(PHP64 $y)
    {
        $temp = self::multiplyHelper($this->value, $this->is_negative, $y->value, $y->is_negative);

        return $this->convertToObj($temp);
    }

    /**
     * Divides two BigIntegers.
     *
     * Returns an array whose first element contains the quotient and whose second element contains the
     * "common residue".  If the remainder would be positive, the "common residue" and the remainder are the
     * same.  If the remainder would be negative, the "common residue" is equal to the sum of the remainder
     * and the divisor (basically, the "common residue" is the first positive modulo).
     *
     * @param PHP64 $y
     * @return array{PHP64, PHP64}
     */
    public function divide(PHP64 $y)
    {
        return $this->divideHelper($y);
    }

    /**
     * Calculates modular inverses.
     *
     * Say you have (30 mod 17 * x mod 17) mod 17 == 1.  x can be found using modular inverses.
     * @param PHP64 $n
     * @return false|PHP64
     */
    public function modInverse(PHP64 $n)
    {
        return $this->modInverseHelper($n);
    }

    /**
     * Calculates modular inverses.
     *
     * Say you have (30 mod 17 * x mod 17) mod 17 == 1.  x can be found using modular inverses.
     * @param PHP64 $n
     * @return PHP64[]
     */
    public function extendedGCD(PHP64 $n)
    {
        return $this->extendedGCDHelper($n);
    }

    /**
     * Calculates the greatest common divisor
     *
     * Say you have 693 and 609.  The GCD is 21.
     *
     * @param PHP64 $n
     * @return PHP64
     */
    public function gcd(PHP64 $n)
    {
        return $this->extendedGCD($n)['gcd'];
    }

    /**
     * Logical And
     *
     * @param PHP64 $x
     * @return PHP64
     */
    public function bitwise_and(PHP64 $x)
    {
        return $this->bitwiseAndHelper($x);
    }

    /**
     * Logical Or
     *
     * @param PHP64 $x
     * @return PHP64
     */
    public function bitwise_or(PHP64 $x)
    {
        return $this->bitwiseOrHelper($x);
    }

    /**
     * Logical Exclusive Or
     *
     * @param PHP64 $x
     * @return PHP64
     */
    public function bitwise_xor(PHP64 $x)
    {
        return $this->bitwiseXorHelper($x);
    }

    /**
     * Compares two numbers.
     *
     * Although one might think !$x->compare($y) means $x != $y, it, in fact, means the opposite.  The reason for this is
     * demonstrated thusly:
     *
     * $x  > $y: $x->compare($y)  > 0
     * $x  < $y: $x->compare($y)  < 0
     * $x == $y: $x->compare($y) == 0
     *
     * Note how the same comparison operator is used.  If you want to test for equality, use $x->equals($y).
     *
     * {@internal Could return $this->subtract($x), but that's not as fast as what we do do.}
     *
     * @param PHP64 $y
     * @return int in case < 0 if $this is less than $y; > 0 if $this is greater than $y, and 0 if they are equal.
     * @see self::equals()
     */
    public function compare(PHP64 $y)
    {
        return parent::compareHelper($this->value, $this->is_negative, $y->value, $y->is_negative);
    }

    /**
     * Tests the equality of two numbers.
     *
     * If you need to see if one number is greater than or less than another number, use BigInteger::compare()
     *
     * @param PHP64 $x
     * @return bool
     */
    public function equals(PHP64 $x)
    {
        return $this->value === $x->value && $this->is_negative == $x->is_negative;
    }

    /**
     * Performs modular exponentiation.
     *
     * @param PHP64 $e
     * @param PHP64 $n
     * @return PHP64
     */
    public function modPow(PHP64 $e, PHP64 $n)
    {
        return $this->powModOuter($e, $n);
    }

    /**
     * Performs modular exponentiation.
     *
     * Alias for modPow().
     *
     * @param PHP64 $e
     * @param PHP64 $n
     * @return PHP64|false
     */
    public function powMod(PHP64 $e, PHP64 $n)
    {
        return $this->powModOuter($e, $n);
    }

    /**
     * Generate a random prime number between a range
     *
     * If there's not a prime within the given range, false will be returned.
     *
     * @param PHP64 $min
     * @param PHP64 $max
     * @return false|PHP64
     */
    public static function randomRangePrime(PHP64 $min, PHP64 $max)
    {
        return self::randomRangePrimeOuter($min, $max);
    }

    /**
     * Generate a random number between a range
     *
     * Returns a random number between $min and $max where $min and $max
     * can be defined using one of the two methods:
     *
     * BigInteger::randomRange($min, $max)
     * BigInteger::randomRange($max, $min)
     *
     * @param PHP64 $min
     * @param PHP64 $max
     * @return PHP64
     */
    public static function randomRange(PHP64 $min, PHP64 $max)
    {
        return self::randomRangeHelper($min, $max);
    }

    /**
     * Performs exponentiation.
     *
     * @param PHP64 $n
     * @return PHP64
     */
    public function pow(PHP64 $n)
    {
        return $this->powHelper($n);
    }

    /**
     * Return the minimum BigInteger between an arbitrary number of BigIntegers.
     *
     * @param PHP64 ...$nums
     * @return PHP64
     */
    public static function min(PHP64 ...$nums)
    {
        return self::minHelper($nums);
    }

    /**
     * Return the maximum BigInteger between an arbitrary number of BigIntegers.
     *
     * @param PHP64 ...$nums
     * @return PHP64
     */
    public static function max(PHP64 ...$nums)
    {
        return self::maxHelper($nums);
    }

    /**
     * Tests BigInteger to see if it is between two integers, inclusive
     *
     * @param PHP64 $min
     * @param PHP64 $max
     * @return bool
     */
    public function between(PHP64 $min, PHP64 $max)
    {
        return $this->compare($min) >= 0 && $this->compare($max) <= 0;
    }
}
