<?php

/**
 * sect193r1
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Crypt\EC\Curves;

use phpseclib3\Crypt\EC\BaseCurves\Binary;
use phpseclib3\Math\BigInteger;

class sect193r1 extends Binary
{
    public function __construct()
    {
        $this->setModulo(193, 15, 0);
        $this->setCoefficients(
            '0017858FEB7A98975169E171F77B4087DE098AC8A911DF7B01',
            '00FDFB49BFE6C3A89FACADAA7A1E5BBC7CC1C2E5D831478814'
        );
        $this->setBasePoint(
            '01F481BC5F0FF84A74AD6CDF6FDEF4BF6179625372D8C0C5E1',
            '0025E399F2903712CCF3EA9E3A1AD17FB0B3201B6AF7CE1B05'
        );
        $this->setOrder(new BigInteger('01000000000000000000000000C7F34A778F443ACC920EBA49', 16));
    }
}
