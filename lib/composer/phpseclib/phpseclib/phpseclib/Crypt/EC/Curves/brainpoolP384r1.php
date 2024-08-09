<?php

/**
 * brainpoolP384r1
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

class brainpoolP384r1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger(
            '8CB91E82A3386D280F5D6F7E50E641DF152F7109ED5456B412B1DA197FB71123ACD3A729901D1A7' .
            '1874700133107EC53',
            16
        ));
        $this->setCoefficients(
            new BigInteger(
                '7BC382C63D8C150C3C72080ACE05AFA0C2BEA28E4FB22787139165EFBA91F90F8AA5814A503' .
                'AD4EB04A8C7DD22CE2826',
                16
            ),
            new BigInteger(
                '4A8C7DD22CE28268B39B55416F0447C2FB77DE107DCD2A62E880EA53EEB62D57CB4390295DB' .
                'C9943AB78696FA504C11',
                16
            )
        );
        $this->setBasePoint(
            new BigInteger(
                '1D1C64F068CF45FFA2A63A81B7C13F6B8847A3E77EF14FE3DB7FCAFE0CBD10E8E826E03436D' .
                '646AAEF87B2E247D4AF1E',
                16
            ),
            new BigInteger(
                '8ABE1D7520F9C2A45CB1EB8E95CFD55262B70B29FEEC5864E19C054FF99129280E464621779' .
                '1811142820341263C5315',
                16
            )
        );
        $this->setOrder(new BigInteger(
            '8CB91E82A3386D280F5D6F7E50E641DF152F7109ED5456B31F166E6CAC0425A7CF3AB6AF6B7FC31' .
            '03B883202E9046565',
            16
        ));
    }
}
