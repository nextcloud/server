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

namespace phpseclib3\Math\BigInteger\Engines\PHP;

use phpseclib3\Math\BigInteger\Engines\Engine;
use phpseclib3\Math\BigInteger\Engines\PHP;
use phpseclib3\Math\BigInteger\Engines\PHP\Reductions\PowerOfTwo;

/**
 * PHP Montgomery Modular Exponentiation Engine
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Montgomery extends Base
{
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
     * @template T of Engine
     * @param Engine $x
     * @param Engine $e
     * @param Engine $n
     * @param class-string<T> $class
     * @return T
     */
    protected static function slidingWindow(Engine $x, Engine $e, Engine $n, $class)
    {
        // is the modulo odd?
        if ($n->value[0] & 1) {
            return parent::slidingWindow($x, $e, $n, $class);
        }
        // if it's not, it's even

        // find the lowest set bit (eg. the max pow of 2 that divides $n)
        for ($i = 0; $i < count($n->value); ++$i) {
            if ($n->value[$i]) {
                $temp = decbin($n->value[$i]);
                $j = strlen($temp) - strrpos($temp, '1') - 1;
                $j += $class::BASE * $i;
                break;
            }
        }
        // at this point, 2^$j * $n/(2^$j) == $n

        $mod1 = clone $n;
        $mod1->rshift($j);
        $mod2 = new $class();
        $mod2->value = [1];
        $mod2->lshift($j);

        $part1 = $mod1->value != [1] ? parent::slidingWindow($x, $e, $mod1, $class) : new $class();
        $part2 = PowerOfTwo::slidingWindow($x, $e, $mod2, $class);

        $y1 = $mod2->modInverse($mod1);
        $y2 = $mod1->modInverse($mod2);

        $result = $part1->multiply($mod2);
        $result = $result->multiply($y1);

        $temp = $part2->multiply($mod1);
        $temp = $temp->multiply($y2);

        $result = $result->add($temp);
        list(, $result) = $result->divide($n);

        return $result;
    }
}
