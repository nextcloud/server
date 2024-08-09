<?php

/**
 * RSA Private Key
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\RSA;

use phpseclib3\Crypt\Common;
use phpseclib3\Crypt\Random;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\Formats\Keys\PSS;
use phpseclib3\Exception\UnsupportedFormatException;
use phpseclib3\Math\BigInteger;

/**
 * Raw RSA Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
final class PrivateKey extends RSA implements Common\PrivateKey
{
    use Common\Traits\PasswordProtected;

    /**
     * Primes for Chinese Remainder Theorem (ie. p and q)
     *
     * @var array
     */
    protected $primes;

    /**
     * Exponents for Chinese Remainder Theorem (ie. dP and dQ)
     *
     * @var array
     */
    protected $exponents;

    /**
     * Coefficients for Chinese Remainder Theorem (ie. qInv)
     *
     * @var array
     */
    protected $coefficients;

    /**
     * Private Exponent
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $privateExponent;

    /**
     * RSADP
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-5.1.2 RFC3447#section-5.1.2}.
     *
     * @return bool|\phpseclib3\Math\BigInteger
     */
    private function rsadp(BigInteger $c)
    {
        if ($c->compare(self::$zero) < 0 || $c->compare($this->modulus) > 0) {
            throw new \OutOfRangeException('Ciphertext representative out of range');
        }
        return $this->exponentiate($c);
    }

    /**
     * RSASP1
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-5.2.1 RFC3447#section-5.2.1}.
     *
     * @return bool|\phpseclib3\Math\BigInteger
     */
    private function rsasp1(BigInteger $m)
    {
        if ($m->compare(self::$zero) < 0 || $m->compare($this->modulus) > 0) {
            throw new \OutOfRangeException('Signature representative out of range');
        }
        return $this->exponentiate($m);
    }

    /**
     * Exponentiate
     *
     * @param \phpseclib3\Math\BigInteger $x
     * @return \phpseclib3\Math\BigInteger
     */
    protected function exponentiate(BigInteger $x)
    {
        switch (true) {
            case empty($this->primes):
            case $this->primes[1]->equals(self::$zero):
            case empty($this->coefficients):
            case $this->coefficients[2]->equals(self::$zero):
            case empty($this->exponents):
            case $this->exponents[1]->equals(self::$zero):
                return $x->modPow($this->exponent, $this->modulus);
        }

        $num_primes = count($this->primes);

        if (!static::$enableBlinding) {
            $m_i = [
                1 => $x->modPow($this->exponents[1], $this->primes[1]),
                2 => $x->modPow($this->exponents[2], $this->primes[2])
            ];
            $h = $m_i[1]->subtract($m_i[2]);
            $h = $h->multiply($this->coefficients[2]);
            list(, $h) = $h->divide($this->primes[1]);
            $m = $m_i[2]->add($h->multiply($this->primes[2]));

            $r = $this->primes[1];
            for ($i = 3; $i <= $num_primes; $i++) {
                $m_i = $x->modPow($this->exponents[$i], $this->primes[$i]);

                $r = $r->multiply($this->primes[$i - 1]);

                $h = $m_i->subtract($m);
                $h = $h->multiply($this->coefficients[$i]);
                list(, $h) = $h->divide($this->primes[$i]);

                $m = $m->add($r->multiply($h));
            }
        } else {
            $smallest = $this->primes[1];
            for ($i = 2; $i <= $num_primes; $i++) {
                if ($smallest->compare($this->primes[$i]) > 0) {
                    $smallest = $this->primes[$i];
                }
            }

            $r = BigInteger::randomRange(self::$one, $smallest->subtract(self::$one));

            $m_i = [
                1 => $this->blind($x, $r, 1),
                2 => $this->blind($x, $r, 2)
            ];
            $h = $m_i[1]->subtract($m_i[2]);
            $h = $h->multiply($this->coefficients[2]);
            list(, $h) = $h->divide($this->primes[1]);
            $m = $m_i[2]->add($h->multiply($this->primes[2]));

            $r = $this->primes[1];
            for ($i = 3; $i <= $num_primes; $i++) {
                $m_i = $this->blind($x, $r, $i);

                $r = $r->multiply($this->primes[$i - 1]);

                $h = $m_i->subtract($m);
                $h = $h->multiply($this->coefficients[$i]);
                list(, $h) = $h->divide($this->primes[$i]);

                $m = $m->add($r->multiply($h));
            }
        }

        return $m;
    }

    /**
     * Performs RSA Blinding
     *
     * Protects against timing attacks by employing RSA Blinding.
     * Returns $x->modPow($this->exponents[$i], $this->primes[$i])
     *
     * @param \phpseclib3\Math\BigInteger $x
     * @param \phpseclib3\Math\BigInteger $r
     * @param int $i
     * @return \phpseclib3\Math\BigInteger
     */
    private function blind(BigInteger $x, BigInteger $r, $i)
    {
        $x = $x->multiply($r->modPow($this->publicExponent, $this->primes[$i]));
        $x = $x->modPow($this->exponents[$i], $this->primes[$i]);

        $r = $r->modInverse($this->primes[$i]);
        $x = $x->multiply($r);
        list(, $x) = $x->divide($this->primes[$i]);

        return $x;
    }

    /**
     * EMSA-PSS-ENCODE
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-9.1.1 RFC3447#section-9.1.1}.
     *
     * @return string
     * @param string $m
     * @throws \RuntimeException on encoding error
     * @param int $emBits
     */
    private function emsa_pss_encode($m, $emBits)
    {
        // if $m is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        $emLen = ($emBits + 1) >> 3; // ie. ceil($emBits / 8)
        $sLen = $this->sLen !== null ? $this->sLen : $this->hLen;

        $mHash = $this->hash->hash($m);
        if ($emLen < $this->hLen + $sLen + 2) {
            throw new \LengthException('RSA modulus too short');
        }

        $salt = Random::string($sLen);
        $m2 = "\0\0\0\0\0\0\0\0" . $mHash . $salt;
        $h = $this->hash->hash($m2);
        $ps = str_repeat(chr(0), $emLen - $sLen - $this->hLen - 2);
        $db = $ps . chr(1) . $salt;
        $dbMask = $this->mgf1($h, $emLen - $this->hLen - 1); // ie. stlren($db)
        $maskedDB = $db ^ $dbMask;
        $maskedDB[0] = ~chr(0xFF << ($emBits & 7)) & $maskedDB[0];
        $em = $maskedDB . $h . chr(0xBC);

        return $em;
    }

    /**
     * RSASSA-PSS-SIGN
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-8.1.1 RFC3447#section-8.1.1}.
     *
     * @param string $m
     * @return bool|string
     */
    private function rsassa_pss_sign($m)
    {
        // EMSA-PSS encoding

        $em = $this->emsa_pss_encode($m, 8 * $this->k - 1);

        // RSA signature

        $m = $this->os2ip($em);
        $s = $this->rsasp1($m);
        $s = $this->i2osp($s, $this->k);

        // Output the signature S

        return $s;
    }

    /**
     * RSASSA-PKCS1-V1_5-SIGN
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-8.2.1 RFC3447#section-8.2.1}.
     *
     * @param string $m
     * @throws \LengthException if the RSA modulus is too short
     * @return bool|string
     */
    private function rsassa_pkcs1_v1_5_sign($m)
    {
        // EMSA-PKCS1-v1_5 encoding

        // If the encoding operation outputs "intended encoded message length too short," output "RSA modulus
        // too short" and stop.
        try {
            $em = $this->emsa_pkcs1_v1_5_encode($m, $this->k);
        } catch (\LengthException $e) {
            throw new \LengthException('RSA modulus too short');
        }

        // RSA signature

        $m = $this->os2ip($em);
        $s = $this->rsasp1($m);
        $s = $this->i2osp($s, $this->k);

        // Output the signature S

        return $s;
    }

    /**
     * Create a signature
     *
     * @see self::verify()
     * @param string $message
     * @return string
     */
    public function sign($message)
    {
        switch ($this->signaturePadding) {
            case self::SIGNATURE_PKCS1:
            case self::SIGNATURE_RELAXED_PKCS1:
                return $this->rsassa_pkcs1_v1_5_sign($message);
            //case self::SIGNATURE_PSS:
            default:
                return $this->rsassa_pss_sign($message);
        }
    }

    /**
     * RSAES-PKCS1-V1_5-DECRYPT
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-7.2.2 RFC3447#section-7.2.2}.
     *
     * @param string $c
     * @return bool|string
     */
    private function rsaes_pkcs1_v1_5_decrypt($c)
    {
        // Length checking

        if (strlen($c) != $this->k) { // or if k < 11
            throw new \LengthException('Ciphertext representative too long');
        }

        // RSA decryption

        $c = $this->os2ip($c);
        $m = $this->rsadp($c);
        $em = $this->i2osp($m, $this->k);

        // EME-PKCS1-v1_5 decoding

        if (ord($em[0]) != 0 || ord($em[1]) > 2) {
            throw new \RuntimeException('Decryption error');
        }

        $ps = substr($em, 2, strpos($em, chr(0), 2) - 2);
        $m = substr($em, strlen($ps) + 3);

        if (strlen($ps) < 8) {
            throw new \RuntimeException('Decryption error');
        }

        // Output M

        return $m;
    }

    /**
     * RSAES-OAEP-DECRYPT
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-7.1.2 RFC3447#section-7.1.2}.  The fact that the error
     * messages aren't distinguishable from one another hinders debugging, but, to quote from RFC3447#section-7.1.2:
     *
     *    Note.  Care must be taken to ensure that an opponent cannot
     *    distinguish the different error conditions in Step 3.g, whether by
     *    error message or timing, or, more generally, learn partial
     *    information about the encoded message EM.  Otherwise an opponent may
     *    be able to obtain useful information about the decryption of the
     *    ciphertext C, leading to a chosen-ciphertext attack such as the one
     *    observed by Manger [36].
     *
     * @param string $c
     * @return bool|string
     */
    private function rsaes_oaep_decrypt($c)
    {
        // Length checking

        // if $l is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        if (strlen($c) != $this->k || $this->k < 2 * $this->hLen + 2) {
            throw new \LengthException('Ciphertext representative too long');
        }

        // RSA decryption

        $c = $this->os2ip($c);
        $m = $this->rsadp($c);
        $em = $this->i2osp($m, $this->k);

        // EME-OAEP decoding

        $lHash = $this->hash->hash($this->label);
        $y = ord($em[0]);
        $maskedSeed = substr($em, 1, $this->hLen);
        $maskedDB = substr($em, $this->hLen + 1);
        $seedMask = $this->mgf1($maskedDB, $this->hLen);
        $seed = $maskedSeed ^ $seedMask;
        $dbMask = $this->mgf1($seed, $this->k - $this->hLen - 1);
        $db = $maskedDB ^ $dbMask;
        $lHash2 = substr($db, 0, $this->hLen);
        $m = substr($db, $this->hLen);
        $hashesMatch = hash_equals($lHash, $lHash2);
        $leadingZeros = 1;
        $patternMatch = 0;
        $offset = 0;
        for ($i = 0; $i < strlen($m); $i++) {
            $patternMatch |= $leadingZeros & ($m[$i] === "\1");
            $leadingZeros &= $m[$i] === "\0";
            $offset += $patternMatch ? 0 : 1;
        }

        // we do | instead of || to avoid https://en.wikipedia.org/wiki/Short-circuit_evaluation
        // to protect against timing attacks
        if (!$hashesMatch | !$patternMatch) {
            throw new \RuntimeException('Decryption error');
        }

        // Output the message M

        return substr($m, $offset + 1);
    }

    /**
     * Raw Encryption / Decryption
     *
     * Doesn't use padding and is not recommended.
     *
     * @param string $m
     * @return bool|string
     * @throws \LengthException if strlen($m) > $this->k
     */
    private function raw_encrypt($m)
    {
        if (strlen($m) > $this->k) {
            throw new \LengthException('Ciphertext representative too long');
        }

        $temp = $this->os2ip($m);
        $temp = $this->rsadp($temp);
        return  $this->i2osp($temp, $this->k);
    }

    /**
     * Decryption
     *
     * @see self::encrypt()
     * @param string $ciphertext
     * @return bool|string
     */
    public function decrypt($ciphertext)
    {
        switch ($this->encryptionPadding) {
            case self::ENCRYPTION_NONE:
                return $this->raw_encrypt($ciphertext);
            case self::ENCRYPTION_PKCS1:
                return $this->rsaes_pkcs1_v1_5_decrypt($ciphertext);
            //case self::ENCRYPTION_OAEP:
            default:
                return $this->rsaes_oaep_decrypt($ciphertext);
        }
    }

    /**
     * Returns the public key
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        $type = self::validatePlugin('Keys', 'PKCS8', 'savePublicKey');
        if (empty($this->modulus) || empty($this->publicExponent)) {
            throw new \RuntimeException('Public key components not found');
        }

        $key = $type::savePublicKey($this->modulus, $this->publicExponent);
        return RSA::loadFormat('PKCS8', $key)
            ->withHash($this->hash->getHash())
            ->withMGFHash($this->mgfHash->getHash())
            ->withSaltLength($this->sLen)
            ->withLabel($this->label)
            ->withPadding($this->signaturePadding | $this->encryptionPadding);
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
        $type = self::validatePlugin(
            'Keys',
            $type,
            empty($this->primes) ? 'savePublicKey' : 'savePrivateKey'
        );

        if ($type == PSS::class) {
            if ($this->signaturePadding == self::SIGNATURE_PSS) {
                $options += [
                    'hash' => $this->hash->getHash(),
                    'MGFHash' => $this->mgfHash->getHash(),
                    'saltLength' => $this->getSaltLength()
                ];
            } else {
                throw new UnsupportedFormatException('The PSS format can only be used when the signature method has been explicitly set to PSS');
            }
        }

        if (empty($this->primes)) {
            return $type::savePublicKey($this->modulus, $this->exponent, $options);
        }

        return $type::savePrivateKey($this->modulus, $this->publicExponent, $this->exponent, $this->primes, $this->exponents, $this->coefficients, $this->password, $options);

        /*
        $key = $type::savePrivateKey($this->modulus, $this->publicExponent, $this->exponent, $this->primes, $this->exponents, $this->coefficients, $this->password, $options);
        if ($key !== false || count($this->primes) == 2) {
            return $key;
        }

        $nSize = $this->getSize() >> 1;

        $primes = [1 => clone self::$one, clone self::$one];
        $i = 1;
        foreach ($this->primes as $prime) {
            $primes[$i] = $primes[$i]->multiply($prime);
            if ($primes[$i]->getLength() >= $nSize) {
                $i++;
            }
        }

        $exponents = [];
        $coefficients = [2 => $primes[2]->modInverse($primes[1])];

        foreach ($primes as $i => $prime) {
            $temp = $prime->subtract(self::$one);
            $exponents[$i] = $this->modulus->modInverse($temp);
        }

        return $type::savePrivateKey($this->modulus, $this->publicExponent, $this->exponent, $primes, $exponents, $coefficients, $this->password, $options);
        */
    }
}
