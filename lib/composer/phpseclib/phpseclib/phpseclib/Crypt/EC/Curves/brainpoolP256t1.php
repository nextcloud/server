<?php

/**
 * brainpoolP256t1
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

class brainpoolP256t1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('A9FB57DBA1EEA9BC3E660A909D838D726E3BF623D52620282013481D1F6E5377', 16));
        $this->setCoefficients(
            new BigInteger('A9FB57DBA1EEA9BC3E660A909D838D726E3BF623D52620282013481D1F6E5374', 16), // eg. -3
            new BigInteger('662C61C430D84EA4FE66A7733D0B76B7BF93EBC4AF2F49256AE58101FEE92B04', 16)
        );
        $this->setBasePoint(
            new BigInteger('A3E8EB3CC1CFE7B7732213B23A656149AFA142C47AAFBC2B79A191562E1305F4', 16),
            new BigInteger('2D996C823439C56D7F7B22E14644417E69BCB6DE39D027001DABE8F35B25C9BE', 16)
        );
        $this->setOrder(new BigInteger('A9FB57DBA1EEA9BC3E660A909D838D718C397AA3B561A6F7901E0E82974856A7', 16));
    }
}
