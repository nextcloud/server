<?php

/**
 * brainpoolP384t1
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

class brainpoolP384t1 extends Prime
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
                '8CB91E82A3386D280F5D6F7E50E641DF152F7109ED5456B412B1DA197FB71123ACD3A729901' .
                'D1A71874700133107EC50',
                16
            ), // eg. -3
            new BigInteger(
                '7F519EADA7BDA81BD826DBA647910F8C4B9346ED8CCDC64E4B1ABD11756DCE1D2074AA263B8' .
                '8805CED70355A33B471EE',
                16
            )
        );
        $this->setBasePoint(
            new BigInteger(
                '18DE98B02DB9A306F2AFCD7235F72A819B80AB12EBD653172476FECD462AABFFC4FF191B946' .
                'A5F54D8D0AA2F418808CC',
                16
            ),
            new BigInteger(
                '25AB056962D30651A114AFD2755AD336747F93475B7A1FCA3B88F2B6A208CCFE469408584DC' .
                '2B2912675BF5B9E582928',
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
