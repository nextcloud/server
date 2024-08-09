<?php

/**
 * secp192k1
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

class secp192k1 extends KoblitzPrime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFEE37', 16));
        $this->setCoefficients(
            new BigInteger('000000000000000000000000000000000000000000000000', 16),
            new BigInteger('000000000000000000000000000000000000000000000003', 16)
        );
        $this->setBasePoint(
            new BigInteger('DB4FF10EC057E9AE26B07D0280B7F4341DA5D1B1EAE06C7D', 16),
            new BigInteger('9B2F2F6D9C5628A7844163D015BE86344082AA88D95E2F9D', 16)
        );
        $this->setOrder(new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFE26F2FC170F69466A74DEFD8D', 16));

        $this->basis = [];
        $this->basis[] = [
            'a' => new BigInteger('00B3FB3400DEC5C4ADCEB8655C', -16),
            'b' => new BigInteger('8EE96418CCF4CFC7124FDA0F', -16)
        ];
        $this->basis[] = [
            'a' => new BigInteger('01D90D03E8F096B9948B20F0A9', -16),
            'b' => new BigInteger('42E49819ABBA9474E1083F6B', -16)
        ];
        $this->beta = $this->factory->newInteger(new BigInteger('447A96E6C647963E2F7809FEAAB46947F34B0AA3CA0BBA74', -16));
    }
}
