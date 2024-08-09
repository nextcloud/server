<?php

/**
 * BCMath Barrett Modular Exponentiation Engine
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Math\BigInteger\Engines\BCMath\Reductions;

use phpseclib3\Math\BigInteger\Engines\BCMath\Base;

/**
 * PHP Barrett Modular Exponentiation Engine
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Barrett extends Base
{
    /**
     * Cache constants
     *
     * $cache[self::VARIABLE] tells us whether or not the cached data is still valid.
     *
     */
    const VARIABLE = 0;
    /**
     * $cache[self::DATA] contains the cached data.
     *
     */
    const DATA = 1;

    /**
     * Barrett Modular Reduction
     *
     * See {@link http://www.cacr.math.uwaterloo.ca/hac/about/chap14.pdf#page=14 HAC 14.3.3} /
     * {@link http://math.libtomcrypt.com/files/tommath.pdf#page=165 MPM 6.2.5} for more information.  Modified slightly,
     * so as not to require negative numbers (initially, this script didn't support negative numbers).
     *
     * Employs "folding", as described at
     * {@link http://www.cosic.esat.kuleuven.be/publications/thesis-149.pdf#page=66 thesis-149.pdf#page=66}.  To quote from
     * it, "the idea [behind folding] is to find a value x' such that x (mod m) = x' (mod m), with x' being smaller than x."
     *
     * Unfortunately, the "Barrett Reduction with Folding" algorithm described in thesis-149.pdf is not, as written, all that
     * usable on account of (1) its not using reasonable radix points as discussed in
     * {@link http://math.libtomcrypt.com/files/tommath.pdf#page=162 MPM 6.2.2} and (2) the fact that, even with reasonable
     * radix points, it only works when there are an even number of digits in the denominator.  The reason for (2) is that
     * (x >> 1) + (x >> 1) != x / 2 + x / 2.  If x is even, they're the same, but if x is odd, they're not.  See the in-line
     * comments for details.
     *
     * @param string $n
     * @param string $m
     * @return string
     */
    protected static function reduce($n, $m)
    {
        static $cache = [
            self::VARIABLE => [],
            self::DATA => []
        ];

        $m_length = strlen($m);

        if (strlen($n) >= 2 * $m_length) {
            return bcmod($n, $m);
        }

        // if (m.length >> 1) + 2 <= m.length then m is too small and n can't be reduced
        if ($m_length < 5) {
            return self::regularBarrett($n, $m);
        }
        // n = 2 * m.length

        if (($key = array_search($m, $cache[self::VARIABLE])) === false) {
            $key = count($cache[self::VARIABLE]);
            $cache[self::VARIABLE][] = $m;

            $lhs = '1' . str_repeat('0', $m_length + ($m_length >> 1));
            $u = bcdiv($lhs, $m, 0);
            $m1 = bcsub($lhs, bcmul($u, $m));

            $cache[self::DATA][] = [
                'u' => $u, // m.length >> 1 (technically (m.length >> 1) + 1)
                'm1' => $m1 // m.length
            ];
        } else {
            extract($cache[self::DATA][$key]);
        }

        $cutoff = $m_length + ($m_length >> 1);

        $lsd = substr($n, -$cutoff);
        $msd = substr($n, 0, -$cutoff);

        $temp = bcmul($msd, $m1); // m.length + (m.length >> 1)
        $n = bcadd($lsd, $temp); // m.length + (m.length >> 1) + 1 (so basically we're adding two same length numbers)
        //if ($m_length & 1) {
        //    return self::regularBarrett($n, $m);
        //}

        // (m.length + (m.length >> 1) + 1) - (m.length - 1) == (m.length >> 1) + 2
        $temp = substr($n, 0, -$m_length + 1);
        // if even: ((m.length >> 1) + 2) + (m.length >> 1) == m.length + 2
        // if odd:  ((m.length >> 1) + 2) + (m.length >> 1) == (m.length - 1) + 2 == m.length + 1
        $temp = bcmul($temp, $u);
        // if even: (m.length + 2) - ((m.length >> 1) + 1) = m.length - (m.length >> 1) + 1
        // if odd:  (m.length + 1) - ((m.length >> 1) + 1) = m.length - (m.length >> 1)
        $temp = substr($temp, 0, -($m_length >> 1) - 1);
        // if even: (m.length - (m.length >> 1) + 1) + m.length = 2 * m.length - (m.length >> 1) + 1
        // if odd:  (m.length - (m.length >> 1)) + m.length     = 2 * m.length - (m.length >> 1)
        $temp = bcmul($temp, $m);

        // at this point, if m had an odd number of digits, we'd be subtracting a 2 * m.length - (m.length >> 1) digit
        // number from a m.length + (m.length >> 1) + 1 digit number.  ie. there'd be an extra digit and the while loop
        // following this comment would loop a lot (hence our calling _regularBarrett() in that situation).

        $result = bcsub($n, $temp);

        //if (bccomp($result, '0') < 0) {
        if ($result[0] == '-') {
            $temp = '1' . str_repeat('0', $m_length + 1);
            $result = bcadd($result, $temp);
        }

        while (bccomp($result, $m) >= 0) {
            $result = bcsub($result, $m);
        }

        return $result;
    }

    /**
     * (Regular) Barrett Modular Reduction
     *
     * For numbers with more than four digits BigInteger::_barrett() is faster.  The difference between that and this
     * is that this function does not fold the denominator into a smaller form.
     *
     * @param string $x
     * @param string $n
     * @return string
     */
    private static function regularBarrett($x, $n)
    {
        static $cache = [
            self::VARIABLE => [],
            self::DATA => []
        ];

        $n_length = strlen($n);

        if (strlen($x) > 2 * $n_length) {
            return bcmod($x, $n);
        }

        if (($key = array_search($n, $cache[self::VARIABLE])) === false) {
            $key = count($cache[self::VARIABLE]);
            $cache[self::VARIABLE][] = $n;
            $lhs = '1' . str_repeat('0', 2 * $n_length);
            $cache[self::DATA][] = bcdiv($lhs, $n, 0);
        }

        $temp = substr($x, 0, -$n_length + 1);
        $temp = bcmul($temp, $cache[self::DATA][$key]);
        $temp = substr($temp, 0, -$n_length - 1);

        $r1 = substr($x, -$n_length - 1);
        $r2 = substr(bcmul($temp, $n), -$n_length - 1);
        $result = bcsub($r1, $r2);

        //if (bccomp($result, '0') < 0) {
        if ($result[0] == '-') {
            $q = '1' . str_repeat('0', $n_length + 1);
            $result = bcadd($result, $q);
        }

        while (bccomp($result, $n) >= 0) {
            $result = bcsub($result, $n);
        }

        return $result;
    }
}
