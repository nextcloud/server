<?php

/**
 * PHP Montgomery Modular Exponentiation Engine
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Math\BigInteger\Engines\PHP\Reductions;

use phpseclib3\Math\BigInteger\Engines\PHP\Montgomery as Progenitor;

/**
 * PHP Montgomery Modular Exponentiation Engine
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Montgomery extends Progenitor
{
    /**
     * Prepare a number for use in Montgomery Modular Reductions
     *
     * @param array $x
     * @param array $n
     * @param string $class
     * @return array
     */
    protected static function prepareReduce(array $x, array $n, $class)
    {
        $lhs = new $class();
        $lhs->value = array_merge(self::array_repeat(0, count($n)), $x);
        $rhs = new $class();
        $rhs->value = $n;

        list(, $temp) = $lhs->divide($rhs);
        return $temp->value;
    }

    /**
     * Montgomery Multiply
     *
     * Interleaves the montgomery reduction and long multiplication algorithms together as described in
     * {@link http://www.cacr.math.uwaterloo.ca/hac/about/chap14.pdf#page=13 HAC 14.36}
     *
     * @param array $x
     * @param array $n
     * @param string $class
     * @return array
     */
    protected static function reduce(array $x, array $n, $class)
    {
        static $cache = [
            self::VARIABLE => [],
            self::DATA => []
        ];

        if (($key = array_search($n, $cache[self::VARIABLE])) === false) {
            $key = count($cache[self::VARIABLE]);
            $cache[self::VARIABLE][] = $x;
            $cache[self::DATA][] = self::modInverse67108864($n, $class);
        }

        $k = count($n);

        $result = [self::VALUE => $x];

        for ($i = 0; $i < $k; ++$i) {
            $temp = $result[self::VALUE][$i] * $cache[self::DATA][$key];
            $temp = $temp - $class::BASE_FULL * ($class::BASE === 26 ? intval($temp / 0x4000000) : ($temp >> 31));
            $temp = $class::regularMultiply([$temp], $n);
            $temp = array_merge(self::array_repeat(0, $i), $temp);
            $result = $class::addHelper($result[self::VALUE], false, $temp, false);
        }

        $result[self::VALUE] = array_slice($result[self::VALUE], $k);

        if (self::compareHelper($result, false, $n, false) >= 0) {
            $result = $class::subtractHelper($result[self::VALUE], false, $n, false);
        }

        return $result[self::VALUE];
    }

    /**
     * Modular Inverse of a number mod 2**26 (eg. 67108864)
     *
     * Based off of the bnpInvDigit function implemented and justified in the following URL:
     *
     * {@link http://www-cs-students.stanford.edu/~tjw/jsbn/jsbn.js}
     *
     * The following URL provides more info:
     *
     * {@link http://groups.google.com/group/sci.crypt/msg/7a137205c1be7d85}
     *
     * As for why we do all the bitmasking...  strange things can happen when converting from floats to ints. For
     * instance, on some computers, var_dump((int) -4294967297) yields int(-1) and on others, it yields
     * int(-2147483648).  To avoid problems stemming from this, we use bitmasks to guarantee that ints aren't
     * auto-converted to floats.  The outermost bitmask is present because without it, there's no guarantee that
     * the "residue" returned would be the so-called "common residue".  We use fmod, in the last step, because the
     * maximum possible $x is 26 bits and the maximum $result is 16 bits.  Thus, we have to be able to handle up to
     * 40 bits, which only 64-bit floating points will support.
     *
     * Thanks to Pedro Gimeno Fortea for input!
     *
     * @param array $x
     * @param string $class
     * @return int
     */
    protected static function modInverse67108864(array $x, $class) // 2**26 == 67,108,864
    {
        $x = -$x[0];
        $result = $x & 0x3; // x**-1 mod 2**2
        $result = ($result * (2 - $x * $result)) & 0xF; // x**-1 mod 2**4
        $result = ($result * (2 - ($x & 0xFF) * $result))  & 0xFF; // x**-1 mod 2**8
        $result = ($result * ((2 - ($x & 0xFFFF) * $result) & 0xFFFF)) & 0xFFFF; // x**-1 mod 2**16
        $result = $class::BASE == 26 ?
            fmod($result * (2 - fmod($x * $result, $class::BASE_FULL)), $class::BASE_FULL) : // x**-1 mod 2**26
            ($result * (2 - ($x * $result) % $class::BASE_FULL)) % $class::BASE_FULL;
        return $result & $class::MAX_DIGIT;
    }
}
