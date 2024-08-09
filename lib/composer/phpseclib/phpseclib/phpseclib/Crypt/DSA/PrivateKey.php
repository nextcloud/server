<?php

/**
 * DSA Private Key
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
use phpseclib3\Math\BigInteger;

/**
 * DSA Private Key
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
final class PrivateKey extends DSA implements Common\PrivateKey
{
    use Common\Traits\PasswordProtected;

    /**
     * DSA secret exponent x
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $x;

    /**
     * Returns the public key
     *
     * If you do "openssl rsa -in private.rsa -pubout -outform PEM" you get a PKCS8 formatted key
     * that contains a publicKeyAlgorithm AlgorithmIdentifier and a publicKey BIT STRING.
     * An AlgorithmIdentifier contains an OID and a parameters field. With RSA public keys this
     * parameters field is NULL. With DSA PKCS8 public keys it is not - it contains the p, q and g
     * variables. The publicKey BIT STRING contains, simply, the y variable. This can be verified
     * by getting a DSA PKCS8 public key:
     *
     * "openssl dsa -in private.dsa -pubout -outform PEM"
     *
     * ie. just swap out rsa with dsa in the rsa command above.
     *
     * A PKCS1 public key corresponds to the publicKey portion of the PKCS8 key. In the case of RSA
     * the publicKey portion /is/ the key. In the case of DSA it is not. You cannot verify a signature
     * without the parameters and the PKCS1 DSA public key format does not include the parameters.
     *
     * @see self::getPrivateKey()
     * @return mixed
     */
    public function getPublicKey()
    {
        $type = self::validatePlugin('Keys', 'PKCS8', 'savePublicKey');

        if (!isset($this->y)) {
            $this->y = $this->g->powMod($this->x, $this->p);
        }

        $key = $type::savePublicKey($this->p, $this->q, $this->g, $this->y);

        return DSA::loadFormat('PKCS8', $key)
            ->withHash($this->hash->getHash())
            ->withSignatureFormat($this->shortFormat);
    }

    /**
     * Create a signature
     *
     * @see self::verify()
     * @param string $message
     * @return mixed
     */
    public function sign($message)
    {
        $format = $this->sigFormat;

        if (self::$engines['OpenSSL'] && in_array($this->hash->getHash(), openssl_get_md_methods())) {
            $signature = '';
            $result = openssl_sign($message, $signature, $this->toString('PKCS8'), $this->hash->getHash());

            if ($result) {
                if ($this->shortFormat == 'ASN1') {
                    return $signature;
                }

                extract(ASN1Signature::load($signature));

                return $format::save($r, $s);
            }
        }

        $h = $this->hash->hash($message);
        $h = $this->bits2int($h);

        while (true) {
            $k = BigInteger::randomRange(self::$one, $this->q->subtract(self::$one));
            $r = $this->g->powMod($k, $this->p);
            list(, $r) = $r->divide($this->q);
            if ($r->equals(self::$zero)) {
                continue;
            }
            $kinv = $k->modInverse($this->q);
            $temp = $h->add($this->x->multiply($r));
            $temp = $kinv->multiply($temp);
            list(, $s) = $temp->divide($this->q);
            if (!$s->equals(self::$zero)) {
                break;
            }
        }

        // the following is an RFC6979 compliant implementation of deterministic DSA
        // it's unused because it's mainly intended for use when a good CSPRNG isn't
        // available. if phpseclib's CSPRNG isn't good then even key generation is
        // suspect
        /*
        $h1 = $this->hash->hash($message);
        $k = $this->computek($h1);
        $r = $this->g->powMod($k, $this->p);
        list(, $r) = $r->divide($this->q);
        $kinv = $k->modInverse($this->q);
        $h1 = $this->bits2int($h1);
        $temp = $h1->add($this->x->multiply($r));
        $temp = $kinv->multiply($temp);
        list(, $s) = $temp->divide($this->q);
        */

        return $format::save($r, $s);
    }

    /**
     * Returns the private key
     *
     * @param string $type
     * @param array $options optional
     * @return string
     */
    public function toString($type, array $options = [])
    {
        $type = self::validatePlugin('Keys', $type, 'savePrivateKey');

        if (!isset($this->y)) {
            $this->y = $this->g->powMod($this->x, $this->p);
        }

        return $type::savePrivateKey($this->p, $this->q, $this->g, $this->y, $this->x, $this->password, $options);
    }
}
