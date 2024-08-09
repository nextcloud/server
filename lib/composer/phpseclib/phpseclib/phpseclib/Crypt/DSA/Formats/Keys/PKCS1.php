<?php

/**
 * PKCS#1 Formatted DSA Key Handler
 *
 * PHP version 5
 *
 * Used by File/X509.php
 *
 * Processes keys with the following headers:
 *
 * -----BEGIN DSA PRIVATE KEY-----
 * -----BEGIN DSA PUBLIC KEY-----
 * -----BEGIN DSA PARAMETERS-----
 *
 * Analogous to ssh-keygen's pem format (as specified by -m)
 *
 * Also, technically, PKCS1 decribes RSA but I am not aware of a formal specification for DSA.
 * The DSA private key format seems to have been adapted from the RSA private key format so
 * we're just re-using that as the name.
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\DSA\Formats\Keys;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\Common\Formats\Keys\PKCS1 as Progenitor;
use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps;
use phpseclib3\Math\BigInteger;

/**
 * PKCS#1 Formatted DSA Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PKCS1 extends Progenitor
{
    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password optional
     * @return array
     */
    public static function load($key, $password = '')
    {
        $key = parent::load($key, $password);

        $decoded = ASN1::decodeBER($key);
        if (!$decoded) {
            throw new \RuntimeException('Unable to decode BER');
        }

        $key = ASN1::asn1map($decoded[0], Maps\DSAParams::MAP);
        if (is_array($key)) {
            return $key;
        }

        $key = ASN1::asn1map($decoded[0], Maps\DSAPrivateKey::MAP);
        if (is_array($key)) {
            return $key;
        }

        $key = ASN1::asn1map($decoded[0], Maps\DSAPublicKey::MAP);
        if (is_array($key)) {
            return $key;
        }

        throw new \RuntimeException('Unable to perform ASN1 mapping');
    }

    /**
     * Convert DSA parameters to the appropriate format
     *
     * @param \phpseclib3\Math\BigInteger $p
     * @param \phpseclib3\Math\BigInteger $q
     * @param \phpseclib3\Math\BigInteger $g
     * @return string
     */
    public static function saveParameters(BigInteger $p, BigInteger $q, BigInteger $g)
    {
        $key = [
            'p' => $p,
            'q' => $q,
            'g' => $g
        ];

        $key = ASN1::encodeDER($key, Maps\DSAParams::MAP);

        return "-----BEGIN DSA PARAMETERS-----\r\n" .
               chunk_split(Strings::base64_encode($key), 64) .
               "-----END DSA PARAMETERS-----\r\n";
    }

    /**
     * Convert a private key to the appropriate format.
     *
     * @param \phpseclib3\Math\BigInteger $p
     * @param \phpseclib3\Math\BigInteger $q
     * @param \phpseclib3\Math\BigInteger $g
     * @param \phpseclib3\Math\BigInteger $y
     * @param \phpseclib3\Math\BigInteger $x
     * @param string $password optional
     * @param array $options optional
     * @return string
     */
    public static function savePrivateKey(BigInteger $p, BigInteger $q, BigInteger $g, BigInteger $y, BigInteger $x, $password = '', array $options = [])
    {
        $key = [
            'version' => 0,
            'p' => $p,
            'q' => $q,
            'g' => $g,
            'y' => $y,
            'x' => $x
        ];

        $key = ASN1::encodeDER($key, Maps\DSAPrivateKey::MAP);

        return self::wrapPrivateKey($key, 'DSA', $password, $options);
    }

    /**
     * Convert a public key to the appropriate format
     *
     * @param \phpseclib3\Math\BigInteger $p
     * @param \phpseclib3\Math\BigInteger $q
     * @param \phpseclib3\Math\BigInteger $g
     * @param \phpseclib3\Math\BigInteger $y
     * @return string
     */
    public static function savePublicKey(BigInteger $p, BigInteger $q, BigInteger $g, BigInteger $y)
    {
        $key = ASN1::encodeDER($y, Maps\DSAPublicKey::MAP);

        return self::wrapPublicKey($key, 'DSA');
    }
}
