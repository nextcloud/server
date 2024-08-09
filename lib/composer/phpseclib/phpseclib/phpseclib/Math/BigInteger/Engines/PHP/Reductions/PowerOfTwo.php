<?php

/**
 * PHP Power of Two Modular Exponentiation Engine
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
 * PHP Power Of Two Modular Exponentiation Engine
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PowerOfTwo extends Base
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
        return self::reduce($x, $n, $class);
    }

    /**
     * Power Of Two Reduction
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

        $temp = new $class();
        $temp->value = [1];

        $result = $lhs->bitwise_and($rhs->subtract($temp));
        return $result->value;
    }
}
