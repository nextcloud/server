<?php

/**
 * brainpoolP160t1
 *
 * This curve is a twisted version of brainpoolP160r1 with A = -3. With brainpool,
 * the curves ending in r1 are the "regular" curves and the curves ending in "t1"
 * are the twisted version of the r1 curves. Per https://tools.ietf.org/html/rfc5639#page-7
 * you can convert a point on an r1 curve to a point on a t1 curve thusly:
 *
 *     F(x,y) := (x*Z^2, y*Z^3)
 *
 * The advantage of A = -3 is that some of the point doubling and point addition can be
 * slightly optimized. See http://hyperelliptic.org/EFD/g1p/auto-shortw-projective-3.html
 * vs http://hyperelliptic.org/EFD/g1p/auto-shortw-projective.html for example.
 *
 * phpseclib does not currently take advantage of this optimization opportunity
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

class brainpoolP160t1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('E95E4A5F737059DC60DFC7AD95B3D8139515620F', 16));
        $this->setCoefficients(
            new BigInteger('E95E4A5F737059DC60DFC7AD95B3D8139515620C', 16), // eg. -3
            new BigInteger('7A556B6DAE535B7B51ED2C4D7DAA7A0B5C55F380', 16)
        );
        $this->setBasePoint(
            new BigInteger('B199B13B9B34EFC1397E64BAEB05ACC265FF2378', 16),
            new BigInteger('ADD6718B7C7C1961F0991B842443772152C9E0AD', 16)
        );
        $this->setOrder(new BigInteger('E95E4A5F737059DC60DF5991D45029409E60FC09', 16));
    }
}
