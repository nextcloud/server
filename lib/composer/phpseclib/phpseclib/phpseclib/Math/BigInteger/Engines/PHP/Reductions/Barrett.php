<?php

/**
 * PHP Barrett Modular Exponentiation Engine
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
use phpseclib3\Math\BigInteger\Engines\PHP\Base;

/**
 * PHP Barrett Modular Exponentiation Engine
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Barrett extends Base
{
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
     * @param array $n
     * @param array $m
     * @param class-string<PHP> $class
     * @return array
     */
    protected static function reduce(array $n, array $m, $class)
    {
        static $cache = [
            self::VARIABLE => [],
            self::DATA => []
        ];

        $m_length = count($m);

        // if (self::compareHelper($n, $static::square($m)) >= 0) {
        if (count($n) >= 2 * $m_length) {
            $lhs = new $class();
            $rhs = new $class();
            $lhs->value = $n;
            $rhs->value = $m;
            list(, $temp) = $lhs->divide($rhs);
            return $temp->value;
        }

        // if (m.length >> 1) + 2 <= m.length then m is too small and n can't be reduced
        if ($m_length < 5) {
            return self::regularBarrett($n, $m, $class);
        }
        // n = 2 * m.length

        if (($key = array_search($m, $cache[self::VARIABLE])) === false) {
            $key = count($cache[self::VARIABLE]);
            $cache[self::VARIABLE][] = $m;

            $lhs = new $class();
            $lhs_value = &$lhs->value;
            $lhs_value = self::array_repeat(0, $m_length + ($m_length >> 1));
            $lhs_value[] = 1;
            $rhs = new $class();
            $rhs->value = $m;

            list($u, $m1) = $lhs->divide($rhs);
            $u = $u->value;
            $m1 = $m1->value;

            $cache[self::DATA][] = [
                'u' => $u, // m.length >> 1 (technically (m.length >> 1) + 1)
                'm1' => $m1 // m.length
            ];
        } else {
            extract($cache[self::DATA][$key]);
        }

        $cutoff = $m_length + ($m_length >> 1);
        $lsd = array_slice($n, 0, $cutoff); // m.length + (m.length >> 1)
        $msd = array_slice($n, $cutoff);    // m.length >> 1

        $lsd = self::trim($lsd);
        $temp = $class::multiplyHelper($msd, false, $m1, false); // m.length + (m.length >> 1)
        $n = $class::addHelper($lsd, false, $temp[self::VALUE], false); // m.length + (m.length >> 1) + 1 (so basically we're adding two same length numbers)
        //if ($m_length & 1) {
        //    return self::regularBarrett($n[self::VALUE], $m, $class);
        //}

        // (m.length + (m.length >> 1) + 1) - (m.length - 1) == (m.length >> 1) + 2
        $temp = array_slice($n[self::VALUE], $m_length - 1);
        // if even: ((m.length >> 1) + 2) + (m.length >> 1) == m.length + 2
        // if odd:  ((m.length >> 1) + 2) + (m.length >> 1) == (m.length - 1) + 2 == m.length + 1
        $temp = $class::multiplyHelper($temp, false, $u, false);
        // if even: (m.length + 2) - ((m.length >> 1) + 1) = m.length - (m.length >> 1) + 1
        // if odd:  (m.length + 1) - ((m.length >> 1) + 1) = m.length - (m.length >> 1)
        $temp = array_slice($temp[self::VALUE], ($m_length >> 1) + 1);
        // if even: (m.length - (m.length >> 1) + 1) + m.length = 2 * m.length - (m.length >> 1) + 1
        // if odd:  (m.length - (m.length >> 1)) + m.length     = 2 * m.length - (m.length >> 1)
        $temp = $class::multiplyHelper($temp, false, $m, false);

        // at this point, if m had an odd number of digits, we'd be subtracting a 2 * m.length - (m.length >> 1) digit
        // number from a m.length + (m.length >> 1) + 1 digit number.  ie. there'd be an extra digit and the while loop
        // following this comment would loop a lot (hence our calling _regularBarrett() in that situation).

        $result = $class::subtractHelper($n[self::VALUE], false, $temp[self::VALUE], false);

        while (self::compareHelper($result[self::VALUE], $result[self::SIGN], $m, false) >= 0) {
            $result = $class::subtractHelper($result[self::VALUE], $result[self::SIGN], $m, false);
        }

        return $result[self::VALUE];
    }

    /**
     * (Regular) Barrett Modular Reduction
     *
     * For numbers with more than four digits BigInteger::_barrett() is faster.  The difference between that and this
     * is that this function does not fold the denominator into a smaller form.
     *
     * @param array $x
     * @param array $n
     * @param string $class
     * @return array
     */
    private static function regularBarrett(array $x, array $n, $class)
    {
        static $cache = [
            self::VARIABLE => [],
            self::DATA => []
        ];

        $n_length = count($n);

        if (count($x) > 2 * $n_length) {
            $lhs = new $class();
            $rhs = new $class();
            $lhs->value = $x;
            $rhs->value = $n;
            list(, $temp) = $lhs->divide($rhs);
            return $temp->value;
        }

        if (($key = array_search($n, $cache[self::VARIABLE])) === false) {
            $key = count($cache[self::VARIABLE]);
            $cache[self::VARIABLE][] = $n;
            $lhs = new $class();
            $lhs_value = &$lhs->value;
            $lhs_value = self::array_repeat(0, 2 * $n_length);
            $lhs_value[] = 1;
            $rhs = new $class();
            $rhs->value = $n;
            list($temp, ) = $lhs->divide($rhs); // m.length
            $cache[self::DATA][] = $temp->value;
        }

        // 2 * m.length - (m.length - 1) = m.length + 1
        $temp = array_slice($x, $n_length - 1);
        // (m.length + 1) + m.length = 2 * m.length + 1
        $temp = $class::multiplyHelper($temp, false, $cache[self::DATA][$key], false);
        // (2 * m.length + 1) - (m.length - 1) = m.length + 2
        $temp = array_slice($temp[self::VALUE], $n_length + 1);

        // m.length + 1
        $result = array_slice($x, 0, $n_length + 1);
        // m.length + 1
        $temp = self::multiplyLower($temp, false, $n, false, $n_length + 1, $class);
        // $temp == array_slice($class::regularMultiply($temp, false, $n, false)->value, 0, $n_length + 1)

        if (self::compareHelper($result, false, $temp[self::VALUE], $temp[self::SIGN]) < 0) {
            $corrector_value = self::array_repeat(0, $n_length + 1);
            $corrector_value[count($corrector_value)] = 1;
            $result = $class::addHelper($result, false, $corrector_value, false);
            $result = $result[self::VALUE];
        }

        // at this point, we're subtracting a number with m.length + 1 digits from another number with m.length + 1 digits
        $result = $class::subtractHelper($result, false, $temp[self::VALUE], $temp[self::SIGN]);
        while (self::compareHelper($result[self::VALUE], $result[self::SIGN], $n, false) > 0) {
            $result = $class::subtractHelper($result[self::VALUE], $result[self::SIGN], $n, false);
        }

        return $result[self::VALUE];
    }

    /**
     * Performs long multiplication up to $stop digits
     *
     * If you're going to be doing array_slice($product->value, 0, $stop), some cycles can be saved.
     *
     * @see self::regularBarrett()
     * @param array $x_value
     * @param bool $x_negative
     * @param array $y_value
     * @param bool $y_negative
     * @param int $stop
     * @param string $class
     * @return array
     */
    private static function multiplyLower(array $x_value, $x_negative, array $y_value, $y_negative, $stop, $class)
    {
        $x_length = count($x_value);
        $y_length = count($y_value);

        if (!$x_length || !$y_length) { // a 0 is being multiplied
            return [
                self::VALUE => [],
                self::SIGN => false
            ];
        }

        if ($x_length < $y_length) {
            $temp = $x_value;
            $x_value = $y_value;
            $y_value = $temp;

            $x_length = count($x_value);
            $y_length = count($y_value);
        }

        $product_value = self::array_repeat(0, $x_length + $y_length);

        // the following for loop could be removed if the for loop following it
        // (the one with nested for loops) initially set $i to 0, but
        // doing so would also make the result in one set of unnecessary adds,
        // since on the outermost loops first pass, $product->value[$k] is going
        // to always be 0

        $carry = 0;

        for ($j = 0; $j < $x_length; ++$j) { // ie. $i = 0, $k = $i
            $temp = $x_value[$j] * $y_value[0] + $carry; // $product_value[$k] == 0
            $carry = $class::BASE === 26 ? intval($temp / 0x4000000) : ($temp >> 31);
            $product_value[$j] = (int) ($temp - $class::BASE_FULL * $carry);
        }

        if ($j < $stop) {
            $product_value[$j] = $carry;
        }

        // the above for loop is what the previous comment was talking about.  the
        // following for loop is the "one with nested for loops"

        for ($i = 1; $i < $y_length; ++$i) {
            $carry = 0;

            for ($j = 0, $k = $i; $j < $x_length && $k < $stop; ++$j, ++$k) {
                $temp = $product_value[$k] + $x_value[$j] * $y_value[$i] + $carry;
                $carry = $class::BASE === 26 ? intval($temp / 0x4000000) : ($temp >> 31);
                $product_value[$k] = (int) ($temp - $class::BASE_FULL * $carry);
            }

            if ($k < $stop) {
                $product_value[$k] = $carry;
            }
        }

        return [
            self::VALUE => self::trim($product_value),
            self::SIGN => $x_negative != $y_negative
        ];
    }
}
