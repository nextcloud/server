<?php

/**
 * Ed25519
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

class Ed25519 extends TwistedEdwards
{
    const HASH = 'sha512';
    /*
      Per https://tools.ietf.org/html/rfc8032#page-6 EdDSA has several parameters, one of which is b:

      2.   An integer b with 2^(b-1) > p.  EdDSA public keys have exactly b
           bits, and EdDSA signatures have exactly 2*b bits.  b is
           recommended to be a multiple of 8, so public key and signature
           lengths are an integral number of octets.

      SIZE corresponds to b
    */
    const SIZE = 32;

    public function __construct()
    {
        // 2^255 - 19
        $this->setModulo(new BigInteger('7FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFED', 16));
        $this->setCoefficients(
            // -1
            new BigInteger('7FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEC', 16), // a
            // -121665/121666
            new BigInteger('52036CEE2B6FFE738CC740797779E89800700A4D4141D8AB75EB4DCA135978A3', 16)  // d
        );
        $this->setBasePoint(
            new BigInteger('216936D3CD6E53FEC0A4E231FDD6DC5C692CC7609525A7B2C9562D608F25D51A', 16),
            new BigInteger('6666666666666666666666666666666666666666666666666666666666666658', 16)
        );
        $this->setOrder(new BigInteger('1000000000000000000000000000000014DEF9DEA2F79CD65812631A5CF5D3ED', 16));
        // algorithm 14.47 from http://cacr.uwaterloo.ca/hac/about/chap14.pdf#page=16
        /*
        $this->setReduction(function($x) {
            $parts = $x->bitwise_split(255);
            $className = $this->className;

            if (count($parts) > 2) {
                list(, $r) = $x->divide($className::$modulo);
                return $r;
            }

            $zero = new BigInteger();
            $c = new BigInteger(19);

            switch (count($parts)) {
                case 2:
                    list($qi, $ri) = $parts;
                    break;
                case 1:
                    $qi = $zero;
                    list($ri) = $parts;
                    break;
                case 0:
                    return $zero;
            }
            $r = $ri;

            while ($qi->compare($zero) > 0) {
                $temp = $qi->multiply($c)->bitwise_split(255);
                if (count($temp) == 2) {
                    list($qi, $ri) = $temp;
                } else {
                    $qi = $zero;
                    list($ri) = $temp;
                }
                $r = $r->add($ri);
            }

            while ($r->compare($className::$modulo) > 0) {
                $r = $r->subtract($className::$modulo);
            }
            return $r;
        });
        */
    }

    /**
     * Recover X from Y
     *
     * Implements steps 2-4 at https://tools.ietf.org/html/rfc8032#section-5.1.3
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
        $v = $this->d->multiply($y2)->add($this->one);
        $x2 = $u->divide($v);
        if ($x2->equals($this->zero)) {
            if ($sign) {
                throw new \RuntimeException('Unable to recover X coordinate (x2 = 0)');
            }
            return clone $this->zero;
        }
        // find the square root
        /* we don't do $x2->squareRoot() because, quoting from
           https://tools.ietf.org/html/rfc8032#section-5.1.1:

           "For point decoding or "decompression", square roots modulo p are
            needed.  They can be computed using the Tonelli-Shanks algorithm or
            the special case for p = 5 (mod 8).  To find a square root of a,
            first compute the candidate root x = a^((p+3)/8) (mod p)."
         */
        $exp = $this->getModulo()->add(new BigInteger(3));
        $exp = $exp->bitwise_rightShift(3);
        $x = $x2->pow($exp);

        // If v x^2 = -u (mod p), set x <-- x * 2^((p-1)/4), which is a square root.
        if (!$x->multiply($x)->subtract($x2)->equals($this->zero)) {
            $temp = $this->getModulo()->subtract(new BigInteger(1));
            $temp = $temp->bitwise_rightShift(2);
            $temp = $this->two->pow($temp);
            $x = $x->multiply($temp);
            if (!$x->multiply($x)->subtract($x2)->equals($this->zero)) {
                throw new \RuntimeException('Unable to recover X coordinate');
            }
        }
        if ($x->isOdd() != $sign) {
            $x = $x->negate();
        }

        return [$x, $y];
    }

    /**
     * Extract Secret Scalar
     *
     * Implements steps 1-3 at https://tools.ietf.org/html/rfc8032#section-5.1.5
     *
     * Used by the various key handlers
     *
     * @param string $str
     * @return array
     */
    public function extractSecret($str)
    {
        if (strlen($str) != 32) {
            throw new \LengthException('Private Key should be 32-bytes long');
        }
        // 1.  Hash the 32-byte private key using SHA-512, storing the digest in
        //     a 64-octet large buffer, denoted h.  Only the lower 32 bytes are
        //     used for generating the public key.
        $hash = new Hash('sha512');
        $h = $hash->hash($str);
        $h = substr($h, 0, 32);
        // 2.  Prune the buffer: The lowest three bits of the first octet are
        //     cleared, the highest bit of the last octet is cleared, and the
        //     second highest bit of the last octet is set.
        $h[0] = $h[0] & chr(0xF8);
        $h = strrev($h);
        $h[0] = ($h[0] & chr(0x3F)) | chr(0x40);
        // 3.  Interpret the buffer as the little-endian integer, forming a
        //     secret scalar s.
        $dA = new BigInteger($h, 256);

        return [
            'dA' => $dA,
            'secret' => $str
        ];
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
        $y = $y->toBytes();
        $y[0] = $y[0] & chr(0x7F);
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
        return $this->extractSecret(Random::string(32))['dA'];
    }

    /**
     * Converts an affine point to an extended homogeneous coordinate
     *
     * From https://tools.ietf.org/html/rfc8032#section-5.1.4 :
     *
     * A point (x,y) is represented in extended homogeneous coordinates (X, Y, Z, T),
     * with x = X/Z, y = Y/Z, x * y = T/Z.
     *
     * @return \phpseclib3\Math\PrimeField\Integer[]
     */
    public function convertToInternal(array $p)
    {
        if (empty($p)) {
            return [clone $this->zero, clone $this->one, clone $this->one, clone $this->zero];
        }

        if (isset($p[2])) {
            return $p;
        }

        $p[2] = clone $this->one;
        $p[3] = $p[0]->multiply($p[1]);

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

        // from https://tools.ietf.org/html/rfc8032#page-12

        list($x1, $y1, $z1, $t1) = $p;

        $a = $x1->multiply($x1);
        $b = $y1->multiply($y1);
        $c = $this->two->multiply($z1)->multiply($z1);
        $h = $a->add($b);
        $temp = $x1->add($y1);
        $e = $h->subtract($temp->multiply($temp));
        $g = $a->subtract($b);
        $f = $c->add($g);

        $x3 = $e->multiply($f);
        $y3 = $g->multiply($h);
        $t3 = $e->multiply($h);
        $z3 = $f->multiply($g);

        return [$x3, $y3, $z3, $t3];
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

        // from https://tools.ietf.org/html/rfc8032#page-12

        list($x1, $y1, $z1, $t1) = $p;
        list($x2, $y2, $z2, $t2) = $q;

        $a = $y1->subtract($x1)->multiply($y2->subtract($x2));
        $b = $y1->add($x1)->multiply($y2->add($x2));
        $c = $t1->multiply($this->two)->multiply($this->d)->multiply($t2);
        $d = $z1->multiply($this->two)->multiply($z2);
        $e = $b->subtract($a);
        $f = $d->subtract($c);
        $g = $d->add($c);
        $h = $b->add($a);

        $x3 = $e->multiply($f);
        $y3 = $g->multiply($h);
        $t3 = $e->multiply($h);
        $z3 = $f->multiply($g);

        return [$x3, $y3, $z3, $t3];
    }
}
