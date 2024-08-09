<?php

/**
 * PHP Montgomery Modular Exponentiation Engine with interleaved multiplication
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Math\BigInteger\Engines\PHP\Reductions;

use phpseclib3\Math\BigInteger\Engines\PHP;

/**
 * PHP Montgomery Modular Exponentiation Engine with interleaved multiplication
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class MontgomeryMult extends Montgomery
{
    /**
     * Montgomery Multiply
     *
     * Interleaves the montgomery reduction and long multiplication algorithms together as described in
     * {@link http://www.cacr.math.uwaterloo.ca/hac/about/chap14.pdf#page=13 HAC 14.36}
     *
     * @see self::_prepMontgomery()
     * @see self::_montgomery()
     * @param array $x
     * @param array $y
     * @param array $m
     * @param class-string<PHP> $class
     * @return array
     */
    public static function multiplyReduce(array $x, array $y, array $m, $class)
    {
        // the following code, although not callable, can be run independently of the above code
        // although the above code performed better in my benchmarks the following could might
        // perform better under different circumstances. in lieu of deleting it it's just been
        // made uncallable

        static $cache = [
            self::VARIABLE => [],
            self::DATA => []
        ];

        if (($key = array_search($m, $cache[self::VARIABLE])) === false) {
            $key = count($cache[self::VARIABLE]);
            $cache[self::VARIABLE][] = $m;
            $cache[self::DATA][] = self::modInverse67108864($m, $class);
        }

        $n = max(count($x), count($y), count($m));
        $x = array_pad($x, $n, 0);
        $y = array_pad($y, $n, 0);
        $m = array_pad($m, $n, 0);
        $a = [self::VALUE => self::array_repeat(0, $n + 1)];
        for ($i = 0; $i < $n; ++$i) {
            $temp = $a[self::VALUE][0] + $x[$i] * $y[0];
            $temp = $temp - $class::BASE_FULL * ($class::BASE === 26 ? intval($temp / 0x4000000) : ($temp >> 31));
            $temp = $temp * $cache[self::DATA][$key];
            $temp = $temp - $class::BASE_FULL * ($class::BASE === 26 ? intval($temp / 0x4000000) : ($temp >> 31));
            $temp = $class::addHelper($class::regularMultiply([$x[$i]], $y), false, $class::regularMultiply([$temp], $m), false);
            $a = $class::addHelper($a[self::VALUE], false, $temp[self::VALUE], false);
            $a[self::VALUE] = array_slice($a[self::VALUE], 1);
        }
        if (self::compareHelper($a[self::VALUE], false, $m, false) >= 0) {
            $a = $class::subtractHelper($a[self::VALUE], false, $m, false);
        }
        return $a[self::VALUE];
    }
}
