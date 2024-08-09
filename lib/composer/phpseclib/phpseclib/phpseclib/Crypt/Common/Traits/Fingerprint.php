<?php

/**
 * Fingerprint Trait for Public Keys
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\Common\Traits;

use phpseclib3\Crypt\Hash;

/**
 * Fingerprint Trait for Private Keys
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
trait Fingerprint
{
    /**
     * Returns the public key's fingerprint
     *
     * The public key's fingerprint is returned, which is equivalent to running `ssh-keygen -lf rsa.pub`. If there is
     * no public key currently loaded, false is returned.
     * Example output (md5): "c1:b1:30:29:d7:b8:de:6c:97:77:10:d7:46:41:63:87" (as specified by RFC 4716)
     *
     * @param string $algorithm The hashing algorithm to be used. Valid options are 'md5' and 'sha256'. False is returned
     * for invalid values.
     * @return mixed
     */
    public function getFingerprint($algorithm = 'md5')
    {
        $type = self::validatePlugin('Keys', 'OpenSSH', 'savePublicKey');
        if ($type === false) {
            return false;
        }
        $key = $this->toString('OpenSSH', ['binary' => true]);
        if ($key === false) {
            return false;
        }
        switch ($algorithm) {
            case 'sha256':
                $hash = new Hash('sha256');
                $base = base64_encode($hash->hash($key));
                return substr($base, 0, strlen($base) - 1);
            case 'md5':
                return substr(chunk_split(md5($key), 2, ':'), 0, -1);
            default:
                return false;
        }
    }
}
