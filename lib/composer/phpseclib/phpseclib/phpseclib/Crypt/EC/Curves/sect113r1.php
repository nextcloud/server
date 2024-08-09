<?php

/**
 * sect113r1
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

class sect113r1 extends Binary
{
    public function __construct()
    {
        $this->setModulo(113, 9, 0);
        $this->setCoefficients(
            '003088250CA6E7C7FE649CE85820F7',
            '00E8BEE4D3E2260744188BE0E9C723'
        );
        $this->setBasePoint(
            '009D73616F35F4AB1407D73562C10F',
            '00A52830277958EE84D1315ED31886'
        );
        $this->setOrder(new BigInteger('0100000000000000D9CCEC8A39E56F', 16));
    }
}
