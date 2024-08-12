<?php

/**
 * sect283k1
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

class sect283k1 extends Binary
{
    public function __construct()
    {
        $this->setModulo(283, 12, 7, 5, 0);
        $this->setCoefficients(
            '000000000000000000000000000000000000000000000000000000000000000000000000',
            '000000000000000000000000000000000000000000000000000000000000000000000001'
        );
        $this->setBasePoint(
            '0503213F78CA44883F1A3B8162F188E553CD265F23C1567A16876913B0C2AC2458492836',
            '01CCDA380F1C9E318D90F95D07E5426FE87E45C0E8184698E45962364E34116177DD2259'
        );
        $this->setOrder(new BigInteger('01FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFE9AE2ED07577265DFF7F94451E061E163C61', 16));
    }
}
