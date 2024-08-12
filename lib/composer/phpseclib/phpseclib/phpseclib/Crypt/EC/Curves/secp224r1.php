<?php

/**
 * secp224r1
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

class secp224r1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF000000000000000000000001', 16));
        $this->setCoefficients(
            new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFE', 16),
            new BigInteger('B4050A850C04B3ABF54132565044B0B7D7BFD8BA270B39432355FFB4', 16)
        );
        $this->setBasePoint(
            new BigInteger('B70E0CBD6BB4BF7F321390B94A03C1D356C21122343280D6115C1D21', 16),
            new BigInteger('BD376388B5F723FB4C22DFE6CD4375A05A07476444D5819985007E34', 16)
        );
        $this->setOrder(new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFF16A2E0B8F03E13DD29455C5C2A3D', 16));
    }
}
