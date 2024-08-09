<?php

/**
 * sect409k1
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wiggint  on <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Crypt\EC\Curves;

use phpseclib3\Crypt\EC\BaseCurves\Binary;
use phpseclib3\Math\BigInteger;

class sect409k1 extends Binary
{
    public function __construct()
    {
        $this->setModulo(409, 87, 0);
        $this->setCoefficients(
            '00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
            '00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001'
        );
        $this->setBasePoint(
            '0060F05F658F49C1AD3AB1890F7184210EFD0987E307C84C27ACCFB8F9F67CC2C460189EB5AAAA62EE222EB1B35540CFE9023746',
            '01E369050B7C4E42ACBA1DACBF04299C3460782F918EA427E6325165E9EA10E3DA5F6C42E9C55215AA9CA27A5863EC48D8E0286B'
        );
        $this->setOrder(new BigInteger(
            '7FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFE5F' .
            '83B2D4EA20400EC4557D5ED3E3E7CA5B4B5C83B8E01E5FCF',
            16
        ));
    }
}
