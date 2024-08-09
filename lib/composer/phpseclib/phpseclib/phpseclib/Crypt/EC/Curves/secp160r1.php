<?php

/**
 * secp160r1
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

class secp160r1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF7FFFFFFF', 16));
        $this->setCoefficients(
            new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF7FFFFFFC', 16),
            new BigInteger('1C97BEFC54BD7A8B65ACF89F81D4D4ADC565FA45', 16)
        );
        $this->setBasePoint(
            new BigInteger('4A96B5688EF573284664698968C38BB913CBFC82', 16),
            new BigInteger('23A628553168947D59DCC912042351377AC5FB32', 16)
        );
        $this->setOrder(new BigInteger('0100000000000000000001F4C8F927AED3CA752257', 16));
    }
}
