<?php

/**
 * Curve448
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2019 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Crypt\EC\Curves;

use phpseclib3\Crypt\EC\BaseCurves\Montgomery;
use phpseclib3\Math\BigInteger;

class Curve448 extends Montgomery
{
    public function __construct()
    {
        // 2^448 - 2^224 - 1
        $this->setModulo(new BigInteger(
            'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFE' .
            'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF',
            16
        ));
        $this->a24 = $this->factory->newInteger(new BigInteger('39081'));
        $this->p = [$this->factory->newInteger(new BigInteger(5))];
        // 2^446 - 0x8335dc163bb124b65129c96fde933d8d723a70aadc873d6d54a7bb0d
        $this->setOrder(new BigInteger(
            '3FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF' .
            '7CCA23E9C44EDB49AED63690216CC2728DC58F552378C292AB5844F3',
            16
        ));

        /*
        $this->setCoefficients(
            new BigInteger('156326'), // a
        );
        $this->setBasePoint(
            new BigInteger(5),
            new BigInteger(
                '355293926785568175264127502063783334808976399387714271831880898' .
                '435169088786967410002932673765864550910142774147268105838985595290' .
                '606362')
        );
        */
    }

    /**
     * Multiply a point on the curve by a scalar
     *
     * Modifies the scalar as described at https://tools.ietf.org/html/rfc7748#page-8
     *
     * @return array
     */
    public function multiplyPoint(array $p, BigInteger $d)
    {
        //$r = strrev(sodium_crypto_scalarmult($d->toBytes(), strrev($p[0]->toBytes())));
        //return [$this->factory->newInteger(new BigInteger($r, 256))];

        $d = $d->toBytes();
        $d[0] = $d[0] & "\xFC";
        $d = strrev($d);
        $d |= "\x80";
        $d = new BigInteger($d, 256);

        return parent::multiplyPoint($p, $d);
    }

    /**
     * Creates a random scalar multiplier
     *
     * @return BigInteger
     */
    public function createRandomMultiplier()
    {
        return BigInteger::random(446);
    }

    /**
     * Performs range check
     */
    public function rangeCheck(BigInteger $x)
    {
        if ($x->getLength() > 448 || $x->isNegative()) {
            throw new \RangeException('x must be a positive integer less than 446 bytes in length');
        }
    }
}
