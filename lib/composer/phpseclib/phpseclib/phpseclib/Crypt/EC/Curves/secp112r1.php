<?php

/**
 * secp112r1
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

class secp112r1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('DB7C2ABF62E35E668076BEAD208B', 16));
        $this->setCoefficients(
            new BigInteger('DB7C2ABF62E35E668076BEAD2088', 16),
            new BigInteger('659EF8BA043916EEDE8911702B22', 16)
        );
        $this->setBasePoint(
            new BigInteger('09487239995A5EE76B55F9C2F098', 16),
            new BigInteger('A89CE5AF8724C0A23E0E0FF77500', 16)
        );
        $this->setOrder(new BigInteger('DB7C2ABF62E35E7628DFAC6561C5', 16));
    }
}
