<?php

/**
 * secp192r1
 *
 * This is the NIST P-192 curve
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Crypt\EC\Curves;

use phpseclib3\Crypt\EC\BaseCurves\Prime;
use phpseclib3\Math\BigInteger;

class secp192r1 extends Prime
{
    public function __construct()
    {
        $modulo = new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFFFFFFFFFFFF', 16);
        $this->setModulo($modulo);

        // algorithm 2.27 from http://diamond.boisestate.edu/~liljanab/MATH308/GuideToECC.pdf#page=66
        /* in theory this should be faster than regular modular reductions save for one small issue.
           to convert to / from base-2**8 with BCMath you have to call bcmul() and bcdiv() a lot.
           to convert to / from base-2**8 with PHP64 you have to call base256_rshift() a lot.
           in short, converting to / from base-2**8 is pretty expensive and that expense is
           enough to offset whatever else might be gained by a simplified reduction algorithm.
           now, if PHP supported unsigned integers things might be different. no bit-shifting
           would be required for the PHP engine and it'd be a lot faster. but as is, BigInteger
           uses base-2**31 or base-2**26 depending on whether or not the system is has a 32-bit
           or a 64-bit OS.
        */
        /*
        $m_length = $this->getLengthInBytes();
        $this->setReduction(function($c) use ($m_length) {
            $cBytes = $c->toBytes();
            $className = $this->className;

            if (strlen($cBytes) > 2 * $m_length) {
                list(, $r) = $c->divide($className::$modulo);
                return $r;
            }

            $c = str_pad($cBytes, 48, "\0", STR_PAD_LEFT);
            $c = array_reverse(str_split($c, 8));

            $null = "\0\0\0\0\0\0\0\0";
            $s1 = new BigInteger($c[2] . $c[1] . $c[0], 256);
            $s2 = new BigInteger($null . $c[3] . $c[3], 256);
            $s3 = new BigInteger($c[4] . $c[4] . $null, 256);
            $s4 = new BigInteger($c[5] . $c[5] . $c[5], 256);

            $r = $s1->add($s2)->add($s3)->add($s4);
            while ($r->compare($className::$modulo) >= 0) {
                $r = $r->subtract($className::$modulo);
            }

            return $r;
        });
        */

        $this->setCoefficients(
            new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFFFFFFFFFFFC', 16),
            new BigInteger('64210519E59C80E70FA7E9AB72243049FEB8DEECC146B9B1', 16)
        );
        $this->setBasePoint(
            new BigInteger('188DA80EB03090F67CBF20EB43A18800F4FF0AFD82FF1012', 16),
            new BigInteger('07192B95FFC8DA78631011ED6B24CDD573F977A11E794811', 16)
        );
        $this->setOrder(new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFF99DEF836146BC9B1B4D22831', 16));
    }
}
