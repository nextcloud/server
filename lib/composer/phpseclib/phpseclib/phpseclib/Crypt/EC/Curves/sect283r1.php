<?php

/**
 * sect283r1
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

class sect283r1 extends Binary
{
    public function __construct()
    {
        $this->setModulo(283, 12, 7, 5, 0);
        $this->setCoefficients(
            '000000000000000000000000000000000000000000000000000000000000000000000001',
            '027B680AC8B8596DA5A4AF8A19A0303FCA97FD7645309FA2A581485AF6263E313B79A2F5'
        );
        $this->setBasePoint(
            '05F939258DB7DD90E1934F8C70B0DFEC2EED25B8557EAC9C80E2E198F8CDBECD86B12053',
            '03676854FE24141CB98FE6D4B20D02B4516FF702350EDDB0826779C813F0DF45BE8112F4'
        );
        $this->setOrder(new BigInteger('03FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEF90399660FC938A90165B042A7CEFADB307', 16));
    }
}
