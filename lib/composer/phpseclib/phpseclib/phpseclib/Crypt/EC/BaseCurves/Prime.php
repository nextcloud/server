<?php

/**
 * Curves over y^2 = x^3 + a*x + b
 *
 * These are curves used in SEC 2 over prime fields: http://www.secg.org/SEC2-Ver-1.0.pdf
 * The curve is a weierstrass curve with a[1], a[3] and a[2] set to 0.
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

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Math\BigInteger;
use phpseclib3\Math\Common\FiniteField\Integer;
use phpseclib3\Math\PrimeField;
use phpseclib3\Math\PrimeField\Integer as PrimeInteger;

/**
 * Curves over y^2 = x^3 + a*x + b
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class Prime extends Base
{
    /**
     * Prime Field Integer factory
     *
     * @var \phpseclib3\Math\PrimeFields
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
     * The number two over the specified finite field
     *
     * @var object
     */
    protected $two;

    /**
     * The number three over the specified finite field
     *
     * @var object
     */
    protected $three;

    /**
     * The number four over the specified finite field
     *
     * @var object
     */
    protected $four;

    /**
     * The number eight over the specified finite field
     *
     * @var object
     */
    protected $eight;

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
    public function setModulo(BigInteger $modulo)
    {
        $this->modulo = $modulo;
        $this->factory = new PrimeField($modulo);
        $this->two = $this->factory->newInteger(new BigInteger(2));
        $this->three = $this->factory->newInteger(new BigInteger(3));
        // used by jacobian coordinates
        $this->one = $this->factory->newInteger(new BigInteger(1));
        $this->four = $this->factory->newInteger(new BigInteger(4));
        $this->eight = $this->factory->newInteger(new BigInteger(8));
    }

    /**
     * Set coefficients a and b
     */
    public function setCoefficients(BigInteger $a, BigInteger $b)
    {
        if (!isset($this->factory)) {
            throw new \RuntimeException('setModulo needs to be called before this method');
        }
        $this->a = $this->factory->newInteger($a);
        $this->b = $this->factory->newInteger($b);
    }

    /**
     * Set x and y coordinates for the base point
     *
     * @param BigInteger|PrimeInteger $x
     * @param BigInteger|PrimeInteger $y
     * @return PrimeInteger[]
     */
    public function setBasePoint($x, $y)
    {
        switch (true) {
            case !$x instanceof BigInteger && !$x instanceof PrimeInteger:
                throw new \UnexpectedValueException('Argument 1 passed to Prime::setBasePoint() must be an instance of either BigInteger or PrimeField\Integer');
            case !$y instanceof BigInteger && !$y instanceof PrimeInteger:
                throw new \UnexpectedValueException('Argument 2 passed to Prime::setBasePoint() must be an instance of either BigInteger or PrimeField\Integer');
        }
        if (!isset($this->factory)) {
            throw new \RuntimeException('setModulo needs to be called before this method');
        }
        $this->p = [
            $x instanceof BigInteger ? $this->factory->newInteger($x) : $x,
            $y instanceof BigInteger ? $this->factory->newInteger($y) : $y
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
     * Adds two "fresh" jacobian form on the curve
     *
     * @return FiniteField[]
     */
    protected function jacobianAddPointMixedXY(array $p, array $q)
    {
        list($u1, $s1) = $p;
        list($u2, $s2) = $q;
        if ($u1->equals($u2)) {
            if (!$s1->equals($s2)) {
                return [];
            } else {
                return $this->doublePoint($p);
            }
        }
        $h = $u2->subtract($u1);
        $r = $s2->subtract($s1);
        $h2 = $h->multiply($h);
        $h3 = $h2->multiply($h);
        $v = $u1->multiply($h2);
        $x3 = $r->multiply($r)->subtract($h3)->subtract($v->multiply($this->two));
        $y3 = $r->multiply(
            $v->subtract($x3)
        )->subtract(
            $s1->multiply($h3)
        );
        return [$x3, $y3, $h];
    }

    /**
     * Adds one "fresh" jacobian form on the curve
     *
     * The second parameter should be the "fresh" one
     *
     * @return FiniteField[]
     */
    protected function jacobianAddPointMixedX(array $p, array $q)
    {
        list($u1, $s1, $z1) = $p;
        list($x2, $y2) = $q;

        $z12 = $z1->multiply($z1);

        $u2 = $x2->multiply($z12);
        $s2 = $y2->multiply($z12->multiply($z1));
        if ($u1->equals($u2)) {
            if (!$s1->equals($s2)) {
                return [];
            } else {
                return $this->doublePoint($p);
            }
        }
        $h = $u2->subtract($u1);
        $r = $s2->subtract($s1);
        $h2 = $h->multiply($h);
        $h3 = $h2->multiply($h);
        $v = $u1->multiply($h2);
        $x3 = $r->multiply($r)->subtract($h3)->subtract($v->multiply($this->two));
        $y3 = $r->multiply(
            $v->subtract($x3)
        )->subtract(
            $s1->multiply($h3)
        );
        $z3 = $h->multiply($z1);
        return [$x3, $y3, $z3];
    }

    /**
     * Adds two jacobian coordinates on the curve
     *
     * @return FiniteField[]
     */
    protected function jacobianAddPoint(array $p, array $q)
    {
        list($x1, $y1, $z1) = $p;
        list($x2, $y2, $z2) = $q;

        $z12 = $z1->multiply($z1);
        $z22 = $z2->multiply($z2);

        $u1 = $x1->multiply($z22);
        $u2 = $x2->multiply($z12);
        $s1 = $y1->multiply($z22->multiply($z2));
        $s2 = $y2->multiply($z12->multiply($z1));
        if ($u1->equals($u2)) {
            if (!$s1->equals($s2)) {
                return [];
            } else {
                return $this->doublePoint($p);
            }
        }
        $h = $u2->subtract($u1);
        $r = $s2->subtract($s1);
        $h2 = $h->multiply($h);
        $h3 = $h2->multiply($h);
        $v = $u1->multiply($h2);
        $x3 = $r->multiply($r)->subtract($h3)->subtract($v->multiply($this->two));
        $y3 = $r->multiply(
            $v->subtract($x3)
        )->subtract(
            $s1->multiply($h3)
        );
        $z3 = $h->multiply($z1)->multiply($z2);
        return [$x3, $y3, $z3];
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

        // use jacobian coordinates
        if (isset($p[2]) && isset($q[2])) {
            if (isset($p['fresh']) && isset($q['fresh'])) {
                return $this->jacobianAddPointMixedXY($p, $q);
            }
            if (isset($p['fresh'])) {
                return $this->jacobianAddPointMixedX($q, $p);
            }
            if (isset($q['fresh'])) {
                return $this->jacobianAddPointMixedX($p, $q);
            }
            return $this->jacobianAddPoint($p, $q);
        }

        if (isset($p[2]) || isset($q[2])) {
            throw new \RuntimeException('Affine coordinates need to be manually converted to Jacobi coordinates or vice versa');
        }

        if ($p[0]->equals($q[0])) {
            if (!$p[1]->equals($q[1])) {
                return [];
            } else { // eg. doublePoint
                list($numerator, $denominator) = $this->doublePointHelper($p);
            }
        } else {
            $numerator = $q[1]->subtract($p[1]);
            $denominator = $q[0]->subtract($p[0]);
        }
        $slope = $numerator->divide($denominator);
        $x = $slope->multiply($slope)->subtract($p[0])->subtract($q[0]);
        $y = $slope->multiply($p[0]->subtract($x))->subtract($p[1]);

        return [$x, $y];
    }

    /**
     * Returns the numerator and denominator of the slope
     *
     * @return FiniteField[]
     */
    protected function doublePointHelper(array $p)
    {
        $numerator = $this->three->multiply($p[0])->multiply($p[0])->add($this->a);
        $denominator = $this->two->multiply($p[1]);
        return [$numerator, $denominator];
    }

    /**
     * Doubles a jacobian coordinate on the curve
     *
     * @return FiniteField[]
     */
    protected function jacobianDoublePoint(array $p)
    {
        list($x, $y, $z) = $p;
        $x2 = $x->multiply($x);
        $y2 = $y->multiply($y);
        $z2 = $z->multiply($z);
        $s = $this->four->multiply($x)->multiply($y2);
        $m1 = $this->three->multiply($x2);
        $m2 = $this->a->multiply($z2->multiply($z2));
        $m = $m1->add($m2);
        $x1 = $m->multiply($m)->subtract($this->two->multiply($s));
        $y1 = $m->multiply($s->subtract($x1))->subtract(
            $this->eight->multiply($y2->multiply($y2))
        );
        $z1 = $this->two->multiply($y)->multiply($z);
        return [$x1, $y1, $z1];
    }

    /**
     * Doubles a "fresh" jacobian coordinate on the curve
     *
     * @return FiniteField[]
     */
    protected function jacobianDoublePointMixed(array $p)
    {
        list($x, $y) = $p;
        $x2 = $x->multiply($x);
        $y2 = $y->multiply($y);
        $s = $this->four->multiply($x)->multiply($y2);
        $m1 = $this->three->multiply($x2);
        $m = $m1->add($this->a);
        $x1 = $m->multiply($m)->subtract($this->two->multiply($s));
        $y1 = $m->multiply($s->subtract($x1))->subtract(
            $this->eight->multiply($y2->multiply($y2))
        );
        $z1 = $this->two->multiply($y);
        return [$x1, $y1, $z1];
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

        // use jacobian coordinates
        if (isset($p[2])) {
            if (isset($p['fresh'])) {
                return $this->jacobianDoublePointMixed($p);
            }
            return $this->jacobianDoublePoint($p);
        }

        list($numerator, $denominator) = $this->doublePointHelper($p);

        $slope = $numerator->divide($denominator);

        $x = $slope->multiply($slope)->subtract($p[0])->subtract($p[0]);
        $y = $slope->multiply($p[0]->subtract($x))->subtract($p[1]);

        return [$x, $y];
    }

    /**
     * Returns the X coordinate and the derived Y coordinate
     *
     * @return array
     */
    public function derivePoint($m)
    {
        $y = ord(Strings::shift($m));
        $x = new BigInteger($m, 256);
        $xp = $this->convertInteger($x);
        switch ($y) {
            case 2:
                $ypn = false;
                break;
            case 3:
                $ypn = true;
                break;
            default:
                throw new \RuntimeException('Coordinate not in recognized format');
        }
        $temp = $xp->multiply($this->a);
        $temp = $xp->multiply($xp)->multiply($xp)->add($temp);
        $temp = $temp->add($this->b);
        $b = $temp->squareRoot();
        if (!$b) {
            throw new \RuntimeException('Unable to derive Y coordinate');
        }
        $bn = $b->isOdd();
        $yp = $ypn == $bn ? $b : $b->negate();
        return [$xp, $yp];
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
        $temp = $x->multiply($this->a);
        $temp = $x->multiply($x)->multiply($x)->add($temp);
        $rhs = $temp->add($this->b);

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
     * Multiply and Add Points
     *
     * Adapted from:
     * https://github.com/indutny/elliptic/blob/725bd91/lib/elliptic/curve/base.js#L125
     *
     * @return int[]
     */
    public function multiplyAddPoints(array $points, array $scalars)
    {
        $length = count($points);

        foreach ($points as &$point) {
            $point = $this->convertToInternal($point);
        }

        $wnd = [$this->getNAFPoints($points[0], 7)];
        $wndWidth = [isset($points[0]['nafwidth']) ? $points[0]['nafwidth'] : 7];
        for ($i = 1; $i < $length; $i++) {
            $wnd[] = $this->getNAFPoints($points[$i], 1);
            $wndWidth[] = isset($points[$i]['nafwidth']) ? $points[$i]['nafwidth'] : 1;
        }

        $naf = [];

        // comb all window NAFs

        $max = 0;
        for ($i = $length - 1; $i >= 1; $i -= 2) {
            $a = $i - 1;
            $b = $i;
            if ($wndWidth[$a] != 1 || $wndWidth[$b] != 1) {
                $naf[$a] = $scalars[$a]->getNAF($wndWidth[$a]);
                $naf[$b] = $scalars[$b]->getNAF($wndWidth[$b]);
                $max = max(count($naf[$a]), count($naf[$b]), $max);
                continue;
            }

            $comb = [
                $points[$a], // 1
                null,        // 3
                null,        // 5
                $points[$b]  // 7
            ];

            $comb[1] = $this->addPoint($points[$a], $points[$b]);
            $comb[2] = $this->addPoint($points[$a], $this->negatePoint($points[$b]));

            $index = [
                -3, /* -1 -1 */
                -1, /* -1  0 */
                -5, /* -1  1 */
                -7, /*  0 -1 */
                 0, /*  0 -1 */
                 7, /*  0  1 */
                 5, /*  1 -1 */
                 1, /*  1  0 */
                 3  /*  1  1 */
            ];

            $jsf = self::getJSFPoints($scalars[$a], $scalars[$b]);

            $max = max(count($jsf[0]), $max);
            if ($max > 0) {
                $naf[$a] = array_fill(0, $max, 0);
                $naf[$b] = array_fill(0, $max, 0);
            } else {
                $naf[$a] = [];
                $naf[$b] = [];
            }

            for ($j = 0; $j < $max; $j++) {
                $ja = isset($jsf[0][$j]) ? $jsf[0][$j] : 0;
                $jb = isset($jsf[1][$j]) ? $jsf[1][$j] : 0;

                $naf[$a][$j] = $index[3 * ($ja + 1) + $jb + 1];
                $naf[$b][$j] = 0;
                $wnd[$a] = $comb;
            }
        }

        $acc = [];
        $temp = [0, 0, 0, 0];
        for ($i = $max; $i >= 0; $i--) {
            $k = 0;
            while ($i >= 0) {
                $zero = true;
                for ($j = 0; $j < $length; $j++) {
                    $temp[$j] = isset($naf[$j][$i]) ? $naf[$j][$i] : 0;
                    if ($temp[$j] != 0) {
                        $zero = false;
                    }
                }
                if (!$zero) {
                    break;
                }
                $k++;
                $i--;
            }

            if ($i >= 0) {
                $k++;
            }
            while ($k--) {
                $acc = $this->doublePoint($acc);
            }

            if ($i < 0) {
                break;
            }

            for ($j = 0; $j < $length; $j++) {
                $z = $temp[$j];
                $p = null;
                if ($z == 0) {
                    continue;
                }
                $p = $z > 0 ?
                    $wnd[$j][($z - 1) >> 1] :
                    $this->negatePoint($wnd[$j][(-$z - 1) >> 1]);
                $acc = $this->addPoint($acc, $p);
            }
        }

        return $this->convertToAffine($acc);
    }

    /**
     * Precomputes NAF points
     *
     * Adapted from:
     * https://github.com/indutny/elliptic/blob/725bd91/lib/elliptic/curve/base.js#L351
     *
     * @return int[]
     */
    private function getNAFPoints(array $point, $wnd)
    {
        if (isset($point['naf'])) {
            return $point['naf'];
        }

        $res = [$point];
        $max = (1 << $wnd) - 1;
        $dbl = $max == 1 ? null : $this->doublePoint($point);
        for ($i = 1; $i < $max; $i++) {
            $res[] = $this->addPoint($res[$i - 1], $dbl);
        }

        $point['naf'] = $res;

        /*
        $str = '';
        foreach ($res as $re) {
            $re[0] = bin2hex($re[0]->toBytes());
            $re[1] = bin2hex($re[1]->toBytes());
            $str.= "            ['$re[0]', '$re[1]'],\r\n";
        }
        file_put_contents('temp.txt', $str);
        exit;
        */

        return $res;
    }

    /**
     * Precomputes points in Joint Sparse Form
     *
     * Adapted from:
     * https://github.com/indutny/elliptic/blob/725bd91/lib/elliptic/utils.js#L96
     *
     * @return int[]
     */
    private static function getJSFPoints(Integer $k1, Integer $k2)
    {
        static $three;
        if (!isset($three)) {
            $three = new BigInteger(3);
        }

        $jsf = [[], []];
        $k1 = $k1->toBigInteger();
        $k2 = $k2->toBigInteger();
        $d1 = 0;
        $d2 = 0;

        while ($k1->compare(new BigInteger(-$d1)) > 0 || $k2->compare(new BigInteger(-$d2)) > 0) {
            // first phase
            $m14 = $k1->testBit(0) + 2 * $k1->testBit(1);
            $m14 += $d1;
            $m14 &= 3;

            $m24 = $k2->testBit(0) + 2 * $k2->testBit(1);
            $m24 += $d2;
            $m24 &= 3;

            if ($m14 == 3) {
                $m14 = -1;
            }
            if ($m24 == 3) {
                $m24 = -1;
            }

            $u1 = 0;
            if ($m14 & 1) { // if $m14 is odd
                $m8 = $k1->testBit(0) + 2 * $k1->testBit(1) + 4 * $k1->testBit(2);
                $m8 += $d1;
                $m8 &= 7;
                $u1 = ($m8 == 3 || $m8 == 5) && $m24 == 2 ? -$m14 : $m14;
            }
            $jsf[0][] = $u1;

            $u2 = 0;
            if ($m24 & 1) { // if $m24 is odd
                $m8 = $k2->testBit(0) + 2 * $k2->testBit(1) + 4 * $k2->testBit(2);
                $m8 += $d2;
                $m8 &= 7;
                $u2 = ($m8 == 3 || $m8 == 5) && $m14 == 2 ? -$m24 : $m24;
            }
            $jsf[1][] = $u2;

            // second phase
            if (2 * $d1 == $u1 + 1) {
                $d1 = 1 - $d1;
            }
            if (2 * $d2 == $u2 + 1) {
                $d2 = 1 - $d2;
            }
            $k1 = $k1->bitwise_rightShift(1);
            $k2 = $k2->bitwise_rightShift(1);
        }

        return $jsf;
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
