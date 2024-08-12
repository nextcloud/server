<?php

/**
 * secp160k1
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://pear.php.net/package/Math_BigInteger
 */

namespace phpseclib3\Crypt\EC\Curves;

use phpseclib3\Crypt\EC\BaseCurves\KoblitzPrime;
use phpseclib3\Math\BigInteger;

class secp160k1 extends KoblitzPrime
{
    public function __construct()
    {
        // same as secp160r2
        $this->setModulo(new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFAC73', 16));
        $this->setCoefficients(
            new BigInteger('0000000000000000000000000000000000000000', 16),
            new BigInteger('0000000000000000000000000000000000000007', 16)
        );
        $this->setBasePoint(
            new BigInteger('3B4C382CE37AA192A4019E763036F4F5DD4D7EBB', 16),
            new BigInteger('938CF935318FDCED6BC28286531733C3F03C4FEE', 16)
        );
        $this->setOrder(new BigInteger('0100000000000000000001B8FA16DFAB9ACA16B6B3', 16));

        $this->basis = [];
        $this->basis[] = [
            'a' => new BigInteger('0096341F1138933BC2F505', -16),
            'b' => new BigInteger('FF6E9D0418C67BB8D5F562', -16)
        ];
        $this->basis[] = [
            'a' => new BigInteger('01BDCB3A09AAAABEAFF4A8', -16),
            'b' => new BigInteger('04D12329FF0EF498EA67', -16)
        ];
        $this->beta = $this->factory->newInteger(new BigInteger('645B7345A143464942CC46D7CF4D5D1E1E6CBB68', -16));
    }
}
