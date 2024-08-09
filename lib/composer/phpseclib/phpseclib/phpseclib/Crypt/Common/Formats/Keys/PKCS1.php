<?php

/**
 * PKCS1 Formatted Key Handler
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\Common\Formats\Keys;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\DES;
use phpseclib3\Crypt\Random;
use phpseclib3\Crypt\TripleDES;
use phpseclib3\Exception\UnsupportedAlgorithmException;
use phpseclib3\File\ASN1;

/**
 * PKCS1 Formatted Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PKCS1 extends PKCS
{
    /**
     * Default encryption algorithm
     *
     * @var string
     */
    private static $defaultEncryptionAlgorithm = 'AES-128-CBC';

    /**
     * Sets the default encryption algorithm
     *
     * @param string $algo
     */
    public static function setEncryptionAlgorithm($algo)
    {
        self::$defaultEncryptionAlgorithm = $algo;
    }

    /**
     * Returns the mode constant corresponding to the mode string
     *
     * @param string $mode
     * @return int
     * @throws \UnexpectedValueException if the block cipher mode is unsupported
     */
    private static function getEncryptionMode($mode)
    {
        switch ($mode) {
            case 'CBC':
            case 'ECB':
            case 'CFB':
            case 'OFB':
            case 'CTR':
                return $mode;
        }
        throw new \UnexpectedValueException('Unsupported block cipher mode of operation');
    }

    /**
     * Returns a cipher object corresponding to a string
     *
     * @param string $algo
     * @return string
     * @throws \UnexpectedValueException if the encryption algorithm is unsupported
     */
    private static function getEncryptionObject($algo)
    {
        $modes = '(CBC|ECB|CFB|OFB|CTR)';
        switch (true) {
            case preg_match("#^AES-(128|192|256)-$modes$#", $algo, $matches):
                $cipher = new AES(self::getEncryptionMode($matches[2]));
                $cipher->setKeyLength($matches[1]);
                return $cipher;
            case preg_match("#^DES-EDE3-$modes$#", $algo, $matches):
                return new TripleDES(self::getEncryptionMode($matches[1]));
            case preg_match("#^DES-$modes$#", $algo, $matches):
                return new DES(self::getEncryptionMode($matches[1]));
            default:
                throw new UnsupportedAlgorithmException($algo . ' is not a supported algorithm');
        }
    }

    /**
     * Generate a symmetric key for PKCS#1 keys
     *
     * @param string $password
     * @param string $iv
     * @param int $length
     * @return string
     */
    private static function generateSymmetricKey($password, $iv, $length)
    {
        $symkey = '';
        $iv = substr($iv, 0, 8);
        while (strlen($symkey) < $length) {
            $symkey .= md5($symkey . $password . $iv, true);
        }
        return substr($symkey, 0, $length);
    }

    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password optional
     * @return array
     */
    protected static function load($key, $password)
    {
        if (!Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . gettype($key));
        }

        /* Although PKCS#1 proposes a format that public and private keys can use, encrypting them is
           "outside the scope" of PKCS#1.  PKCS#1 then refers you to PKCS#12 and PKCS#15 if you're wanting to
           protect private keys, however, that's not what OpenSSL* does.  OpenSSL protects private keys by adding
           two new "fields" to the key - DEK-Info and Proc-Type.  These fields are discussed here:

           http://tools.ietf.org/html/rfc1421#section-4.6.1.1
           http://tools.ietf.org/html/rfc1421#section-4.6.1.3

           DES-EDE3-CBC as an algorithm, however, is not discussed anywhere, near as I can tell.
           DES-CBC and DES-EDE are discussed in RFC1423, however, DES-EDE3-CBC isn't, nor is its key derivation
           function.  As is, the definitive authority on this encoding scheme isn't the IETF but rather OpenSSL's
           own implementation.  ie. the implementation *is* the standard and any bugs that may exist in that
           implementation are part of the standard, as well.

           * OpenSSL is the de facto standard.  It's utilized by OpenSSH and other projects */
        if (preg_match('#DEK-Info: (.+),(.+)#', $key, $matches)) {
            $iv = Strings::hex2bin(trim($matches[2]));
            // remove the Proc-Type / DEK-Info sections as they're no longer needed
            $key = preg_replace('#^(?:Proc-Type|DEK-Info): .*#m', '', $key);
            $ciphertext = ASN1::extractBER($key);
            if ($ciphertext === false) {
                $ciphertext = $key;
            }
            $crypto = self::getEncryptionObject($matches[1]);
            $crypto->setKey(self::generateSymmetricKey($password, $iv, $crypto->getKeyLength() >> 3));
            $crypto->setIV($iv);
            $key = $crypto->decrypt($ciphertext);
        } else {
            if (self::$format != self::MODE_DER) {
                $decoded = ASN1::extractBER($key);
                if ($decoded !== false) {
                    $key = $decoded;
                } elseif (self::$format == self::MODE_PEM) {
                    throw new \UnexpectedValueException('Expected base64-encoded PEM format but was unable to decode base64 text');
                }
            }
        }

        return $key;
    }

    /**
     * Wrap a private key appropriately
     *
     * @param string $key
     * @param string $type
     * @param string $password
     * @param array $options optional
     * @return string
     */
    protected static function wrapPrivateKey($key, $type, $password, array $options = [])
    {
        if (empty($password) || !is_string($password)) {
            return "-----BEGIN $type PRIVATE KEY-----\r\n" .
                   chunk_split(Strings::base64_encode($key), 64) .
                   "-----END $type PRIVATE KEY-----";
        }

        $encryptionAlgorithm = isset($options['encryptionAlgorithm']) ? $options['encryptionAlgorithm'] : self::$defaultEncryptionAlgorithm;

        $cipher = self::getEncryptionObject($encryptionAlgorithm);
        $iv = Random::string($cipher->getBlockLength() >> 3);
        $cipher->setKey(self::generateSymmetricKey($password, $iv, $cipher->getKeyLength() >> 3));
        $cipher->setIV($iv);
        $iv = strtoupper(Strings::bin2hex($iv));
        return "-----BEGIN $type PRIVATE KEY-----\r\n" .
               "Proc-Type: 4,ENCRYPTED\r\n" .
               "DEK-Info: " . $encryptionAlgorithm . ",$iv\r\n" .
               "\r\n" .
               chunk_split(Strings::base64_encode($cipher->encrypt($key)), 64) .
               "-----END $type PRIVATE KEY-----";
    }

    /**
     * Wrap a public key appropriately
     *
     * @param string $key
     * @param string $type
     * @return string
     */
    protected static function wrapPublicKey($key, $type)
    {
        return "-----BEGIN $type PUBLIC KEY-----\r\n" .
               chunk_split(Strings::base64_encode($key), 64) .
               "-----END $type PUBLIC KEY-----";
    }
}
