<?php

/**
 * sect409r1
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

class sect409r1 extends Binary
{
    public function __construct()
    {
        $this->setModulo(409, 87, 0);
        $this->setCoefficients(
            '00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001',
            '0021A5C2C8EE9FEB5C4B9A753B7B476B7FD6422EF1F3DD674761FA99D6AC27C8A9A197B272822F6CD57A55AA4F50AE317B13545F'
        );
        $this->setBasePoint(
            '015D4860D088DDB3496B0C6064756260441CDE4AF1771D4DB01FFE5B34E59703DC255A868A1180515603AEAB60794E54BB7996A7',
            '0061B1CFAB6BE5F32BBFA78324ED106A7636B9C5A7BD198D0158AA4F5488D08F38514F1FDF4B4F40D2181B3681C364BA0273C706'
        );
        $this->setOrder(new BigInteger(
            '010000000000000000000000000000000000000000000000000001E2' .
            'AAD6A612F33307BE5FA47C3C9E052F838164CD37D9A21173',
            16
        ));
    }
}
