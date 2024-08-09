<?php

/**
 * sect239k1
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

class sect239k1 extends Binary
{
    public function __construct()
    {
        $this->setModulo(239, 158, 0);
        $this->setCoefficients(
            '000000000000000000000000000000000000000000000000000000000000',
            '000000000000000000000000000000000000000000000000000000000001'
        );
        $this->setBasePoint(
            '29A0B6A887A983E9730988A68727A8B2D126C44CC2CC7B2A6555193035DC',
            '76310804F12E549BDB011C103089E73510ACB275FC312A5DC6B76553F0CA'
        );
        $this->setOrder(new BigInteger('2000000000000000000000000000005A79FEC67CB6E91F1C1DA800E478A5', 16));
    }
}
