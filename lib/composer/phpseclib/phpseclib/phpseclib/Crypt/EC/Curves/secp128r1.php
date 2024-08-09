<?php

/**
 * secp128r1
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

class secp128r1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('FFFFFFFDFFFFFFFFFFFFFFFFFFFFFFFF', 16));
        $this->setCoefficients(
            new BigInteger('FFFFFFFDFFFFFFFFFFFFFFFFFFFFFFFC', 16),
            new BigInteger('E87579C11079F43DD824993C2CEE5ED3', 16)
        );
        $this->setBasePoint(
            new BigInteger('161FF7528B899B2D0C28607CA52C5B86', 16),
            new BigInteger('CF5AC8395BAFEB13C02DA292DDED7A83', 16)
        );
        $this->setOrder(new BigInteger('FFFFFFFE0000000075A30D1B9038A115', 16));
    }
}
