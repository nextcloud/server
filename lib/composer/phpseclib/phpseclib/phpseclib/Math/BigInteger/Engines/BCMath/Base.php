<?php

/**
 * Modular Exponentiation Engine
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
 * Sliding Window Exponentiation Engine
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Base extends BCMath
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
     * Test for engine validity
     *
     * @return bool
     */
    public static function isValidEngine()
    {
        return static::class != __CLASS__;
    }

    /**
     * Performs modular exponentiation.
     *
     * @param BCMath $x
     * @param BCMath $e
     * @param BCMath $n
     * @param string $class
     * @return BCMath
     */
    protected static function powModHelper(BCMath $x, BCMath $e, BCMath $n, $class)
    {
        if (empty($e->value)) {
            $temp = new $class();
            $temp->value = '1';
            return $x->normalize($temp);
        }

        return $x->normalize(static::slidingWindow($x, $e, $n, $class));
    }

    /**
     * Modular reduction preparation
     *
     * @param string $x
     * @param string $n
     * @param string $class
     * @see self::slidingWindow()
     * @return string
     */
    protected static function prepareReduce($x, $n, $class)
    {
        return static::reduce($x, $n);
    }

    /**
     * Modular multiply
     *
     * @param string $x
     * @param string $y
     * @param string $n
     * @param string $class
     * @see self::slidingWindow()
     * @return string
     */
    protected static function multiplyReduce($x, $y, $n, $class)
    {
        return static::reduce(bcmul($x, $y), $n);
    }

    /**
     * Modular square
     *
     * @param string $x
     * @param string $n
     * @param string $class
     * @see self::slidingWindow()
     * @return string
     */
    protected static function squareReduce($x, $n, $class)
    {
        return static::reduce(bcmul($x, $x), $n);
    }
}
