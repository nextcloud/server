<?php

/**
 * BCMath BigInteger Engine
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Math\BigInteger\Engines;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Exception\BadConfigurationException;

/**
 * BCMath Engine.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class BCMath extends Engine
{
    /**
     * Can Bitwise operations be done fast?
     *
     * @see parent::bitwise_leftRotate()
     * @see parent::bitwise_rightRotate()
     */
    const FAST_BITWISE = false;

    /**
     * Engine Directory
     *
     * @see parent::setModExpEngine
     */
    const ENGINE_DIR = 'BCMath';

    /**
     * Test for engine validity
     *
     * @return bool
     * @see parent::__construct()
     */
    public static function isValidEngine()
    {
        return extension_loaded('bcmath');
    }

    /**
     * Default constructor
     *
     * @param mixed $x integer Base-10 number or base-$base number if $base set.
     * @param int $base
     * @see parent::__construct()
     */
    public function __construct($x = 0, $base = 10)
    {
        if (!isset(static::$isValidEngine[static::class])) {
            static::$isValidEngine[static::class] = self::isValidEngine();
        }
        if (!static::$isValidEngine[static::class]) {
            throw new BadConfigurationException('BCMath is not setup correctly on this system');
        }

        $this->value = '0';

        parent::__construct($x, $base);
    }

    /**
     * Initialize a BCMath BigInteger Engine instance
     *
     * @param int $base
     * @see parent::__construct()
     */
    protected function initialize($base)
    {
        switch (abs($base)) {
            case 256:
                // round $len to the nearest 4
                $len = (strlen($this->value) + 3) & ~3;

                $x = str_pad($this->value, $len, chr(0), STR_PAD_LEFT);

                $this->value = '0';
                for ($i = 0; $i < $len; $i += 4) {
                    $this->value = bcmul($this->value, '4294967296', 0); // 4294967296 == 2**32
                    $this->value = bcadd(
                        $this->value,
                        0x1000000 * ord($x[$i]) + ((ord($x[$i + 1]) << 16) | (ord(
                            $x[$i + 2]
                        ) << 8) | ord($x[$i + 3])),
                        0
                    );
                }

                if ($this->is_negative) {
                    $this->value = '-' . $this->value;
                }
                break;
            case 16:
                $x = (strlen($this->value) & 1) ? '0' . $this->value : $this->value;
                $temp = new self(Strings::hex2bin($x), 256);
                $this->value = $this->is_negative ? '-' . $temp->value : $temp->value;
                $this->is_negative = false;
                break;
            case 10:
                // explicitly casting $x to a string is necessary, here, since doing $x[0] on -1 yields different
                // results then doing it on '-1' does (modInverse does $x[0])
                $this->value = $this->value === '-' ? '0' : (string)$this->value;
        }
    }

    /**
     * Converts a BigInteger to a base-10 number.
     *
     * @return string
     */
    public function toString()
    {
        if ($this->value === '0') {
            return '0';
        }

        return ltrim($this->value, '0');
    }

    /**
     * Converts a BigInteger to a byte string (eg. base-256).
     *
     * @param bool $twos_compliment
     * @return string
     */
    public function toBytes($twos_compliment = false)
    {
        if ($twos_compliment) {
            return $this->toBytesHelper();
        }

        $value = '';
        $current = $this->value;

        if ($current[0] == '-') {
            $current = substr($current, 1);
        }

        while (bccomp($current, '0', 0) > 0) {
            $temp = bcmod($current, '16777216');
            $value = chr($temp >> 16) . chr($temp >> 8) . chr($temp) . $value;
            $current = bcdiv($current, '16777216', 0);
        }

        return $this->precision > 0 ?
            substr(str_pad($value, $this->precision >> 3, chr(0), STR_PAD_LEFT), -($this->precision >> 3)) :
            ltrim($value, chr(0));
    }

    /**
     * Adds two BigIntegers.
     *
     * @param BCMath $y
     * @return BCMath
     */
    public function add(BCMath $y)
    {
        $temp = new self();
        $temp->value = bcadd($this->value, $y->value);

        return $this->normalize($temp);
    }

    /**
     * Subtracts two BigIntegers.
     *
     * @param BCMath $y
     * @return BCMath
     */
    public function subtract(BCMath $y)
    {
        $temp = new self();
        $temp->value = bcsub($this->value, $y->value);

        return $this->normalize($temp);
    }

    /**
     * Multiplies two BigIntegers.
     *
     * @param BCMath $x
     * @return BCMath
     */
    public function multiply(BCMath $x)
    {
        $temp = new self();
        $temp->value = bcmul($this->value, $x->value);

        return $this->normalize($temp);
    }

    /**
     * Divides two BigIntegers.
     *
     * Returns an array whose first element contains the quotient and whose second element contains the
     * "common residue".  If the remainder would be positive, the "common residue" and the remainder are the
     * same.  If the remainder would be negative, the "common residue" is equal to the sum of the remainder
     * and the divisor (basically, the "common residue" is the first positive modulo).
     *
     * @param BCMath $y
     * @return array{static, static}
     */
    public function divide(BCMath $y)
    {
        $quotient = new self();
        $remainder = new self();

        $quotient->value = bcdiv($this->value, $y->value, 0);
        $remainder->value = bcmod($this->value, $y->value);

        if ($remainder->value[0] == '-') {
            $remainder->value = bcadd($remainder->value, $y->value[0] == '-' ? substr($y->value, 1) : $y->value, 0);
        }

        return [$this->normalize($quotient), $this->normalize($remainder)];
    }

    /**
     * Calculates modular inverses.
     *
     * Say you have (30 mod 17 * x mod 17) mod 17 == 1.  x can be found using modular inverses.
     *
     * @param BCMath $n
     * @return false|BCMath
     */
    public function modInverse(BCMath $n)
    {
        return $this->modInverseHelper($n);
    }

    /**
     * Calculates the greatest common divisor and Bezout's identity.
     *
     * Say you have 693 and 609.  The GCD is 21.  Bezout's identity states that there exist integers x and y such that
     * 693*x + 609*y == 21.  In point of fact, there are actually an infinite number of x and y combinations and which
     * combination is returned is dependent upon which mode is in use.  See
     * {@link http://en.wikipedia.org/wiki/B%C3%A9zout%27s_identity Bezout's identity - Wikipedia} for more information.
     *
     * @param BCMath $n
     * @return array{gcd: static, x: static, y: static}
     */
    public function extendedGCD(BCMath $n)
    {
        // it might be faster to use the binary xGCD algorithim here, as well, but (1) that algorithim works
        // best when the base is a power of 2 and (2) i don't think it'd make much difference, anyway.  as is,
        // the basic extended euclidean algorithim is what we're using.

        $u = $this->value;
        $v = $n->value;

        $a = '1';
        $b = '0';
        $c = '0';
        $d = '1';

        while (bccomp($v, '0', 0) != 0) {
            $q = bcdiv($u, $v, 0);

            $temp = $u;
            $u = $v;
            $v = bcsub($temp, bcmul($v, $q, 0), 0);

            $temp = $a;
            $a = $c;
            $c = bcsub($temp, bcmul($a, $q, 0), 0);

            $temp = $b;
            $b = $d;
            $d = bcsub($temp, bcmul($b, $q, 0), 0);
        }

        return [
            'gcd' => $this->normalize(new static($u)),
            'x' => $this->normalize(new static($a)),
            'y' => $this->normalize(new static($b))
        ];
    }

    /**
     * Calculates the greatest common divisor
     *
     * Say you have 693 and 609.  The GCD is 21.
     *
     * @param BCMath $n
     * @return BCMath
     */
    public function gcd(BCMath $n)
    {
        extract($this->extendedGCD($n));
        /** @var BCMath $gcd */
        return $gcd;
    }

    /**
     * Absolute value.
     *
     * @return BCMath
     */
    public function abs()
    {
        $temp = new static();
        $temp->value = strlen($this->value) && $this->value[0] == '-' ?
            substr($this->value, 1) :
            $this->value;

        return $temp;
    }

    /**
     * Logical And
     *
     * @param BCMath $x
     * @return BCMath
     */
    public function bitwise_and(BCMath $x)
    {
        return $this->bitwiseAndHelper($x);
    }

    /**
     * Logical Or
     *
     * @param BCMath $x
     * @return BCMath
     */
    public function bitwise_or(BCMath $x)
    {
        return $this->bitwiseXorHelper($x);
    }

    /**
     * Logical Exclusive Or
     *
     * @param BCMath $x
     * @return BCMath
     */
    public function bitwise_xor(BCMath $x)
    {
        return $this->bitwiseXorHelper($x);
    }

    /**
     * Logical Right Shift
     *
     * Shifts BigInteger's by $shift bits, effectively dividing by 2**$shift.
     *
     * @param int $shift
     * @return BCMath
     */
    public function bitwise_rightShift($shift)
    {
        $temp = new static();
        $temp->value = bcdiv($this->value, bcpow('2', $shift, 0), 0);

        return $this->normalize($temp);
    }

    /**
     * Logical Left Shift
     *
     * Shifts BigInteger's by $shift bits, effectively multiplying by 2**$shift.
     *
     * @param int $shift
     * @return BCMath
     */
    public function bitwise_leftShift($shift)
    {
        $temp = new static();
        $temp->value = bcmul($this->value, bcpow('2', $shift, 0), 0);

        return $this->normalize($temp);
    }

    /**
     * Compares two numbers.
     *
     * Although one might think !$x->compare($y) means $x != $y, it, in fact, means the opposite.  The reason for this
     * is demonstrated thusly:
     *
     * $x  > $y: $x->compare($y)  > 0
     * $x  < $y: $x->compare($y)  < 0
     * $x == $y: $x->compare($y) == 0
     *
     * Note how the same comparison operator is used.  If you want to test for equality, use $x->equals($y).
     *
     * {@internal Could return $this->subtract($x), but that's not as fast as what we do do.}
     *
     * @param BCMath $y
     * @return int in case < 0 if $this is less than $y; > 0 if $this is greater than $y, and 0 if they are equal.
     * @see self::equals()
     */
    public function compare(BCMath $y)
    {
        return bccomp($this->value, $y->value, 0);
    }

    /**
     * Tests the equality of two numbers.
     *
     * If you need to see if one number is greater than or less than another number, use BigInteger::compare()
     *
     * @param BCMath $x
     * @return bool
     */
    public function equals(BCMath $x)
    {
        return $this->value == $x->value;
    }

    /**
     * Performs modular exponentiation.
     *
     * @param BCMath $e
     * @param BCMath $n
     * @return BCMath
     */
    public function modPow(BCMath $e, BCMath $n)
    {
        return $this->powModOuter($e, $n);
    }

    /**
     * Performs modular exponentiation.
     *
     * Alias for modPow().
     *
     * @param BCMath $e
     * @param BCMath $n
     * @return BCMath
     */
    public function powMod(BCMath $e, BCMath $n)
    {
        return $this->powModOuter($e, $n);
    }

    /**
     * Performs modular exponentiation.
     *
     * @param BCMath $e
     * @param BCMath $n
     * @return BCMath
     */
    protected function powModInner(BCMath $e, BCMath $n)
    {
        try {
            $class = static::$modexpEngine[static::class];
            return $class::powModHelper($this, $e, $n, static::class);
        } catch (\Exception $err) {
            return BCMath\DefaultEngine::powModHelper($this, $e, $n, static::class);
        }
    }

    /**
     * Normalize
     *
     * Removes leading zeros and truncates (if necessary) to maintain the appropriate precision
     *
     * @param BCMath $result
     * @return BCMath
     */
    protected function normalize(BCMath $result)
    {
        $result->precision = $this->precision;
        $result->bitmask = $this->bitmask;

        if ($result->bitmask !== false) {
            $result->value = bcmod($result->value, $result->bitmask->value);
        }

        return $result;
    }

    /**
     * Generate a random prime number between a range
     *
     * If there's not a prime within the given range, false will be returned.
     *
     * @param BCMath $min
     * @param BCMath $max
     * @return false|BCMath
     */
    public static function randomRangePrime(BCMath $min, BCMath $max)
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
     * @param BCMath $min
     * @param BCMath $max
     * @return BCMath
     */
    public static function randomRange(BCMath $min, BCMath $max)
    {
        return self::randomRangeHelper($min, $max);
    }

    /**
     * Make the current number odd
     *
     * If the current number is odd it'll be unchanged.  If it's even, one will be added to it.
     *
     * @see self::randomPrime()
     */
    protected function make_odd()
    {
        if (!$this->isOdd()) {
            $this->value = bcadd($this->value, '1');
        }
    }

    /**
     * Test the number against small primes.
     *
     * @see self::isPrime()
     */
    protected function testSmallPrimes()
    {
        if ($this->value === '1') {
            return false;
        }
        if ($this->value === '2') {
            return true;
        }
        if ($this->value[strlen($this->value) - 1] % 2 == 0) {
            return false;
        }

        $value = $this->value;

        foreach (self::PRIMES as $prime) {
            $r = bcmod($this->value, $prime);
            if ($r == '0') {
                return $this->value == $prime;
            }
        }

        return true;
    }

    /**
     * Scan for 1 and right shift by that amount
     *
     * ie. $s = gmp_scan1($n, 0) and $r = gmp_div_q($n, gmp_pow(gmp_init('2'), $s));
     *
     * @param BCMath $r
     * @return int
     * @see self::isPrime()
     */
    public static function scan1divide(BCMath $r)
    {
        $r_value = &$r->value;
        $s = 0;
        // if $n was 1, $r would be 0 and this would be an infinite loop, hence our $this->equals(static::$one[static::class]) check earlier
        while ($r_value[strlen($r_value) - 1] % 2 == 0) {
            $r_value = bcdiv($r_value, '2', 0);
            ++$s;
        }

        return $s;
    }

    /**
     * Performs exponentiation.
     *
     * @param BCMath $n
     * @return BCMath
     */
    public function pow(BCMath $n)
    {
        $temp = new self();
        $temp->value = bcpow($this->value, $n->value);

        return $this->normalize($temp);
    }

    /**
     * Return the minimum BigInteger between an arbitrary number of BigIntegers.
     *
     * @param BCMath ...$nums
     * @return BCMath
     */
    public static function min(BCMath ...$nums)
    {
        return self::minHelper($nums);
    }

    /**
     * Return the maximum BigInteger between an arbitrary number of BigIntegers.
     *
     * @param BCMath ...$nums
     * @return BCMath
     */
    public static function max(BCMath ...$nums)
    {
        return self::maxHelper($nums);
    }

    /**
     * Tests BigInteger to see if it is between two integers, inclusive
     *
     * @param BCMath $min
     * @param BCMath $max
     * @return bool
     */
    public function between(BCMath $min, BCMath $max)
    {
        return $this->compare($min) >= 0 && $this->compare($max) <= 0;
    }

    /**
     * Set Bitmask
     *
     * @param int $bits
     * @return Engine
     * @see self::setPrecision()
     */
    protected static function setBitmask($bits)
    {
        $temp = parent::setBitmask($bits);
        return $temp->add(static::$one[static::class]);
    }

    /**
     * Is Odd?
     *
     * @return bool
     */
    public function isOdd()
    {
        return $this->value[strlen($this->value) - 1] % 2 == 1;
    }

    /**
     * Tests if a bit is set
     *
     * @return bool
     */
    public function testBit($x)
    {
        return bccomp(
            bcmod($this->value, bcpow('2', $x + 1, 0)),
            bcpow('2', $x, 0),
            0
        ) >= 0;
    }

    /**
     * Is Negative?
     *
     * @return bool
     */
    public function isNegative()
    {
        return strlen($this->value) && $this->value[0] == '-';
    }

    /**
     * Negate
     *
     * Given $k, returns -$k
     *
     * @return BCMath
     */
    public function negate()
    {
        $temp = clone $this;

        if (!strlen($temp->value)) {
            return $temp;
        }

        $temp->value = $temp->value[0] == '-' ?
            substr($this->value, 1) :
            '-' . $this->value;

        return $temp;
    }
}
