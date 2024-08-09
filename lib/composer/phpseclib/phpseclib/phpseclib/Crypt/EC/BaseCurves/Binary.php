<?php

/**
 * Curves over y^2 + x*y = x^3 + a*x^2 + b
 *
 * These are curves used in SEC 2 over prime fields: http://www.secg.org/SEC2-Ver-1.0.pdf
 * The curve is a weierstrass curve with a[3] and a[2] set to 0.
 *
 * Uses Jacobian Coordinates for speed if able:
 *
 * https://en.wikipedia.org/wiki/Jacobian_curve
 * https://en.wikibooks.org/wiki/Cryptography/Prime_Curve/Jacobian_Coordinates
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
use phpseclib3\Math\BinaryField;
use phpseclib3\Math\BinaryField\Integer as BinaryInteger;

/**
 * Curves over y^2 + x*y = x^3 + a*x^2 + b
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class Binary extends Base
{
    /**
     * Binary Field Integer factory
     *
     * @var \phpseclib3\Math\BinaryField
     */
    protected $factory;

    /**
     * Cofficient for x^1
     *
     * @var object
     */
    protected $a;

    /**
     * Cofficient for x^0
     *
     * @var object
     */
    protected $b;

    /**
     * Base Point
     *
     * @var object
     */
    protected $p;

    /**
     * The number one over the specified finite field
     *
     * @var object
     */
    protected $one;

    /**
     * The modulo
     *
     * @var BigInteger
     */
    protected $modulo;

    /**
     * The Order
     *
     * @var BigInteger
     */
    protected $order;

    /**
     * Sets the modulo
     */
    public function setModulo(...$modulo)
    {
        $this->modulo = $modulo;
        $this->factory = new BinaryField(...$modulo);

        $this->one = $this->factory->newInteger("\1");
    }

    /**
     * Set coefficients a and b
     *
     * @param string $a
     * @param string $b
     */
    public function setCoefficients($a, $b)
    {
        if (!isset($this->factory)) {
            throw new \RuntimeException('setModulo needs to be called before this method');
        }
        $this->a = $this->factory->newInteger(pack('H*', $a));
        $this->b = $this->factory->newInteger(pack('H*', $b));
    }

    /**
     * Set x and y coordinates for the base point
     *
     * @param string|BinaryInteger $x
     * @param string|BinaryInteger $y
     */
    public function setBasePoint($x, $y)
    {
        switch (true) {
            case !is_string($x) && !$x instanceof BinaryInteger:
                throw new \UnexpectedValueException('Argument 1 passed to Binary::setBasePoint() must be a string or an instance of BinaryField\Integer');
            case !is_string($y) && !$y instanceof BinaryInteger:
                throw new \UnexpectedValueException('Argument 2 passed to Binary::setBasePoint() must be a string or an instance of BinaryField\Integer');
        }
        if (!isset($this->factory)) {
            throw new \RuntimeException('setModulo needs to be called before this method');
        }
        $this->p = [
            is_string($x) ? $this->factory->newInteger(pack('H*', $x)) : $x,
            is_string($y) ? $this->factory->newInteger(pack('H*', $y)) : $y
        ];
    }

    /**
     * Retrieve the base point as an array
     *
     * @return array
     */
    public function getBasePoint()
    {
        if (!isset($this->factory)) {
            throw new \RuntimeException('setModulo needs to be called before this method');
        }
        /*
        if (!isset($this->p)) {
            throw new \RuntimeException('setBasePoint needs to be called before this method');
        }
        */
        return $this->p;
    }

    /**
     * Adds two points on the curve
     *
     * @return FiniteField[]
     */
    public function addPoint(array $p, array $q)
    {
        if (!isset($this->factory)) {
            throw new \RuntimeException('setModulo needs to be called before this method');
        }

        if (!count($p) || !count($q)) {
            if (count($q)) {
                return $q;
            }
            if (count($p)) {
                return $p;
            }
            return [];
        }

        if (!isset($p[2]) || !isset($q[2])) {
            throw new \RuntimeException('Affine coordinates need to be manually converted to "Jacobi" coordinates or vice versa');
        }

        if ($p[0]->equals($q[0])) {
            return !$p[1]->equals($q[1]) ? [] : $this->doublePoint($p);
        }

        // formulas from http://hyperelliptic.org/EFD/g12o/auto-shortw-jacobian.html

        list($x1, $y1, $z1) = $p;
        list($x2, $y2, $z2) = $q;

        $o1 = $z1->multiply($z1);
        $b = $x2->multiply($o1);

        if ($z2->equals($this->one)) {
            $d = $y2->multiply($o1)->multiply($z1);
            $e = $x1->add($b);
            $f = $y1->add($d);
            $z3 = $e->multiply($z1);
            $h = $f->multiply($x2)->add($z3->multiply($y2));
            $i = $f->add($z3);
            $g = $z3->multiply($z3);
            $p1 = $this->a->multiply($g);
            $p2 = $f->multiply($i);
            $p3 = $e->multiply($e)->multiply($e);
            $x3 = $p1->add($p2)->add($p3);
            $y3 = $i->multiply($x3)->add($g->multiply($h));

            return [$x3, $y3, $z3];
        }

        $o2 = $z2->multiply($z2);
        $a = $x1->multiply($o2);
        $c = $y1->multiply($o2)->multiply($z2);
        $d = $y2->multiply($o1)->multiply($z1);
        $e = $a->add($b);
        $f = $c->add($d);
        $g = $e->multiply($z1);
        $h = $f->multiply($x2)->add($g->multiply($y2));
        $z3 = $g->multiply($z2);
        $i = $f->add($z3);
        $p1 = $this->a->multiply($z3->multiply($z3));
        $p2 = $f->multiply($i);
        $p3 = $e->multiply($e)->multiply($e);
        $x3 = $p1->add($p2)->add($p3);
        $y3 = $i->multiply($x3)->add($g->multiply($g)->multiply($h));

        return [$x3, $y3, $z3];
    }

    /**
     * Doubles a point on a curve
     *
     * @return FiniteField[]
     */
    public function doublePoint(array $p)
    {
        if (!isset($this->factory)) {
            throw new \RuntimeException('setModulo needs to be called before this method');
        }

        if (!count($p)) {
            return [];
        }

        if (!isset($p[2])) {
            throw new \RuntimeException('Affine coordinates need to be manually converted to "Jacobi" coordinates or vice versa');
        }

        // formulas from http://hyperelliptic.org/EFD/g12o/auto-shortw-jacobian.html

        list($x1, $y1, $z1) = $p;

        $a = $x1->multiply($x1);
        $b = $a->multiply($a);

        if ($z1->equals($this->one)) {
            $x3 = $b->add($this->b);
            $z3 = clone $x1;
            $p1 = $a->add($y1)->add($z3)->multiply($this->b);
            $p2 = $a->add($y1)->multiply($b);
            $y3 = $p1->add($p2);

            return [$x3, $y3, $z3];
        }

        $c = $z1->multiply($z1);
        $d = $c->multiply($c);
        $x3 = $b->add($this->b->multiply($d->multiply($d)));
        $z3 = $x1->multiply($c);
        $p1 = $b->multiply($z3);
        $p2 = $a->add($y1->multiply($z1))->add($z3)->multiply($x3);
        $y3 = $p1->add($p2);

        return [$x3, $y3, $z3];
    }

    /**
     * Returns the X coordinate and the derived Y coordinate
     *
     * Not supported because it is covered by patents.
     * Quoting https://www.openssl.org/docs/man1.1.0/apps/ecparam.html ,
     *
     * "Due to patent issues the compressed option is disabled by default for binary curves
     *  and can be enabled by defining the preprocessor macro OPENSSL_EC_BIN_PT_COMP at
     *  compile time."
     *
     * @return array
     */
    public function derivePoint($m)
    {
        throw new \RuntimeException('Point compression on binary finite field elliptic curves is not supported');
    }

    /**
     * Tests whether or not the x / y values satisfy the equation
     *
     * @return boolean
     */
    public function verifyPoint(array $p)
    {
        list($x, $y) = $p;
        $lhs = $y->multiply($y);
        $lhs = $lhs->add($x->multiply($y));
        $x2 = $x->multiply($x);
        $x3 = $x2->multiply($x);
        $rhs = $x3->add($this->a->multiply($x2))->add($this->b);

        return $lhs->equals($rhs);
    }

    /**
     * Returns the modulo
     *
     * @return \phpseclib3\Math\BigInteger
     */
    public function getModulo()
    {
        return $this->modulo;
    }

    /**
     * Returns the a coefficient
     *
     * @return \phpseclib3\Math\PrimeField\Integer
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * Returns the a coefficient
     *
     * @return \phpseclib3\Math\PrimeField\Integer
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * Returns the affine point
     *
     * A Jacobian Coordinate is of the form (x, y, z).
     * To convert a Jacobian Coordinate to an Affine Point
     * you do (x / z^2, y / z^3)
     *
     * @return \phpseclib3\Math\PrimeField\Integer[]
     */
    public function convertToAffine(array $p)
    {
        if (!isset($p[2])) {
            return $p;
        }
        list($x, $y, $z) = $p;
        $z = $this->one->divide($z);
        $z2 = $z->multiply($z);
        return [
            $x->multiply($z2),
            $y->multiply($z2)->multiply($z)
        ];
    }

    /**
     * Converts an affine point to a jacobian coordinate
     *
     * @return \phpseclib3\Math\PrimeField\Integer[]
     */
    public function convertToInternal(array $p)
    {
        if (isset($p[2])) {
            return $p;
        }

        $p[2] = clone $this->one;
        $p['fresh'] = true;
        return $p;
    }
}
