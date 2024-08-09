<?php

/**
 * brainpoolP512t1
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

class brainpoolP512t1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger(
            'AADD9DB8DBE9C48B3FD4E6AE33C9FC07CB308DB3B3C9D20ED6639CCA703308717D4D9B009BC' .
            '66842AECDA12AE6A380E62881FF2F2D82C68528AA6056583A48F3',
            16
        ));
        $this->setCoefficients(
            new BigInteger(
                'AADD9DB8DBE9C48B3FD4E6AE33C9FC07CB308DB3B3C9D20ED6639CCA703308717D4D9B009BC' .
                '66842AECDA12AE6A380E62881FF2F2D82C68528AA6056583A48F0',
                16
            ), // eg. -3
            new BigInteger(
                '7CBBBCF9441CFAB76E1890E46884EAE321F70C0BCB4981527897504BEC3E36A62BCDFA23049' .
                '76540F6450085F2DAE145C22553B465763689180EA2571867423E',
                16
            )
        );
        $this->setBasePoint(
            new BigInteger(
                '640ECE5C12788717B9C1BA06CBC2A6FEBA85842458C56DDE9DB1758D39C0313D82BA51735CD' .
                'B3EA499AA77A7D6943A64F7A3F25FE26F06B51BAA2696FA9035DA',
                16
            ),
            new BigInteger(
                '5B534BD595F5AF0FA2C892376C84ACE1BB4E3019B71634C01131159CAE03CEE9D9932184BEE' .
                'F216BD71DF2DADF86A627306ECFF96DBB8BACE198B61E00F8B332',
                16
            )
        );
        $this->setOrder(new BigInteger(
            'AADD9DB8DBE9C48B3FD4E6AE33C9FC07CB308DB3B3C9D20ED6639CCA70330870553E5C414CA' .
            '92619418661197FAC10471DB1D381085DDADDB58796829CA90069',
            16
        ));
    }
}
