<?php

/**
 * BCMath Dynamic Barrett Modular Exponentiation Engine
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Math\BigInteger\Engines\BCMath\Reductions;

use phpseclib3\Math\BigInteger\Engines\BCMath;
use phpseclib3\Math\BigInteger\Engines\BCMath\Base;

/**
 * PHP Barrett Modular Exponentiation Engine
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class EvalBarrett extends Base
{
    /**
     * Custom Reduction Function
     *
     * @see self::generateCustomReduction
     */
    private static $custom_reduction;

    /**
     * Barrett Modular Reduction
     *
     * This calls a dynamically generated loop unrolled function that's specific to a given modulo.
     * Array lookups are avoided as are if statements testing for how many bits the host OS supports, etc.
     *
     * @param string $n
     * @param string $m
     * @return string
     */
    protected static function reduce($n, $m)
    {
        $inline = self::$custom_reduction;
        return $inline($n);
    }

    /**
     * Generate Custom Reduction
     *
     * @param BCMath $m
     * @param string $class
     * @return callable|void
     */
    protected static function generateCustomReduction(BCMath $m, $class)
    {
        $m_length = strlen($m);

        if ($m_length < 5) {
            $code = 'return bcmod($x, $n);';
            eval('$func = function ($n) { ' . $code . '};');
            self::$custom_reduction = $func;
            return;
        }

        $lhs = '1' . str_repeat('0', $m_length + ($m_length >> 1));
        $u = bcdiv($lhs, $m, 0);
        $m1 = bcsub($lhs, bcmul($u, $m));

        $cutoff = $m_length + ($m_length >> 1);

        $m = "'$m'";
        $u = "'$u'";
        $m1 = "'$m1'";

        $code = '
            $lsd = substr($n, -' . $cutoff . ');
            $msd = substr($n, 0, -' . $cutoff . ');

            $temp = bcmul($msd, ' . $m1 . ');
            $n = bcadd($lsd, $temp);

            $temp = substr($n, 0, ' . (-$m_length + 1) . ');
            $temp = bcmul($temp, ' . $u . ');
            $temp = substr($temp, 0, ' . (-($m_length >> 1) - 1) . ');
            $temp = bcmul($temp, ' . $m . ');

            $result = bcsub($n, $temp);

            if ($result[0] == \'-\') {
                $temp = \'1' . str_repeat('0', $m_length + 1) . '\';
                $result = bcadd($result, $temp);
            }

            while (bccomp($result, ' . $m . ') >= 0) {
                $result = bcsub($result, ' . $m . ');
            }

            return $result;';

        eval('$func = function ($n) { ' . $code . '};');

        self::$custom_reduction = $func;

        return $func;
    }
}
