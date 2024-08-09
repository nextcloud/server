<?php

/**
 * prime192v3
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

class prime192v3 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFFFFFFFFFFFF', 16));
        $this->setCoefficients(
            new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFFFFFFFFFFFC', 16),
            new BigInteger('22123DC2395A05CAA7423DAECCC94760A7D462256BD56916', 16)
        );
        $this->setBasePoint(
            new BigInteger('7D29778100C65A1DA1783716588DCE2B8B4AEE8E228F1896', 16),
            new BigInteger('38A90F22637337334B49DCB66A6DC8F9978ACA7648A943B0', 16)
        );
        $this->setOrder(new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFF7A62D031C83F4294F640EC13', 16));
    }
}
