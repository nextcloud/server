<?php

/**
 * PHP Classic Modular Exponentiation Engine
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Math\BigInteger\Engines\PHP\Reductions;

use phpseclib3\Math\BigInteger\Engines\PHP\Base;

/**
 * PHP Classic Modular Exponentiation Engine
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Classic extends Base
{
    /**
     * Regular Division
     *
     * @param array $x
     * @param array $n
     * @param string $class
     * @return array
     */
    protected static function reduce(array $x, array $n, $class)
    {
        $lhs = new $class();
        $lhs->value = $x;
        $rhs = new $class();
        $rhs->value = $n;
        list(, $temp) = $lhs->divide($rhs);
        return $temp->value;
    }
}
