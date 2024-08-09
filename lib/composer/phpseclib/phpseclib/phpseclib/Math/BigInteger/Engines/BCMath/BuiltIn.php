<?php

/**
 * Built-In BCMath Modular Exponentiation Engine
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Math\BigInteger\Engines\BCMath;

use phpseclib3\Math\BigInteger\Engines\BCMath;

/**
 * Built-In BCMath Modular Exponentiation Engine
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class BuiltIn extends BCMath
{
    /**
     * Performs modular exponentiation.
     *
     * @param BCMath $x
     * @param BCMath $e
     * @param BCMath $n
     * @return BCMath
     */
    protected static function powModHelper(BCMath $x, BCMath $e, BCMath $n)
    {
        $temp = new BCMath();
        $temp->value = bcpowmod($x->value, $e->value, $n->value);

        return $x->normalize($temp);
    }
}
