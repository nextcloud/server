<?php

/**
 * brainpoolP320r1
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

class brainpoolP320r1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('D35E472036BC4FB7E13C785ED201E065F98FCFA6F6F40DEF4F9' .
                                        '2B9EC7893EC28FCD412B1F1B32E27', 16));
        $this->setCoefficients(
            new BigInteger('3EE30B568FBAB0F883CCEBD46D3F3BB8A2A73513F5EB79DA66190EB085FFA9F4' .
                           '92F375A97D860EB4', 16),
            new BigInteger('520883949DFDBC42D3AD198640688A6FE13F41349554B49ACC31DCCD88453981' .
                           '6F5EB4AC8FB1F1A6', 16)
        );
        $this->setBasePoint(
            new BigInteger('43BD7E9AFB53D8B85289BCC48EE5BFE6F20137D10A087EB6E7871E2A10A599C7' .
                           '10AF8D0D39E20611', 16),
            new BigInteger('14FDD05545EC1CC8AB4093247F77275E0743FFED117182EAA9C77877AAAC6AC7' .
                           'D35245D1692E8EE1', 16)
        );
        $this->setOrder(new BigInteger('D35E472036BC4FB7E13C785ED201E065F98FCFA5B68F12A32D4' .
                                       '82EC7EE8658E98691555B44C59311', 16));
    }
}
