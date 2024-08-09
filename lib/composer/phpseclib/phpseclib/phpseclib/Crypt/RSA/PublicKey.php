<?php

/**
 * RSA Public Key
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\RSA;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\Common;
use phpseclib3\Crypt\Hash;
use phpseclib3\Crypt\Random;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\Formats\Keys\PSS;
use phpseclib3\Exception\UnsupportedAlgorithmException;
use phpseclib3\Exception\UnsupportedFormatException;
use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps\DigestInfo;
use phpseclib3\Math\BigInteger;

/**
 * Raw RSA Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
final class PublicKey extends RSA implements Common\PublicKey
{
    use Common\Traits\Fingerprint;

    /**
     * Exponentiate
     *
     * @param \phpseclib3\Math\BigInteger $x
     * @return \phpseclib3\Math\BigInteger
     */
    private function exponentiate(BigInteger $x)
    {
        return $x->modPow($this->exponent, $this->modulus);
    }

    /**
     * RSAVP1
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-5.2.2 RFC3447#section-5.2.2}.
     *
     * @param \phpseclib3\Math\BigInteger $s
     * @return bool|\phpseclib3\Math\BigInteger
     */
    private function rsavp1($s)
    {
        if ($s->compare(self::$zero) < 0 || $s->compare($this->modulus) > 0) {
            return false;
        }
        return $this->exponentiate($s);
    }

    /**
     * RSASSA-PKCS1-V1_5-VERIFY
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-8.2.2 RFC3447#section-8.2.2}.
     *
     * @param string $m
     * @param string $s
     * @throws \LengthException if the RSA modulus is too short
     * @return bool
     */
    private function rsassa_pkcs1_v1_5_verify($m, $s)
    {
        // Length checking

        if (strlen($s) != $this->k) {
            return false;
        }

        // RSA verification

        $s = $this->os2ip($s);
        $m2 = $this->rsavp1($s);
        if ($m2 === false) {
            return false;
        }
        $em = $this->i2osp($m2, $this->k);
        if ($em === false) {
            return false;
        }

        // EMSA-PKCS1-v1_5 encoding

        $exception = false;

        // If the encoding operation outputs "intended encoded message length too short," output "RSA modulus
        // too short" and stop.
        try {
            $em2 = $this->emsa_pkcs1_v1_5_encode($m, $this->k);
            $r1 = hash_equals($em, $em2);
        } catch (\LengthException $e) {
            $exception = true;
        }

        try {
            $em3 = $this->emsa_pkcs1_v1_5_encode_without_null($m, $this->k);
            $r2 = hash_equals($em, $em3);
        } catch (\LengthException $e) {
            $exception = true;
        } catch (UnsupportedAlgorithmException $e) {
            $r2 = false;
        }

        if ($exception) {
            throw new \LengthException('RSA modulus too short');
        }

        // Compare
        return $r1 || $r2;
    }

    /**
     * RSASSA-PKCS1-V1_5-VERIFY (relaxed matching)
     *
     * Per {@link http://tools.ietf.org/html/rfc3447#page-43 RFC3447#page-43} PKCS1 v1.5
     * specified the use BER encoding rather than DER encoding that PKCS1 v2.0 specified.
     * This means that under rare conditions you can have a perfectly valid v1.5 signature
     * that fails to validate with _rsassa_pkcs1_v1_5_verify(). PKCS1 v2.1 also recommends
     * that if you're going to validate these types of signatures you "should indicate
     * whether the underlying BER encoding is a DER encoding and hence whether the signature
     * is valid with respect to the specification given in [PKCS1 v2.0+]". so if you do
     * $rsa->getLastPadding() and get RSA::PADDING_RELAXED_PKCS1 back instead of
     * RSA::PADDING_PKCS1... that means BER encoding was used.
     *
     * @param string $m
     * @param string $s
     * @return bool
     */
    private function rsassa_pkcs1_v1_5_relaxed_verify($m, $s)
    {
        // Length checking

        if (strlen($s) != $this->k) {
            return false;
        }

        // RSA verification

        $s = $this->os2ip($s);
        $m2 = $this->rsavp1($s);
        if ($m2 === false) {
            return false;
        }
        $em = $this->i2osp($m2, $this->k);
        if ($em === false) {
            return false;
        }

        if (Strings::shift($em, 2) != "\0\1") {
            return false;
        }

        $em = ltrim($em, "\xFF");
        if (Strings::shift($em) != "\0") {
            return false;
        }

        $decoded = ASN1::decodeBER($em);
        if (!is_array($decoded) || empty($decoded[0]) || strlen($em) > $decoded[0]['length']) {
            return false;
        }

        static $oids;
        if (!isset($oids)) {
            $oids = [
                'md2' => '1.2.840.113549.2.2',
                'md4' => '1.2.840.113549.2.4', // from PKCS1 v1.5
                'md5' => '1.2.840.113549.2.5',
                'id-sha1' => '1.3.14.3.2.26',
                'id-sha256' => '2.16.840.1.101.3.4.2.1',
                'id-sha384' => '2.16.840.1.101.3.4.2.2',
                'id-sha512' => '2.16.840.1.101.3.4.2.3',
                // from PKCS1 v2.2
                'id-sha224' => '2.16.840.1.101.3.4.2.4',
                'id-sha512/224' => '2.16.840.1.101.3.4.2.5',
                'id-sha512/256' => '2.16.840.1.101.3.4.2.6',
            ];
            ASN1::loadOIDs($oids);
        }

        $decoded = ASN1::asn1map($decoded[0], DigestInfo::MAP);
        if (!isset($decoded) || $decoded === false) {
            return false;
        }

        if (!isset($oids[$decoded['digestAlgorithm']['algorithm']])) {
            return false;
        }

        if (isset($decoded['digestAlgorithm']['parameters']) && $decoded['digestAlgorithm']['parameters'] !== ['null' => '']) {
            return false;
        }

        $hash = $decoded['digestAlgorithm']['algorithm'];
        $hash = substr($hash, 0, 3) == 'id-' ?
            substr($hash, 3) :
            $hash;
        $hash = new Hash($hash);
        $em = $hash->hash($m);
        $em2 = $decoded['digest'];

        return hash_equals($em, $em2);
    }

    /**
     * EMSA-PSS-VERIFY
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-9.1.2 RFC3447#section-9.1.2}.
     *
     * @param string $m
     * @param string $em
     * @param int $emBits
     * @return string
     */
    private function emsa_pss_verify($m, $em, $emBits)
    {
        // if $m is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        $emLen = ($emBits + 7) >> 3; // ie. ceil($emBits / 8);
        $sLen = $this->sLen !== null ? $this->sLen : $this->hLen;

        $mHash = $this->hash->hash($m);
        if ($emLen < $this->hLen + $sLen + 2) {
            return false;
        }

        if ($em[strlen($em) - 1] != chr(0xBC)) {
            return false;
        }

        $maskedDB = substr($em, 0, -$this->hLen - 1);
        $h = substr($em, -$this->hLen - 1, $this->hLen);
        $temp = chr(0xFF << ($emBits & 7));
        if ((~$maskedDB[0] & $temp) != $temp) {
            return false;
        }
        $dbMask = $this->mgf1($h, $emLen - $this->hLen - 1);
        $db = $maskedDB ^ $dbMask;
        $db[0] = ~chr(0xFF << ($emBits & 7)) & $db[0];
        $temp = $emLen - $this->hLen - $sLen - 2;
        if (substr($db, 0, $temp) != str_repeat(chr(0), $temp) || ord($db[$temp]) != 1) {
            return false;
        }
        $salt = substr($db, $temp + 1); // should be $sLen long
        $m2 = "\0\0\0\0\0\0\0\0" . $mHash . $salt;
        $h2 = $this->hash->hash($m2);
        return hash_equals($h, $h2);
    }

    /**
     * RSASSA-PSS-VERIFY
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-8.1.2 RFC3447#section-8.1.2}.
     *
     * @param string $m
     * @param string $s
     * @return bool|string
     */
    private function rsassa_pss_verify($m, $s)
    {
        // Length checking

        if (strlen($s) != $this->k) {
            return false;
        }

        // RSA verification

        $modBits = strlen($this->modulus->toBits());

        $s2 = $this->os2ip($s);
        $m2 = $this->rsavp1($s2);
        $em = $this->i2osp($m2, $this->k);
        if ($em === false) {
            return false;
        }

        // EMSA-PSS verification

        return $this->emsa_pss_verify($m, $em, $modBits - 1);
    }

    /**
     * Verifies a signature
     *
     * @see self::sign()
     * @param string $message
     * @param string $signature
     * @return bool
     */
    public function verify($message, $signature)
    {
        switch ($this->signaturePadding) {
            case self::SIGNATURE_RELAXED_PKCS1:
                return $this->rsassa_pkcs1_v1_5_relaxed_verify($message, $signature);
            case self::SIGNATURE_PKCS1:
                return $this->rsassa_pkcs1_v1_5_verify($message, $signature);
            //case self::SIGNATURE_PSS:
            default:
                return $this->rsassa_pss_verify($message, $signature);
        }
    }

    /**
     * RSAES-PKCS1-V1_5-ENCRYPT
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-7.2.1 RFC3447#section-7.2.1}.
     *
     * @param string $m
     * @param bool $pkcs15_compat optional
     * @throws \LengthException if strlen($m) > $this->k - 11
     * @return bool|string
     */
    private function rsaes_pkcs1_v1_5_encrypt($m, $pkcs15_compat = false)
    {
        $mLen = strlen($m);

        // Length checking

        if ($mLen > $this->k - 11) {
            throw new \LengthException('Message too long');
        }

        // EME-PKCS1-v1_5 encoding

        $psLen = $this->k - $mLen - 3;
        $ps = '';
        while (strlen($ps) != $psLen) {
            $temp = Random::string($psLen - strlen($ps));
            $temp = str_replace("\x00", '', $temp);
            $ps .= $temp;
        }
        $type = 2;
        $em = chr(0) . chr($type) . $ps . chr(0) . $m;

        // RSA encryption
        $m = $this->os2ip($em);
        $c = $this->rsaep($m);
        $c = $this->i2osp($c, $this->k);

        // Output the ciphertext C

        return $c;
    }

    /**
     * RSAES-OAEP-ENCRYPT
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-7.1.1 RFC3447#section-7.1.1} and
     * {http://en.wikipedia.org/wiki/Optimal_Asymmetric_Encryption_Padding OAES}.
     *
     * @param string $m
     * @throws \LengthException if strlen($m) > $this->k - 2 * $this->hLen - 2
     * @return string
     */
    private function rsaes_oaep_encrypt($m)
    {
        $mLen = strlen($m);

        // Length checking

        // if $l is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        if ($mLen > $this->k - 2 * $this->hLen - 2) {
            throw new \LengthException('Message too long');
        }

        // EME-OAEP encoding

        $lHash = $this->hash->hash($this->label);
        $ps = str_repeat(chr(0), $this->k - $mLen - 2 * $this->hLen - 2);
        $db = $lHash . $ps . chr(1) . $m;
        $seed = Random::string($this->hLen);
        $dbMask = $this->mgf1($seed, $this->k - $this->hLen - 1);
        $maskedDB = $db ^ $dbMask;
        $seedMask = $this->mgf1($maskedDB, $this->hLen);
        $maskedSeed = $seed ^ $seedMask;
        $em = chr(0) . $maskedSeed . $maskedDB;

        // RSA encryption

        $m = $this->os2ip($em);
        $c = $this->rsaep($m);
        $c = $this->i2osp($c, $this->k);

        // Output the ciphertext C

        return $c;
    }

    /**
     * RSAEP
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-5.1.1 RFC3447#section-5.1.1}.
     *
     * @param \phpseclib3\Math\BigInteger $m
     * @return bool|\phpseclib3\Math\BigInteger
     */
    private function rsaep($m)
    {
        if ($m->compare(self::$zero) < 0 || $m->compare($this->modulus) > 0) {
            throw new \OutOfRangeException('Message representative out of range');
        }
        return $this->exponentiate($m);
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
            throw new \LengthException('Message too long');
        }

        $temp = $this->os2ip($m);
        $temp = $this->rsaep($temp);
        return  $this->i2osp($temp, $this->k);
    }

    /**
     * Encryption
     *
     * Both self::PADDING_OAEP and self::PADDING_PKCS1 both place limits on how long $plaintext can be.
     * If $plaintext exceeds those limits it will be broken up so that it does and the resultant ciphertext's will
     * be concatenated together.
     *
     * @see self::decrypt()
     * @param string $plaintext
     * @return bool|string
     * @throws \LengthException if the RSA modulus is too short
     */
    public function encrypt($plaintext)
    {
        switch ($this->encryptionPadding) {
            case self::ENCRYPTION_NONE:
                return $this->raw_encrypt($plaintext);
            case self::ENCRYPTION_PKCS1:
                return $this->rsaes_pkcs1_v1_5_encrypt($plaintext);
            //case self::ENCRYPTION_OAEP:
            default:
                return $this->rsaes_oaep_encrypt($plaintext);
        }
    }

    /**
     * Returns the public key
     *
     * The public key is only returned under two circumstances - if the private key had the public key embedded within it
     * or if the public key was set via setPublicKey().  If the currently loaded key is supposed to be the public key this
     * function won't return it since this library, for the most part, doesn't distinguish between public and private keys.
     *
     * @param string $type
     * @param array $options optional
     * @return mixed
     */
    public function toString($type, array $options = [])
    {
        $type = self::validatePlugin('Keys', $type, 'savePublicKey');

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

        return $type::savePublicKey($this->modulus, $this->publicExponent, $options);
    }

    /**
     * Converts a public key to a private key
     *
     * @return RSA
     */
    public function asPrivateKey()
    {
        $new = new PrivateKey();
        $new->exponent = $this->exponent;
        $new->modulus = $this->modulus;
        $new->k = $this->k;
        $new->format = $this->format;
        return $new
            ->withHash($this->hash->getHash())
            ->withMGFHash($this->mgfHash->getHash())
            ->withSaltLength($this->sLen)
            ->withLabel($this->label)
            ->withPadding($this->signaturePadding | $this->encryptionPadding);
    }
}
