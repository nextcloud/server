<?php

/**
 * brainpoolP192r1
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

class brainpoolP192r1 extends Prime
{
    public function __construct()
    {
        $this->setModulo(new BigInteger('C302F41D932A36CDA7A3463093D18DB78FCE476DE1A86297', 16));
        $this->setCoefficients(
            new BigInteger('6A91174076B1E0E19C39C031FE8685C1CAE040E5C69A28EF', 16),
            new BigInteger('469A28EF7C28CCA3DC721D044F4496BCCA7EF4146FBF25C9', 16)
        );
        $this->setBasePoint(
            new BigInteger('C0A0647EAAB6A48753B033C56CB0F0900A2F5C4853375FD6', 16),
            new BigInteger('14B690866ABD5BB88B5F4828C1490002E6773FA2FA299B8F', 16)
        );
        $this->setOrder(new BigInteger('C302F41D932A36CDA7A3462F9E9E916B5BE8F1029AC4ACC1', 16));
    }
}
