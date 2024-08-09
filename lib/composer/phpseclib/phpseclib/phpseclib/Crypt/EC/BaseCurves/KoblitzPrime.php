<?php

/**
 * Generalized Koblitz Curves over y^2 = x^3 + b.
 *
 * According to http://www.secg.org/SEC2-Ver-1.0.pdf Koblitz curves are over the GF(2**m)
 * finite field. Both the $a$ and $b$ coefficients are either 0 or 1. However, SEC2
 * generalizes the definition to include curves over GF(P) "which possess an efficiently
 * computable endomorphism".
 *
 * For these generalized Koblitz curves $b$ doesn't have to be 0 or 1. Whether or not $a$
 * has any restrictions on it is unclear, however, for all the GF(P) Koblitz curves defined
 * in SEC2 v1.0 $a$ is $0$ so all of the methods defined herein will assume that it is.
 *
 * I suppose we could rename the $b$ coefficient to $a$, however, the documentation refers
 * to $b$ so we'll just keep it.
 *
 * If a later version of SEC2 comes out wherein some $a$ values are non-zero we can create a
 * new method for those. eg. KoblitzA1Prime.php or something.
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
use phpseclib3\Math\PrimeField;

/**
 * Curves over y^2 = x^3 + b
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class KoblitzPrime extends Prime
{
    /**
     * Basis
     *
     * @var list<array{a: BigInteger, b: BigInteger}>
     */
    protected $basis;

    /**
     * Beta
     *
     * @var PrimeField\Integer
     */
    protected $beta;

    // don't overwrite setCoefficients() with one that only accepts one parameter so that
    // one might be able to switch between KoblitzPrime and Prime more easily (for benchmarking
    // purposes).

    /**
     * Multiply and Add Points
     *
     * Uses a efficiently computable endomorphism to achieve a slight speedup
     *
     * Adapted from:
     * https://github.com/indutny/elliptic/blob/725bd91/lib/elliptic/curve/short.js#L219
     *
     * @return int[]
     */
    public function multiplyAddPoints(array $points, array $scalars)
    {
        static $zero, $one, $two;
        if (!isset($two)) {
            $two = new BigInteger(2);
            $one = new BigInteger(1);
        }

        if (!isset($this->beta)) {
            // get roots
            $inv = $this->one->divide($this->two)->negate();
            $s = $this->three->negate()->squareRoot()->multiply($inv);
            $betas = [
                $inv->add($s),
                $inv->subtract($s)
            ];
            $this->beta = $betas[0]->compare($betas[1]) < 0 ? $betas[0] : $betas[1];
            //echo strtoupper($this->beta->toHex(true)) . "\n"; exit;
        }

        if (!isset($this->basis)) {
            $factory = new PrimeField($this->order);
            $tempOne = $factory->newInteger($one);
            $tempTwo = $factory->newInteger($two);
            $tempThree = $factory->newInteger(new BigInteger(3));

            $inv = $tempOne->divide($tempTwo)->negate();
            $s = $tempThree->negate()->squareRoot()->multiply($inv);

            $lambdas = [
                $inv->add($s),
                $inv->subtract($s)
            ];

            $lhs = $this->multiplyPoint($this->p, $lambdas[0])[0];
            $rhs = $this->p[0]->multiply($this->beta);
            $lambda = $lhs->equals($rhs) ? $lambdas[0] : $lambdas[1];

            $this->basis = static::extendedGCD($lambda->toBigInteger(), $this->order);
            ///*
            foreach ($this->basis as $basis) {
                echo strtoupper($basis['a']->toHex(true)) . "\n";
                echo strtoupper($basis['b']->toHex(true)) . "\n\n";
            }
            exit;
            //*/
        }

        $npoints = $nscalars = [];
        for ($i = 0; $i < count($points); $i++) {
            $p = $points[$i];
            $k = $scalars[$i]->toBigInteger();

            // begin split
            list($v1, $v2) = $this->basis;

            $c1 = $v2['b']->multiply($k);
            list($c1, $r) = $c1->divide($this->order);
            if ($this->order->compare($r->multiply($two)) <= 0) {
                $c1 = $c1->add($one);
            }

            $c2 = $v1['b']->negate()->multiply($k);
            list($c2, $r) = $c2->divide($this->order);
            if ($this->order->compare($r->multiply($two)) <= 0) {
                $c2 = $c2->add($one);
            }

            $p1 = $c1->multiply($v1['a']);
            $p2 = $c2->multiply($v2['a']);
            $q1 = $c1->multiply($v1['b']);
            $q2 = $c2->multiply($v2['b']);

            $k1 = $k->subtract($p1)->subtract($p2);
            $k2 = $q1->add($q2)->negate();
            // end split

            $beta = [
                $p[0]->multiply($this->beta),
                $p[1],
                clone $this->one
            ];

            if (isset($p['naf'])) {
                $beta['naf'] = array_map(function ($p) {
                    return [
                        $p[0]->multiply($this->beta),
                        $p[1],
                        clone $this->one
                    ];
                }, $p['naf']);
                $beta['nafwidth'] = $p['nafwidth'];
            }

            if ($k1->isNegative()) {
                $k1 = $k1->negate();
                $p = $this->negatePoint($p);
            }

            if ($k2->isNegative()) {
                $k2 = $k2->negate();
                $beta = $this->negatePoint($beta);
            }

            $pos = 2 * $i;
            $npoints[$pos] = $p;
            $nscalars[$pos] = $this->factory->newInteger($k1);

            $pos++;
            $npoints[$pos] = $beta;
            $nscalars[$pos] = $this->factory->newInteger($k2);
        }

        return parent::multiplyAddPoints($npoints, $nscalars);
    }

    /**
     * Returns the numerator and denominator of the slope
     *
     * @return FiniteField[]
     */
    protected function doublePointHelper(array $p)
    {
        $numerator = $this->three->multiply($p[0])->multiply($p[0]);
        $denominator = $this->two->multiply($p[1]);
        return [$numerator, $denominator];
    }

    /**
     * Doubles a jacobian coordinate on the curve
     *
     * See http://hyperelliptic.org/EFD/g1p/auto-shortw-jacobian-0.html#doubling-dbl-2009-l
     *
     * @return FiniteField[]
     */
    protected function jacobianDoublePoint(array $p)
    {
        list($x1, $y1, $z1) = $p;
        $a = $x1->multiply($x1);
        $b = $y1->multiply($y1);
        $c = $b->multiply($b);
        $d = $x1->add($b);
        $d = $d->multiply($d)->subtract($a)->subtract($c)->multiply($this->two);
        $e = $this->three->multiply($a);
        $f = $e->multiply($e);
        $x3 = $f->subtract($this->two->multiply($d));
        $y3 = $e->multiply($d->subtract($x3))->subtract(
            $this->eight->multiply($c)
        );
        $z3 = $this->two->multiply($y1)->multiply($z1);
        return [$x3, $y3, $z3];
    }

    /**
     * Doubles a "fresh" jacobian coordinate on the curve
     *
     * See http://hyperelliptic.org/EFD/g1p/auto-shortw-jacobian-0.html#doubling-mdbl-2007-bl
     *
     * @return FiniteField[]
     */
    protected function jacobianDoublePointMixed(array $p)
    {
        list($x1, $y1) = $p;
        $xx = $x1->multiply($x1);
        $yy = $y1->multiply($y1);
        $yyyy = $yy->multiply($yy);
        $s = $x1->add($yy);
        $s = $s->multiply($s)->subtract($xx)->subtract($yyyy)->multiply($this->two);
        $m = $this->three->multiply($xx);
        $t = $m->multiply($m)->subtract($this->two->multiply($s));
        $x3 = $t;
        $y3 = $s->subtract($t);
        $y3 = $m->multiply($y3)->subtract($this->eight->multiply($yyyy));
        $z3 = $this->two->multiply($y1);
        return [$x3, $y3, $z3];
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
        $temp = $x->multiply($x)->multiply($x);
        $rhs = $temp->add($this->b);

        return $lhs->equals($rhs);
    }

    /**
     * Calculates the parameters needed from the Euclidean algorithm as discussed at
     * http://diamond.boisestate.edu/~liljanab/MATH308/GuideToECC.pdf#page=148
     *
     * @param BigInteger $u
     * @param BigInteger $v
     * @return BigInteger[]
     */
    protected static function extendedGCD(BigInteger $u, BigInteger $v)
    {
        $one = new BigInteger(1);
        $zero = new BigInteger();

        $a = clone $one;
        $b = clone $zero;
        $c = clone $zero;
        $d = clone $one;

        $stop = $v->bitwise_rightShift($v->getLength() >> 1);

        $a1 = clone $zero;
        $b1 = clone $zero;
        $a2 = clone $zero;
        $b2 = clone $zero;

        $postGreatestIndex = 0;

        while (!$v->equals($zero)) {
            list($q) = $u->divide($v);

            $temp = $u;
            $u = $v;
            $v = $temp->subtract($v->multiply($q));

            $temp = $a;
            $a = $c;
            $c = $temp->subtract($a->multiply($q));

            $temp = $b;
            $b = $d;
            $d = $temp->subtract($b->multiply($q));

            if ($v->compare($stop) > 0) {
                $a0 = $v;
                $b0 = $c;
            } else {
                $postGreatestIndex++;
            }

            if ($postGreatestIndex == 1) {
                $a1 = $v;
                $b1 = $c->negate();
            }

            if ($postGreatestIndex == 2) {
                $rhs = $a0->multiply($a0)->add($b0->multiply($b0));
                $lhs = $v->multiply($v)->add($b->multiply($b));
                if ($lhs->compare($rhs) <= 0) {
                    $a2 = $a0;
                    $b2 = $b0->negate();
                } else {
                    $a2 = $v;
                    $b2 = $c->negate();
                }

                break;
            }
        }

        return [
            ['a' => $a1, 'b' => $b1],
            ['a' => $a2, 'b' => $b2]
        ];
    }
}
