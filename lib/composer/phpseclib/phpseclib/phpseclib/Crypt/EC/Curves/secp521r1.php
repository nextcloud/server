<?php

/**
 * secp521r1
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

class secp521r1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('01FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF' .
                                        'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF' .
                                        'FFFF', 16));
        $this->setCoefficients(
            new BigInteger('01FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF' .
                           'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF' .
                           'FFFC', 16),
            new BigInteger('0051953EB9618E1C9A1F929A21A0B68540EEA2DA725B99B315F3B8B489918EF1' .
                           '09E156193951EC7E937B1652C0BD3BB1BF073573DF883D2C34F1EF451FD46B50' .
                           '3F00', 16)
        );
        $this->setBasePoint(
            new BigInteger('00C6858E06B70404E9CD9E3ECB662395B4429C648139053FB521F828AF606B4D' .
                           '3DBAA14B5E77EFE75928FE1DC127A2FFA8DE3348B3C1856A429BF97E7E31C2E5' .
                           'BD66', 16),
            new BigInteger('011839296A789A3BC0045C8A5FB42C7D1BD998F54449579B446817AFBD17273E' .
                           '662C97EE72995EF42640C550B9013FAD0761353C7086A272C24088BE94769FD1' .
                           '6650', 16)
        );
        $this->setOrder(new BigInteger('01FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF' .
                                       'FFFA51868783BF2F966B7FCC0148F709A5D03BB5C9B8899C47AEBB6FB71E9138' .
                                       '6409', 16));
    }
}
