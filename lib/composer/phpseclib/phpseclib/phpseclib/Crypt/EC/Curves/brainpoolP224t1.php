<?php

/**
 * brainpoolP224t1
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

class brainpoolP224t1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('D7C134AA264366862A18302575D1D787B09F075797DA89F57EC8C0FF', 16));
        $this->setCoefficients(
            new BigInteger('D7C134AA264366862A18302575D1D787B09F075797DA89F57EC8C0FC', 16), // eg. -3
            new BigInteger('4B337D934104CD7BEF271BF60CED1ED20DA14C08B3BB64F18A60888D', 16)
        );
        $this->setBasePoint(
            new BigInteger('6AB1E344CE25FF3896424E7FFE14762ECB49F8928AC0C76029B4D580', 16),
            new BigInteger('0374E9F5143E568CD23F3F4D7C0D4B1E41C8CC0D1C6ABD5F1A46DB4C', 16)
        );
        $this->setOrder(new BigInteger('D7C134AA264366862A18302575D0FB98D116BC4B6DDEBCA3A5A7939F', 16));
    }
}
