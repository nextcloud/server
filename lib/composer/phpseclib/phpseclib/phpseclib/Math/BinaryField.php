<?php

/**
 * Binary Finite Fields
 *
 * Utilizes the factory design pattern
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace phpseclib3\Math;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Math\BinaryField\Integer;
use phpseclib3\Math\Common\FiniteField;

/**
 * Binary Finite Fields
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class BinaryField extends FiniteField
{
    /**
     * Instance Counter
     *
     * @var int
     */
    private static $instanceCounter = 0;

    /**
     * Keeps track of current instance
     *
     * @var int
     */
    protected $instanceID;

    /** @var BigInteger */
    private $randomMax;

    /**
     * Default constructor
     */
    public function __construct(...$indices)
    {
        $m = array_shift($indices);
        if ($m > 571) {
            /* sect571r1 and sect571k1 are the largest binary curves that https://www.secg.org/sec2-v2.pdf defines
               altho theoretically there may be legit reasons to use binary finite fields with larger degrees
               imposing a limit on the maximum size is both reasonable and precedented. in particular,
               http://tools.ietf.org/html/rfc4253#section-6.1 (The Secure Shell (SSH) Transport Layer Protocol) says
               "implementations SHOULD check that the packet length is reasonable in order for the implementation to
                avoid denial of service and/or buffer overflow attacks" */
            throw new \OutOfBoundsException('Degrees larger than 571 are not supported');
        }
        $val = str_repeat('0', $m) . '1';
        foreach ($indices as $index) {
            $val[$index] = '1';
        }
        $modulo = static::base2ToBase256(strrev($val));

        $mStart = 2 * $m - 2;
        $t = ceil($m / 8);
        $finalMask = chr((1 << ($m % 8)) - 1);
        if ($finalMask == "\0") {
            $finalMask = "\xFF";
        }
        $bitLen = $mStart + 1;
        $pad = ceil($bitLen / 8);
        $h = $bitLen & 7;
        $h = $h ? 8 - $h : 0;

        $r = rtrim(substr($val, 0, -1), '0');
        $u = [static::base2ToBase256(strrev($r))];
        for ($i = 1; $i < 8; $i++) {
            $u[] = static::base2ToBase256(strrev(str_repeat('0', $i) . $r));
        }

        // implements algorithm 2.40 (in section 2.3.5) in "Guide to Elliptic Curve Cryptography"
        // with W = 8
        $reduce = function ($c) use ($u, $mStart, $m, $t, $finalMask, $pad, $h) {
            $c = str_pad($c, $pad, "\0", STR_PAD_LEFT);
            for ($i = $mStart; $i >= $m;) {
                $g = $h >> 3;
                $mask = $h & 7;
                $mask = $mask ? 1 << (7 - $mask) : 0x80;
                for (; $mask > 0; $mask >>= 1, $i--, $h++) {
                    if (ord($c[$g]) & $mask) {
                        $temp = $i - $m;
                        $j = $temp >> 3;
                        $k = $temp & 7;
                        $t1 = $j ? substr($c, 0, -$j) : $c;
                        $length = strlen($t1);
                        if ($length) {
                            $t2 = str_pad($u[$k], $length, "\0", STR_PAD_LEFT);
                            $temp = $t1 ^ $t2;
                            $c = $j ? substr_replace($c, $temp, 0, $length) : $temp;
                        }
                    }
                }
            }
            $c = substr($c, -$t);
            if (strlen($c) == $t) {
                $c[0] = $c[0] & $finalMask;
            }
            return ltrim($c, "\0");
        };

        $this->instanceID = self::$instanceCounter++;
        Integer::setModulo($this->instanceID, $modulo);
        Integer::setRecurringModuloFunction($this->instanceID, $reduce);

        $this->randomMax = new BigInteger($modulo, 2);
    }

    /**
     * Returns an instance of a dynamically generated PrimeFieldInteger class
     *
     * @param string $num
     * @return Integer
     */
    public function newInteger($num)
    {
        return new Integer($this->instanceID, $num instanceof BigInteger ? $num->toBytes() : $num);
    }

    /**
     * Returns an integer on the finite field between one and the prime modulo
     *
     * @return Integer
     */
    public function randomInteger()
    {
        static $one;
        if (!isset($one)) {
            $one = new BigInteger(1);
        }

        return new Integer($this->instanceID, BigInteger::randomRange($one, $this->randomMax)->toBytes());
    }

    /**
     * Returns the length of the modulo in bytes
     *
     * @return int
     */
    public function getLengthInBytes()
    {
        return strlen(Integer::getModulo($this->instanceID));
    }

    /**
     * Returns the length of the modulo in bits
     *
     * @return int
     */
    public function getLength()
    {
        return strlen(Integer::getModulo($this->instanceID)) << 3;
    }

    /**
     * Converts a base-2 string to a base-256 string
     *
     * @param string $x
     * @param int|null $size
     * @return string
     */
    public static function base2ToBase256($x, $size = null)
    {
        $str = Strings::bits2bin($x);

        $pad = strlen($x) >> 3;
        if (strlen($x) & 3) {
            $pad++;
        }
        $str = str_pad($str, $pad, "\0", STR_PAD_LEFT);
        if (isset($size)) {
            $str = str_pad($str, $size, "\0", STR_PAD_LEFT);
        }

        return $str;
    }

    /**
     * Converts a base-256 string to a base-2 string
     *
     * @param string $x
     * @return string
     */
    public static function base256ToBase2($x)
    {
        if (function_exists('gmp_import')) {
            return gmp_strval(gmp_import($x), 2);
        }

        return Strings::bin2bits($x);
    }
}
