<?php

/**
 * Binary Finite Fields
 *
 * In a binary finite field numbers are actually polynomial equations. If you
 * represent the number as a sequence of bits you get a sequence of 1's or 0's.
 * These 1's or 0's represent the coefficients of the x**n, where n is the
 * location of the given bit. When you add numbers over a binary finite field
 * the result should have a coefficient of 1 or 0 as well. Hence addition
 * and subtraction become the same operation as XOR.
 * eg. 1 + 1 + 1 == 3 % 2 == 1 or 0 - 1 == -1 % 2 == 1
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace phpseclib3\Math\BinaryField;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Math\BigInteger;
use phpseclib3\Math\BinaryField;
use phpseclib3\Math\Common\FiniteField\Integer as Base;

/**
 * Binary Finite Fields
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class Integer extends Base
{
    /**
     * Holds the BinaryField's value
     *
     * @var string
     */
    protected $value;

    /**
     * Keeps track of current instance
     *
     * @var int
     */
    protected $instanceID;

    /**
     * Holds the PrimeField's modulo
     *
     * @var array<int, string>
     */
    protected static $modulo;

    /**
     * Holds a pre-generated function to perform modulo reductions
     *
     * @var callable[]
     */
    protected static $reduce;

    /**
     * Default constructor
     */
    public function __construct($instanceID, $num = '')
    {
        $this->instanceID = $instanceID;
        if (!strlen($num)) {
            $this->value = '';
        } else {
            $reduce = static::$reduce[$instanceID];
            $this->value = $reduce($num);
        }
    }

    /**
     * Set the modulo for a given instance
     * @param int $instanceID
     * @param string $modulo
     */
    public static function setModulo($instanceID, $modulo)
    {
        static::$modulo[$instanceID] = $modulo;
    }

    /**
     * Set the modulo for a given instance
     */
    public static function setRecurringModuloFunction($instanceID, callable $function)
    {
        static::$reduce[$instanceID] = $function;
    }

    /**
     * Tests a parameter to see if it's of the right instance
     *
     * Throws an exception if the incorrect class is being utilized
     */
    private static function checkInstance(self $x, self $y)
    {
        if ($x->instanceID != $y->instanceID) {
            throw new \UnexpectedValueException('The instances of the two BinaryField\Integer objects do not match');
        }
    }

    /**
     * Tests the equality of two numbers.
     *
     * @return bool
     */
    public function equals(self $x)
    {
        static::checkInstance($this, $x);

        return $this->value == $x->value;
    }

    /**
     * Compares two numbers.
     *
     * @return int
     */
    public function compare(self $x)
    {
        static::checkInstance($this, $x);

        $a = $this->value;
        $b = $x->value;

        $length = max(strlen($a), strlen($b));

        $a = str_pad($a, $length, "\0", STR_PAD_LEFT);
        $b = str_pad($b, $length, "\0", STR_PAD_LEFT);

        return strcmp($a, $b);
    }

    /**
     * Returns the degree of the polynomial
     *
     * @param string $x
     * @return int
     */
    private static function deg($x)
    {
        $x = ltrim($x, "\0");
        $xbit = decbin(ord($x[0]));
        $xlen = $xbit == '0' ? 0 : strlen($xbit);
        $len = strlen($x);
        if (!$len) {
            return -1;
        }
        return 8 * strlen($x) - 9 + $xlen;
    }

    /**
     * Perform polynomial division
     *
     * @return string[]
     * @link https://en.wikipedia.org/wiki/Polynomial_greatest_common_divisor#Euclidean_division
     */
    private static function polynomialDivide($x, $y)
    {
        // in wikipedia's description of the algorithm, lc() is the leading coefficient. over a binary field that's
        // always going to be 1.

        $q = chr(0);
        $d = static::deg($y);
        $r = $x;
        while (($degr = static::deg($r)) >= $d) {
            $s = '1' . str_repeat('0', $degr - $d);
            $s = BinaryField::base2ToBase256($s);
            $length = max(strlen($s), strlen($q));
            $q = !isset($q) ? $s :
                str_pad($q, $length, "\0", STR_PAD_LEFT) ^
                str_pad($s, $length, "\0", STR_PAD_LEFT);
            $s = static::polynomialMultiply($s, $y);
            $length = max(strlen($r), strlen($s));
            $r = str_pad($r, $length, "\0", STR_PAD_LEFT) ^
                 str_pad($s, $length, "\0", STR_PAD_LEFT);
        }

        return [ltrim($q, "\0"), ltrim($r, "\0")];
    }

    /**
     * Perform polynomial multiplation in the traditional way
     *
     * @return string
     * @link https://en.wikipedia.org/wiki/Finite_field_arithmetic#Multiplication
     */
    private static function regularPolynomialMultiply($x, $y)
    {
        $precomputed = [ltrim($x, "\0")];
        $x = strrev(BinaryField::base256ToBase2($x));
        $y = strrev(BinaryField::base256ToBase2($y));
        if (strlen($x) == strlen($y)) {
            $length = strlen($x);
        } else {
            $length = max(strlen($x), strlen($y));
            $x = str_pad($x, $length, '0');
            $y = str_pad($y, $length, '0');
        }
        $result = str_repeat('0', 2 * $length - 1);
        $result = BinaryField::base2ToBase256($result);
        $size = strlen($result);
        $x = strrev($x);

        // precompute left shift 1 through 7
        for ($i = 1; $i < 8; $i++) {
            $precomputed[$i] = BinaryField::base2ToBase256($x . str_repeat('0', $i));
        }
        for ($i = 0; $i < strlen($y); $i++) {
            if ($y[$i] == '1') {
                $temp = $precomputed[$i & 7] . str_repeat("\0", $i >> 3);
                $result ^= str_pad($temp, $size, "\0", STR_PAD_LEFT);
            }
        }

        return $result;
    }

    /**
     * Perform polynomial multiplation
     *
     * Uses karatsuba multiplication to reduce x-bit multiplications to a series of 32-bit multiplications
     *
     * @return string
     * @link https://en.wikipedia.org/wiki/Karatsuba_algorithm
     */
    private static function polynomialMultiply($x, $y)
    {
        if (strlen($x) == strlen($y)) {
            $length = strlen($x);
        } else {
            $length = max(strlen($x), strlen($y));
            $x = str_pad($x, $length, "\0", STR_PAD_LEFT);
            $y = str_pad($y, $length, "\0", STR_PAD_LEFT);
        }

        switch (true) {
            case PHP_INT_SIZE == 8 && $length <= 4:
                return $length != 4 ?
                    self::subMultiply(str_pad($x, 4, "\0", STR_PAD_LEFT), str_pad($y, 4, "\0", STR_PAD_LEFT)) :
                    self::subMultiply($x, $y);
            case PHP_INT_SIZE == 4 || $length > 32:
                return self::regularPolynomialMultiply($x, $y);
        }

        $m = $length >> 1;

        $x1 = substr($x, 0, -$m);
        $x0 = substr($x, -$m);
        $y1 = substr($y, 0, -$m);
        $y0 = substr($y, -$m);

        $z2 = self::polynomialMultiply($x1, $y1);
        $z0 = self::polynomialMultiply($x0, $y0);
        $z1 = self::polynomialMultiply(
            self::subAdd2($x1, $x0),
            self::subAdd2($y1, $y0)
        );

        $z1 = self::subAdd3($z1, $z2, $z0);

        $xy = self::subAdd3(
            $z2 . str_repeat("\0", 2 * $m),
            $z1 . str_repeat("\0", $m),
            $z0
        );

        return ltrim($xy, "\0");
    }

    /**
     * Perform polynomial multiplication on 2x 32-bit numbers, returning
     * a 64-bit number
     *
     * @param string $x
     * @param string $y
     * @return string
     * @link https://www.bearssl.org/constanttime.html#ghash-for-gcm
     */
    private static function subMultiply($x, $y)
    {
        $x = unpack('N', $x)[1];
        $y = unpack('N', $y)[1];

        $x0 = $x & 0x11111111;
        $x1 = $x & 0x22222222;
        $x2 = $x & 0x44444444;
        $x3 = $x & 0x88888888;

        $y0 = $y & 0x11111111;
        $y1 = $y & 0x22222222;
        $y2 = $y & 0x44444444;
        $y3 = $y & 0x88888888;

        $z0 = ($x0 * $y0) ^ ($x1 * $y3) ^ ($x2 * $y2) ^ ($x3 * $y1);
        $z1 = ($x0 * $y1) ^ ($x1 * $y0) ^ ($x2 * $y3) ^ ($x3 * $y2);
        $z2 = ($x0 * $y2) ^ ($x1 * $y1) ^ ($x2 * $y0) ^ ($x3 * $y3);
        $z3 = ($x0 * $y3) ^ ($x1 * $y2) ^ ($x2 * $y1) ^ ($x3 * $y0);

        $z0 &= 0x1111111111111111;
        $z1 &= 0x2222222222222222;
        $z2 &= 0x4444444444444444;
        $z3 &= -8608480567731124088; // 0x8888888888888888 gets interpreted as a float

        $z = $z0 | $z1 | $z2 | $z3;

        return pack('J', $z);
    }

    /**
     * Adds two numbers
     *
     * @param string $x
     * @param string $y
     * @return string
     */
    private static function subAdd2($x, $y)
    {
        $length = max(strlen($x), strlen($y));
        $x = str_pad($x, $length, "\0", STR_PAD_LEFT);
        $y = str_pad($y, $length, "\0", STR_PAD_LEFT);
        return $x ^ $y;
    }

    /**
     * Adds three numbers
     *
     * @param string $x
     * @param string $y
     * @return string
     */
    private static function subAdd3($x, $y, $z)
    {
        $length = max(strlen($x), strlen($y), strlen($z));
        $x = str_pad($x, $length, "\0", STR_PAD_LEFT);
        $y = str_pad($y, $length, "\0", STR_PAD_LEFT);
        $z = str_pad($z, $length, "\0", STR_PAD_LEFT);
        return $x ^ $y ^ $z;
    }

    /**
     * Adds two BinaryFieldIntegers.
     *
     * @return static
     */
    public function add(self $y)
    {
        static::checkInstance($this, $y);

        $length = strlen(static::$modulo[$this->instanceID]);

        $x = str_pad($this->value, $length, "\0", STR_PAD_LEFT);
        $y = str_pad($y->value, $length, "\0", STR_PAD_LEFT);

        return new static($this->instanceID, $x ^ $y);
    }

    /**
     * Subtracts two BinaryFieldIntegers.
     *
     * @return static
     */
    public function subtract(self $x)
    {
        return $this->add($x);
    }

    /**
     * Multiplies two BinaryFieldIntegers.
     *
     * @return static
     */
    public function multiply(self $y)
    {
        static::checkInstance($this, $y);

        return new static($this->instanceID, static::polynomialMultiply($this->value, $y->value));
    }

    /**
     * Returns the modular inverse of a BinaryFieldInteger
     *
     * @return static
     */
    public function modInverse()
    {
        $remainder0 = static::$modulo[$this->instanceID];
        $remainder1 = $this->value;

        if ($remainder1 == '') {
            return new static($this->instanceID);
        }

        $aux0 = "\0";
        $aux1 = "\1";
        while ($remainder1 != "\1") {
            list($q, $r) = static::polynomialDivide($remainder0, $remainder1);
            $remainder0 = $remainder1;
            $remainder1 = $r;
            // the auxiliary in row n is given by the sum of the auxiliary in
            // row n-2 and the product of the quotient and the auxiliary in row
            // n-1
            $temp = static::polynomialMultiply($aux1, $q);
            $aux = str_pad($aux0, strlen($temp), "\0", STR_PAD_LEFT) ^
                   str_pad($temp, strlen($aux0), "\0", STR_PAD_LEFT);
            $aux0 = $aux1;
            $aux1 = $aux;
        }

        $temp = new static($this->instanceID);
        $temp->value = ltrim($aux1, "\0");
        return $temp;
    }

    /**
     * Divides two PrimeFieldIntegers.
     *
     * @return static
     */
    public function divide(self $x)
    {
        static::checkInstance($this, $x);

        $x = $x->modInverse();
        return $this->multiply($x);
    }

    /**
     * Negate
     *
     * A negative number can be written as 0-12. With modulos, 0 is the same thing as the modulo
     * so 0-12 is the same thing as modulo-12
     *
     * @return object
     */
    public function negate()
    {
        $x = str_pad($this->value, strlen(static::$modulo[$this->instanceID]), "\0", STR_PAD_LEFT);

        return new static($this->instanceID, $x ^ static::$modulo[$this->instanceID]);
    }

    /**
     * Returns the modulo
     *
     * @return string
     */
    public static function getModulo($instanceID)
    {
        return static::$modulo[$instanceID];
    }

    /**
     * Converts an Integer to a byte string (eg. base-256).
     *
     * @return string
     */
    public function toBytes()
    {
        return str_pad($this->value, strlen(static::$modulo[$this->instanceID]), "\0", STR_PAD_LEFT);
    }

    /**
     * Converts an Integer to a hex string (eg. base-16).
     *
     * @return string
     */
    public function toHex()
    {
        return Strings::bin2hex($this->toBytes());
    }

    /**
     * Converts an Integer to a bit string (eg. base-2).
     *
     * @return string
     */
    public function toBits()
    {
        //return str_pad(BinaryField::base256ToBase2($this->value), strlen(static::$modulo[$this->instanceID]), '0', STR_PAD_LEFT);
        return BinaryField::base256ToBase2($this->value);
    }

    /**
     * Converts an Integer to a BigInteger
     *
     * @return string
     */
    public function toBigInteger()
    {
        return new BigInteger($this->value, 256);
    }

    /**
     *  __toString() magic method
     *
     */
    public function __toString()
    {
        return (string) $this->toBigInteger();
    }

    /**
     *  __debugInfo() magic method
     *
     */
    public function __debugInfo()
    {
        return ['value' => $this->toHex()];
    }
}
