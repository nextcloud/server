<?php

/**
 * Ed448
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace phpseclib3\Crypt\EC\Curves;

use phpseclib3\Crypt\EC\BaseCurves\TwistedEdwards;
use phpseclib3\Crypt\Hash;
use phpseclib3\Crypt\Random;
use phpseclib3\Math\BigInteger;

class Ed448 extends TwistedEdwards
{
    const HASH = 'shake256-912';
    const SIZE = 57;

    public function __construct()
    {
        // 2^448 - 2^224 - 1
        $this->setModulo(new BigInteger(
            'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFE' .
            'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF',
            16
        ));
        $this->setCoefficients(
            new BigInteger(1),
            // -39081
            new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFE' .
                           'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF6756', 16)
        );
        $this->setBasePoint(
            new BigInteger('4F1970C66BED0DED221D15A622BF36DA9E146570470F1767EA6DE324' .
                           'A3D3A46412AE1AF72AB66511433B80E18B00938E2626A82BC70CC05E', 16),
            new BigInteger('693F46716EB6BC248876203756C9C7624BEA73736CA3984087789C1E' .
                           '05A0C2D73AD3FF1CE67C39C4FDBD132C4ED7C8AD9808795BF230FA14', 16)
        );
        $this->setOrder(new BigInteger(
            '3FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF' .
            '7CCA23E9C44EDB49AED63690216CC2728DC58F552378C292AB5844F3',
            16
        ));
    }

    /**
     * Recover X from Y
     *
     * Implements steps 2-4 at https://tools.ietf.org/html/rfc8032#section-5.2.3
     *
     * Used by EC\Keys\Common.php
     *
     * @param BigInteger $y
     * @param boolean $sign
     * @return object[]
     */
    public function recoverX(BigInteger $y, $sign)
    {
        $y = $this->factory->newInteger($y);

        $y2 = $y->multiply($y);
        $u = $y2->subtract($this->one);
        $v = $this->d->multiply($y2)->subtract($this->one);
        $x2 = $u->divide($v);
        if ($x2->equals($this->zero)) {
            if ($sign) {
                throw new \RuntimeException('Unable to recover X coordinate (x2 = 0)');
            }
            return clone $this->zero;
        }
        // find the square root
        $exp = $this->getModulo()->add(new BigInteger(1));
        $exp = $exp->bitwise_rightShift(2);
        $x = $x2->pow($exp);

        if (!$x->multiply($x)->subtract($x2)->equals($this->zero)) {
            throw new \RuntimeException('Unable to recover X coordinate');
        }
        if ($x->isOdd() != $sign) {
            $x = $x->negate();
        }

        return [$x, $y];
    }

    /**
     * Extract Secret Scalar
     *
     * Implements steps 1-3 at https://tools.ietf.org/html/rfc8032#section-5.2.5
     *
     * Used by the various key handlers
     *
     * @param string $str
     * @return array
     */
    public function extractSecret($str)
    {
        if (strlen($str) != 57) {
            throw new \LengthException('Private Key should be 57-bytes long');
        }
        // 1.  Hash the 57-byte private key using SHAKE256(x, 114), storing the
        //     digest in a 114-octet large buffer, denoted h.  Only the lower 57
        //     bytes are used for generating the public key.
        $hash = new Hash('shake256-912');
        $h = $hash->hash($str);
        $h = substr($h, 0, 57);
        // 2.  Prune the buffer: The two least significant bits of the first
        //     octet are cleared, all eight bits the last octet are cleared, and
        //     the highest bit of the second to last octet is set.
        $h[0] = $h[0] & chr(0xFC);
        $h = strrev($h);
        $h[0] = "\0";
        $h[1] = $h[1] | chr(0x80);
        // 3.  Interpret the buffer as the little-endian integer, forming a
        //     secret scalar s.
        $dA = new BigInteger($h, 256);

        return [
            'dA' => $dA,
            'secret' => $str
        ];

        $dA->secret = $str;
        return $dA;
    }

    /**
     * Encode a point as a string
     *
     * @param array $point
     * @return string
     */
    public function encodePoint($point)
    {
        list($x, $y) = $point;
        $y = "\0" . $y->toBytes();
        if ($x->isOdd()) {
            $y[0] = $y[0] | chr(0x80);
        }
        $y = strrev($y);

        return $y;
    }

    /**
     * Creates a random scalar multiplier
     *
     * @return \phpseclib3\Math\PrimeField\Integer
     */
    public function createRandomMultiplier()
    {
        return $this->extractSecret(Random::string(57))['dA'];
    }

    /**
     * Converts an affine point to an extended homogeneous coordinate
     *
     * From https://tools.ietf.org/html/rfc8032#section-5.2.4 :
     *
     * A point (x,y) is represented in extended homogeneous coordinates (X, Y, Z, T),
     * with x = X/Z, y = Y/Z, x * y = T/Z.
     *
     * @return \phpseclib3\Math\PrimeField\Integer[]
     */
    public function convertToInternal(array $p)
    {
        if (empty($p)) {
            return [clone $this->zero, clone $this->one, clone $this->one];
        }

        if (isset($p[2])) {
            return $p;
        }

        $p[2] = clone $this->one;

        return $p;
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

        // from https://tools.ietf.org/html/rfc8032#page-18

        list($x1, $y1, $z1) = $p;

        $b = $x1->add($y1);
        $b = $b->multiply($b);
        $c = $x1->multiply($x1);
        $d = $y1->multiply($y1);
        $e = $c->add($d);
        $h = $z1->multiply($z1);
        $j = $e->subtract($this->two->multiply($h));

        $x3 = $b->subtract($e)->multiply($j);
        $y3 = $c->subtract($d)->multiply($e);
        $z3 = $e->multiply($j);

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

        if (!isset($p[2]) || !isset($q[2])) {
            throw new \RuntimeException('Affine coordinates need to be manually converted to "Jacobi" coordinates or vice versa');
        }

        if ($p[0]->equals($q[0])) {
            return !$p[1]->equals($q[1]) ? [] : $this->doublePoint($p);
        }

        // from https://tools.ietf.org/html/rfc8032#page-17

        list($x1, $y1, $z1) = $p;
        list($x2, $y2, $z2) = $q;

        $a = $z1->multiply($z2);
        $b = $a->multiply($a);
        $c = $x1->multiply($x2);
        $d = $y1->multiply($y2);
        $e = $this->d->multiply($c)->multiply($d);
        $f = $b->subtract($e);
        $g = $b->add($e);
        $h = $x1->add($y1)->multiply($x2->add($y2));

        $x3 = $a->multiply($f)->multiply($h->subtract($c)->subtract($d));
        $y3 = $a->multiply($g)->multiply($d->subtract($c));
        $z3 = $f->multiply($g);

        return [$x3, $y3, $z3];
    }
}
