<?php

/**
 * Curve methods common to all curves
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Crypt\EC\BaseCurves;

use phpseclib3\Math\BigInteger;

/**
 * Base
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Base
{
    /**
     * The Order
     *
     * @var BigInteger
     */
    protected $order;

    /**
     * Finite Field Integer factory
     *
     * @var \phpseclib3\Math\FiniteField\Integer
     */
    protected $factory;

    /**
     * Returns a random integer
     *
     * @return object
     */
    public function randomInteger()
    {
        return $this->factory->randomInteger();
    }

    /**
     * Converts a BigInteger to a \phpseclib3\Math\FiniteField\Integer integer
     *
     * @return object
     */
    public function convertInteger(BigInteger $x)
    {
        return $this->factory->newInteger($x);
    }

    /**
     * Returns the length, in bytes, of the modulo
     *
     * @return integer
     */
    public function getLengthInBytes()
    {
        return $this->factory->getLengthInBytes();
    }

    /**
     * Returns the length, in bits, of the modulo
     *
     * @return integer
     */
    public function getLength()
    {
        return $this->factory->getLength();
    }

    /**
     * Multiply a point on the curve by a scalar
     *
     * Uses the montgomery ladder technique as described here:
     *
     * https://en.wikipedia.org/wiki/Elliptic_curve_point_multiplication#Montgomery_ladder
     * https://github.com/phpecc/phpecc/issues/16#issuecomment-59176772
     *
     * @return array
     */
    public function multiplyPoint(array $p, BigInteger $d)
    {
        $alreadyInternal = isset($p[2]);
        $r = $alreadyInternal ?
            [[], $p] :
            [[], $this->convertToInternal($p)];

        $d = $d->toBits();
        for ($i = 0; $i < strlen($d); $i++) {
            $d_i = (int) $d[$i];
            $r[1 - $d_i] = $this->addPoint($r[0], $r[1]);
            $r[$d_i] = $this->doublePoint($r[$d_i]);
        }

        return $alreadyInternal ? $r[0] : $this->convertToAffine($r[0]);
    }

    /**
     * Creates a random scalar multiplier
     *
     * @return BigInteger
     */
    public function createRandomMultiplier()
    {
        static $one;
        if (!isset($one)) {
            $one = new BigInteger(1);
        }

        return BigInteger::randomRange($one, $this->order->subtract($one));
    }

    /**
     * Performs range check
     */
    public function rangeCheck(BigInteger $x)
    {
        static $zero;
        if (!isset($zero)) {
            $zero = new BigInteger();
        }

        if (!isset($this->order)) {
            throw new \RuntimeException('setOrder needs to be called before this method');
        }
        if ($x->compare($this->order) > 0 || $x->compare($zero) <= 0) {
            throw new \RangeException('x must be between 1 and the order of the curve');
        }
    }

    /**
     * Sets the Order
     */
    public function setOrder(BigInteger $order)
    {
        $this->order = $order;
    }

    /**
     * Returns the Order
     *
     * @return \phpseclib3\Math\BigInteger
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Use a custom defined modular reduction function
     *
     * @return object
     */
    public function setReduction(callable $func)
    {
        $this->factory->setReduction($func);
    }

    /**
     * Returns the affine point
     *
     * @return object[]
     */
    public function convertToAffine(array $p)
    {
        return $p;
    }

    /**
     * Converts an affine point to a jacobian coordinate
     *
     * @return object[]
     */
    public function convertToInternal(array $p)
    {
        return $p;
    }

    /**
     * Negates a point
     *
     * @return object[]
     */
    public function negatePoint(array $p)
    {
        $temp = [
            $p[0],
            $p[1]->negate()
        ];
        if (isset($p[2])) {
            $temp[] = $p[2];
        }
        return $temp;
    }

    /**
     * Multiply and Add Points
     *
     * @return int[]
     */
    public function multiplyAddPoints(array $points, array $scalars)
    {
        $p1 = $this->convertToInternal($points[0]);
        $p2 = $this->convertToInternal($points[1]);
        $p1 = $this->multiplyPoint($p1, $scalars[0]);
        $p2 = $this->multiplyPoint($p2, $scalars[1]);
        $r = $this->addPoint($p1, $p2);
        return $this->convertToAffine($r);
    }
}
