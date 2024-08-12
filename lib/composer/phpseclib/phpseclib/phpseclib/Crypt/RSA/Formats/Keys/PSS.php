<?php

/**
 * PKCS#8 Formatted RSA-PSS Key Handler
 *
 * PHP version 5
 *
 * Used by PHP's openssl_public_encrypt() and openssl's rsautl (when -pubin is set)
 *
 * Processes keys with the following headers:
 *
 * -----BEGIN ENCRYPTED PRIVATE KEY-----
 * -----BEGIN PRIVATE KEY-----
 * -----BEGIN PUBLIC KEY-----
 *
 * Analogous to "openssl genpkey -algorithm rsa-pss".
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\RSA\Formats\Keys;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\Common\Formats\Keys\PKCS8 as Progenitor;
use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps;
use phpseclib3\Math\BigInteger;

/**
 * PKCS#8 Formatted RSA-PSS Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PSS extends Progenitor
{
    /**
     * OID Name
     *
     * @var string
     */
    const OID_NAME = 'id-RSASSA-PSS';

    /**
     * OID Value
     *
     * @var string
     */
    const OID_VALUE = '1.2.840.113549.1.1.10';

    /**
     * OIDs loaded
     *
     * @var bool
     */
    private static $oidsLoaded = false;

    /**
     * Child OIDs loaded
     *
     * @var bool
     */
    protected static $childOIDsLoaded = false;

    /**
     * Initialize static variables
     */
    private static function initialize_static_variables()
    {
        if (!self::$oidsLoaded) {
            ASN1::loadOIDs([
                'md2' => '1.2.840.113549.2.2',
                'md4' => '1.2.840.113549.2.4',
                'md5' => '1.2.840.113549.2.5',
                'id-sha1' => '1.3.14.3.2.26',
                'id-sha256' => '2.16.840.1.101.3.4.2.1',
                'id-sha384' => '2.16.840.1.101.3.4.2.2',
                'id-sha512' => '2.16.840.1.101.3.4.2.3',
                'id-sha224' => '2.16.840.1.101.3.4.2.4',
                'id-sha512/224' => '2.16.840.1.101.3.4.2.5',
                'id-sha512/256' => '2.16.840.1.101.3.4.2.6',

                'id-mgf1' => '1.2.840.113549.1.1.8'
            ]);
            self::$oidsLoaded = true;
        }
    }

    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password optional
     * @return array
     */
    public static function load($key, $password = '')
    {
        self::initialize_static_variables();

        if (!Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . gettype($key));
        }

        $components = ['isPublicKey' => strpos($key, 'PUBLIC') !== false];

        $key = parent::load($key, $password);

        $type = isset($key['privateKey']) ? 'private' : 'public';

        $result = $components + PKCS1::load($key[$type . 'Key']);

        if (isset($key[$type . 'KeyAlgorithm']['parameters'])) {
            $decoded = ASN1::decodeBER($key[$type . 'KeyAlgorithm']['parameters']);
            if ($decoded === false) {
                throw new \UnexpectedValueException('Unable to decode parameters');
            }
            $params = ASN1::asn1map($decoded[0], Maps\RSASSA_PSS_params::MAP);
        } else {
            $params = [];
        }

        if (isset($params['maskGenAlgorithm']['parameters'])) {
            $decoded = ASN1::decodeBER($params['maskGenAlgorithm']['parameters']);
            if ($decoded === false) {
                throw new \UnexpectedValueException('Unable to decode parameters');
            }
            $params['maskGenAlgorithm']['parameters'] = ASN1::asn1map($decoded[0], Maps\HashAlgorithm::MAP);
        } else {
            $params['maskGenAlgorithm'] = [
                'algorithm' => 'id-mgf1',
                'parameters' => ['algorithm' => 'id-sha1']
            ];
        }

        if (!isset($params['hashAlgorithm']['algorithm'])) {
            $params['hashAlgorithm']['algorithm'] = 'id-sha1';
        }

        $result['hash'] = str_replace('id-', '', $params['hashAlgorithm']['algorithm']);
        $result['MGFHash'] = str_replace('id-', '', $params['maskGenAlgorithm']['parameters']['algorithm']);
        if (isset($params['saltLength'])) {
            $result['saltLength'] = (int) $params['saltLength']->toString();
        }

        if (isset($key['meta'])) {
            $result['meta'] = $key['meta'];
        }

        return $result;
    }

    /**
     * Convert a private key to the appropriate format.
     *
     * @param \phpseclib3\Math\BigInteger $n
     * @param \phpseclib3\Math\BigInteger $e
     * @param \phpseclib3\Math\BigInteger $d
     * @param array $primes
     * @param array $exponents
     * @param array $coefficients
     * @param string $password optional
     * @param array $options optional
     * @return string
     */
    public static function savePrivateKey(BigInteger $n, BigInteger $e, BigInteger $d, array $primes, array $exponents, array $coefficients, $password = '', array $options = [])
    {
        self::initialize_static_variables();

        $key = PKCS1::savePrivateKey($n, $e, $d, $primes, $exponents, $coefficients);
        $key = ASN1::extractBER($key);
        $params = self::savePSSParams($options);
        return self::wrapPrivateKey($key, [], $params, $password, null, '', $options);
    }

    /**
     * Convert a public key to the appropriate format
     *
     * @param \phpseclib3\Math\BigInteger $n
     * @param \phpseclib3\Math\BigInteger $e
     * @param array $options optional
     * @return string
     */
    public static function savePublicKey(BigInteger $n, BigInteger $e, array $options = [])
    {
        self::initialize_static_variables();

        $key = PKCS1::savePublicKey($n, $e);
        $key = ASN1::extractBER($key);
        $params = self::savePSSParams($options);
        return self::wrapPublicKey($key, $params);
    }

    /**
     * Encodes PSS parameters
     *
     * @param array $options
     * @return string
     */
    public static function savePSSParams(array $options)
    {
        /*
         The trailerField field is an integer.  It provides
         compatibility with IEEE Std 1363a-2004 [P1363A].  The value
         MUST be 1, which represents the trailer field with hexadecimal
         value 0xBC.  Other trailer fields, including the trailer field
         composed of HashID concatenated with 0xCC that is specified in
         IEEE Std 1363a, are not supported.  Implementations that
         perform signature generation MUST omit the trailerField field,
         indicating that the default trailer field value was used.
         Implementations that perform signature validation MUST
         recognize both a present trailerField field with value 1 and an
         absent trailerField field.

         source: https://tools.ietf.org/html/rfc4055#page-9
        */
        $params = [
            'trailerField' => new BigInteger(1)
        ];
        if (isset($options['hash'])) {
            $params['hashAlgorithm']['algorithm'] = 'id-' . $options['hash'];
        }
        if (isset($options['MGFHash'])) {
            $temp = ['algorithm' => 'id-' . $options['MGFHash']];
            $temp = ASN1::encodeDER($temp, Maps\HashAlgorithm::MAP);
            $params['maskGenAlgorithm'] = [
                'algorithm' => 'id-mgf1',
                'parameters' => new ASN1\Element($temp)
            ];
        }
        if (isset($options['saltLength'])) {
            $params['saltLength'] = new BigInteger($options['saltLength']);
        }

        return new ASN1\Element(ASN1::encodeDER($params, Maps\RSASSA_PSS_params::MAP));
    }
}
