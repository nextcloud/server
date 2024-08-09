<?php

/**
 * DSA Public Key
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\DSA;

use phpseclib3\Crypt\Common;
use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\DSA\Formats\Signature\ASN1 as ASN1Signature;

/**
 * DSA Public Key
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
final class PublicKey extends DSA implements Common\PublicKey
{
    use Common\Traits\Fingerprint;

    /**
     * Verify a signature
     *
     * @see self::verify()
     * @param string $message
     * @param string $signature
     * @return mixed
     */
    public function verify($message, $signature)
    {
        $format = $this->sigFormat;

        $params = $format::load($signature);
        if ($params === false || count($params) != 2) {
            return false;
        }
        extract($params);

        if (self::$engines['OpenSSL'] && in_array($this->hash->getHash(), openssl_get_md_methods())) {
            $sig = $format != 'ASN1' ? ASN1Signature::save($r, $s) : $signature;

            $result = openssl_verify($message, $sig, $this->toString('PKCS8'), $this->hash->getHash());

            if ($result != -1) {
                return (bool) $result;
            }
        }

        $q_1 = $this->q->subtract(self::$one);
        if (!$r->between(self::$one, $q_1) || !$s->between(self::$one, $q_1)) {
            return false;
        }

        $w = $s->modInverse($this->q);
        $h = $this->hash->hash($message);
        $h = $this->bits2int($h);
        list(, $u1) = $h->multiply($w)->divide($this->q);
        list(, $u2) = $r->multiply($w)->divide($this->q);
        $v1 = $this->g->powMod($u1, $this->p);
        $v2 = $this->y->powMod($u2, $this->p);
        list(, $v) = $v1->multiply($v2)->divide($this->p);
        list(, $v) = $v->divide($this->q);

        return $v->equals($r);
    }

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

        return $type::savePublicKey($this->p, $this->q, $this->g, $this->y, $options);
    }
}
