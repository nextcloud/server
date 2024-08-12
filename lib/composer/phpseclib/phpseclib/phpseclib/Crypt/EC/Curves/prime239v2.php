<?php

/**
 * prime239v2
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

class prime239v2 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('7FFFFFFFFFFFFFFFFFFFFFFF7FFFFFFFFFFF8000000000007FFFFFFFFFFF', 16));
        $this->setCoefficients(
            new BigInteger('7FFFFFFFFFFFFFFFFFFFFFFF7FFFFFFFFFFF8000000000007FFFFFFFFFFC', 16),
            new BigInteger('617FAB6832576CBBFED50D99F0249C3FEE58B94BA0038C7AE84C8C832F2C', 16)
        );
        $this->setBasePoint(
            new BigInteger('38AF09D98727705120C921BB5E9E26296A3CDCF2F35757A0EAFD87B830E7', 16),
            new BigInteger('5B0125E4DBEA0EC7206DA0FC01D9B081329FB555DE6EF460237DFF8BE4BA', 16)
        );
        $this->setOrder(new BigInteger('7FFFFFFFFFFFFFFFFFFFFFFF800000CFA7E8594377D414C03821BC582063', 16));
    }
}
