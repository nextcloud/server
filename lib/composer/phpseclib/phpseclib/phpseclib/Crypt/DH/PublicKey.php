<?php

/**
 * DH Public Key
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\DH;

use phpseclib3\Crypt\Common;
use phpseclib3\Crypt\DH;

/**
 * DH Public Key
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
final class PublicKey extends DH
{
    use Common\Traits\Fingerprint;

    /**
     * Returns the public key
     *
     * @param string $type
     * @param array $options optional
     * @return string
     */
    public function toString($type, array $options = [])
    {
        $type = self::validatePlugin('Keys', $type, 'savePublicKey');

        return $type::savePublicKey($this->prime, $this->base, $this->publicKey, $options);
    }

    /**
     * Returns the public key as a BigInteger
     *
     * @return \phpseclib3\Math\BigInteger
     */
    public function toBigInteger()
    {
        return $this->publicKey;
    }
}
