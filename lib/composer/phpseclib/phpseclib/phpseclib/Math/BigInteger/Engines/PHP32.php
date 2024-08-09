<?php

/**
 * Pure-PHP 32-bit BigInteger Engine
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
 * Pure-PHP 32-bit Engine.
 *
 * Uses 64-bit floats if int size is 4 bits
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class PHP32 extends PHP
{
    // Constants used by PHP.php
    const BASE = 26;
    const BASE_FULL = 0x4000000;
    const MAX_DIGIT = 0x3FFFFFF;
    const MSB = 0x2000000;

    /**
     * MAX10 in greatest MAX10LEN satisfying
     * MAX10 = 10**MAX10LEN <= 2**BASE.
     */
    const MAX10 = 10000000;

    /**
     * MAX10LEN in greatest MAX10LEN satisfying
     * MAX10 = 10**MAX10LEN <= 2**BASE.
     */
    const MAX10LEN = 7;
    const MAX_DIGIT2 = 4503599627370496;

    /**
     * Initialize a PHP32 BigInteger Engine instance
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
            if ($digit < 0) {
                $digit += 0xFFFFFFFF + 1;
            }
            $step = count($vals) & 3;
            if ($step) {
                $digit = (int) floor($digit / pow(2, 2 * $step));
            }
            if ($step != 3) {
                $digit = (int) fmod($digit, static::BASE_FULL);
                $i++;
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
        return PHP_INT_SIZE >= 4 && !self::testJITOnWindows();
    }

    /**
     * Adds two BigIntegers.
     *
     * @param PHP32 $y
     * @return PHP32
     */
    public function add(PHP32 $y)
    {
        $temp = self::addHelper($this->value, $this->is_negative, $y->value, $y->is_negative);

        return $this->convertToObj($temp);
    }

    /**
     * Subtracts two BigIntegers.
     *
     * @param PHP32 $y
     * @return PHP32
     */
    public function subtract(PHP32 $y)
    {
        $temp = self::subtractHelper($this->value, $this->is_negative, $y->value, $y->is_negative);

        return $this->convertToObj($temp);
    }

    /**
     * Multiplies two BigIntegers.
     *
     * @param PHP32 $y
     * @return PHP32
     */
    public function multiply(PHP32 $y)
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
     * @param PHP32 $y
     * @return array{PHP32, PHP32}
     */
    public function divide(PHP32 $y)
    {
        return $this->divideHelper($y);
    }

    /**
     * Calculates modular inverses.
     *
     * Say you have (30 mod 17 * x mod 17) mod 17 == 1.  x can be found using modular inverses.
     * @param PHP32 $n
     * @return false|PHP32
     */
    public function modInverse(PHP32 $n)
    {
        return $this->modInverseHelper($n);
    }

    /**
     * Calculates modular inverses.
     *
     * Say you have (30 mod 17 * x mod 17) mod 17 == 1.  x can be found using modular inverses.
     * @param PHP32 $n
     * @return PHP32[]
     */
    public function extendedGCD(PHP32 $n)
    {
        return $this->extendedGCDHelper($n);
    }

    /**
     * Calculates the greatest common divisor
     *
     * Say you have 693 and 609.  The GCD is 21.
     *
     * @param PHP32 $n
     * @return PHP32
     */
    public function gcd(PHP32 $n)
    {
        return $this->extendedGCD($n)['gcd'];
    }

    /**
     * Logical And
     *
     * @param PHP32 $x
     * @return PHP32
     */
    public function bitwise_and(PHP32 $x)
    {
        return $this->bitwiseAndHelper($x);
    }

    /**
     * Logical Or
     *
     * @param PHP32 $x
     * @return PHP32
     */
    public function bitwise_or(PHP32 $x)
    {
        return $this->bitwiseOrHelper($x);
    }

    /**
     * Logical Exclusive Or
     *
     * @param PHP32 $x
     * @return PHP32
     */
    public function bitwise_xor(PHP32 $x)
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
     * @param PHP32 $y
     * @return int in case < 0 if $this is less than $y; > 0 if $this is greater than $y, and 0 if they are equal.
     * @see self::equals()
     */
    public function compare(PHP32 $y)
    {
        return $this->compareHelper($this->value, $this->is_negative, $y->value, $y->is_negative);
    }

    /**
     * Tests the equality of two numbers.
     *
     * If you need to see if one number is greater than or less than another number, use BigInteger::compare()
     *
     * @param PHP32 $x
     * @return bool
     */
    public function equals(PHP32 $x)
    {
        return $this->value === $x->value && $this->is_negative == $x->is_negative;
    }

    /**
     * Performs modular exponentiation.
     *
     * @param PHP32 $e
     * @param PHP32 $n
     * @return PHP32
     */
    public function modPow(PHP32 $e, PHP32 $n)
    {
        return $this->powModOuter($e, $n);
    }

    /**
     * Performs modular exponentiation.
     *
     * Alias for modPow().
     *
     * @param PHP32 $e
     * @param PHP32 $n
     * @return PHP32
     */
    public function powMod(PHP32 $e, PHP32 $n)
    {
        return $this->powModOuter($e, $n);
    }

    /**
     * Generate a random prime number between a range
     *
     * If there's not a prime within the given range, false will be returned.
     *
     * @param PHP32 $min
     * @param PHP32 $max
     * @return false|PHP32
     */
    public static function randomRangePrime(PHP32 $min, PHP32 $max)
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
     * @param PHP32 $min
     * @param PHP32 $max
     * @return PHP32
     */
    public static function randomRange(PHP32 $min, PHP32 $max)
    {
        return self::randomRangeHelper($min, $max);
    }

    /**
     * Performs exponentiation.
     *
     * @param PHP32 $n
     * @return PHP32
     */
    public function pow(PHP32 $n)
    {
        return $this->powHelper($n);
    }

    /**
     * Return the minimum BigInteger between an arbitrary number of BigIntegers.
     *
     * @param PHP32 ...$nums
     * @return PHP32
     */
    public static function min(PHP32 ...$nums)
    {
        return self::minHelper($nums);
    }

    /**
     * Return the maximum BigInteger between an arbitrary number of BigIntegers.
     *
     * @param PHP32 ...$nums
     * @return PHP32
     */
    public static function max(PHP32 ...$nums)
    {
        return self::maxHelper($nums);
    }

    /**
     * Tests BigInteger to see if it is between two integers, inclusive
     *
     * @param PHP32 $min
     * @param PHP32 $max
     * @return bool
     */
    public function between(PHP32 $min, PHP32 $max)
    {
        return $this->compare($min) >= 0 && $this->compare($max) <= 0;
    }
}
