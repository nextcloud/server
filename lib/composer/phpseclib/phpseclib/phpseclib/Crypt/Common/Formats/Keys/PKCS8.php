<?php

/**
 * PKCS#8 Formatted Key Handler
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
 * Analogous to ssh-keygen's pkcs8 format (as specified by -m). Although PKCS8
 * is specific to private keys it's basically creating a DER-encoded wrapper
 * for keys. This just extends that same concept to public keys (much like ssh-keygen)
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
use phpseclib3\Crypt\RC2;
use phpseclib3\Crypt\RC4;
use phpseclib3\Crypt\TripleDES;
use phpseclib3\Exception\InsufficientSetupException;
use phpseclib3\Exception\UnsupportedAlgorithmException;
use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps;

/**
 * PKCS#8 Formatted Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PKCS8 extends PKCS
{
    /**
     * Default encryption algorithm
     *
     * @var string
     */
    private static $defaultEncryptionAlgorithm = 'id-PBES2';

    /**
     * Default encryption scheme
     *
     * Only used when defaultEncryptionAlgorithm is id-PBES2
     *
     * @var string
     */
    private static $defaultEncryptionScheme = 'aes128-CBC-PAD';

    /**
     * Default PRF
     *
     * Only used when defaultEncryptionAlgorithm is id-PBES2
     *
     * @var string
     */
    private static $defaultPRF = 'id-hmacWithSHA256';

    /**
     * Default Iteration Count
     *
     * @var int
     */
    private static $defaultIterationCount = 2048;

    /**
     * OIDs loaded
     *
     * @var bool
     */
    private static $oidsLoaded = false;

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
     * Sets the default encryption algorithm for PBES2
     *
     * @param string $algo
     */
    public static function setEncryptionScheme($algo)
    {
        self::$defaultEncryptionScheme = $algo;
    }

    /**
     * Sets the iteration count
     *
     * @param int $count
     */
    public static function setIterationCount($count)
    {
        self::$defaultIterationCount = $count;
    }

    /**
     * Sets the PRF for PBES2
     *
     * @param string $algo
     */
    public static function setPRF($algo)
    {
        self::$defaultPRF = $algo;
    }

    /**
     * Returns a SymmetricKey object based on a PBES1 $algo
     *
     * @return \phpseclib3\Crypt\Common\SymmetricKey
     * @param string $algo
     */
    private static function getPBES1EncryptionObject($algo)
    {
        $algo = preg_match('#^pbeWith(?:MD2|MD5|SHA1|SHA)And(.*?)-CBC$#', $algo, $matches) ?
            $matches[1] :
            substr($algo, 13); // strlen('pbeWithSHAAnd') == 13

        switch ($algo) {
            case 'DES':
                $cipher = new DES('cbc');
                break;
            case 'RC2':
                $cipher = new RC2('cbc');
                $cipher->setKeyLength(64);
                break;
            case '3-KeyTripleDES':
                $cipher = new TripleDES('cbc');
                break;
            case '2-KeyTripleDES':
                $cipher = new TripleDES('cbc');
                $cipher->setKeyLength(128);
                break;
            case '128BitRC2':
                $cipher = new RC2('cbc');
                $cipher->setKeyLength(128);
                break;
            case '40BitRC2':
                $cipher = new RC2('cbc');
                $cipher->setKeyLength(40);
                break;
            case '128BitRC4':
                $cipher = new RC4();
                $cipher->setKeyLength(128);
                break;
            case '40BitRC4':
                $cipher = new RC4();
                $cipher->setKeyLength(40);
                break;
            default:
                throw new UnsupportedAlgorithmException("$algo is not a supported algorithm");
        }

        return $cipher;
    }

    /**
     * Returns a hash based on a PBES1 $algo
     *
     * @return string
     * @param string $algo
     */
    private static function getPBES1Hash($algo)
    {
        if (preg_match('#^pbeWith(MD2|MD5|SHA1|SHA)And.*?-CBC$#', $algo, $matches)) {
            return $matches[1] == 'SHA' ? 'sha1' : $matches[1];
        }

        return 'sha1';
    }

    /**
     * Returns a KDF baesd on a PBES1 $algo
     *
     * @return string
     * @param string $algo
     */
    private static function getPBES1KDF($algo)
    {
        switch ($algo) {
            case 'pbeWithMD2AndDES-CBC':
            case 'pbeWithMD2AndRC2-CBC':
            case 'pbeWithMD5AndDES-CBC':
            case 'pbeWithMD5AndRC2-CBC':
            case 'pbeWithSHA1AndDES-CBC':
            case 'pbeWithSHA1AndRC2-CBC':
                return 'pbkdf1';
        }

        return 'pkcs12';
    }

    /**
     * Returns a SymmetricKey object baesd on a PBES2 $algo
     *
     * @return SymmetricKey
     * @param string $algo
     */
    private static function getPBES2EncryptionObject($algo)
    {
        switch ($algo) {
            case 'desCBC':
                $cipher = new DES('cbc');
                break;
            case 'des-EDE3-CBC':
                $cipher = new TripleDES('cbc');
                break;
            case 'rc2CBC':
                $cipher = new RC2('cbc');
                // in theory this can be changed
                $cipher->setKeyLength(128);
                break;
            case 'rc5-CBC-PAD':
                throw new UnsupportedAlgorithmException('rc5-CBC-PAD is not supported for PBES2 PKCS#8 keys');
            case 'aes128-CBC-PAD':
            case 'aes192-CBC-PAD':
            case 'aes256-CBC-PAD':
                $cipher = new AES('cbc');
                $cipher->setKeyLength(substr($algo, 3, 3));
                break;
            default:
                throw new UnsupportedAlgorithmException("$algo is not supported");
        }

        return $cipher;
    }

    /**
     * Initialize static variables
     *
     */
    private static function initialize_static_variables()
    {
        if (!isset(static::$childOIDsLoaded)) {
            throw new InsufficientSetupException('This class should not be called directly');
        }

        if (!static::$childOIDsLoaded) {
            ASN1::loadOIDs(is_array(static::OID_NAME) ?
                array_combine(static::OID_NAME, static::OID_VALUE) :
                [static::OID_NAME => static::OID_VALUE]);
            static::$childOIDsLoaded = true;
        }
        if (!self::$oidsLoaded) {
            // from https://tools.ietf.org/html/rfc2898
            ASN1::loadOIDs([
               // PBES1 encryption schemes
               'pbeWithMD2AndDES-CBC' => '1.2.840.113549.1.5.1',
               'pbeWithMD2AndRC2-CBC' => '1.2.840.113549.1.5.4',
               'pbeWithMD5AndDES-CBC' => '1.2.840.113549.1.5.3',
               'pbeWithMD5AndRC2-CBC' => '1.2.840.113549.1.5.6',
               'pbeWithSHA1AndDES-CBC' => '1.2.840.113549.1.5.10',
               'pbeWithSHA1AndRC2-CBC' => '1.2.840.113549.1.5.11',

               // from PKCS#12:
               // https://tools.ietf.org/html/rfc7292
               'pbeWithSHAAnd128BitRC4' => '1.2.840.113549.1.12.1.1',
               'pbeWithSHAAnd40BitRC4' => '1.2.840.113549.1.12.1.2',
               'pbeWithSHAAnd3-KeyTripleDES-CBC' => '1.2.840.113549.1.12.1.3',
               'pbeWithSHAAnd2-KeyTripleDES-CBC' => '1.2.840.113549.1.12.1.4',
               'pbeWithSHAAnd128BitRC2-CBC' => '1.2.840.113549.1.12.1.5',
               'pbeWithSHAAnd40BitRC2-CBC' => '1.2.840.113549.1.12.1.6',

               'id-PBKDF2' => '1.2.840.113549.1.5.12',
               'id-PBES2' => '1.2.840.113549.1.5.13',
               'id-PBMAC1' => '1.2.840.113549.1.5.14',

               // from PKCS#5 v2.1:
               // http://www.rsa.com/rsalabs/pkcs/files/h11302-wp-pkcs5v2-1-password-based-cryptography-standard.pdf
               'id-hmacWithSHA1' => '1.2.840.113549.2.7',
               'id-hmacWithSHA224' => '1.2.840.113549.2.8',
               'id-hmacWithSHA256' => '1.2.840.113549.2.9',
               'id-hmacWithSHA384' => '1.2.840.113549.2.10',
               'id-hmacWithSHA512' => '1.2.840.113549.2.11',
               'id-hmacWithSHA512-224' => '1.2.840.113549.2.12',
               'id-hmacWithSHA512-256' => '1.2.840.113549.2.13',

               'desCBC'       => '1.3.14.3.2.7',
               'des-EDE3-CBC' => '1.2.840.113549.3.7',
               'rc2CBC' => '1.2.840.113549.3.2',
               'rc5-CBC-PAD' => '1.2.840.113549.3.9',

               'aes128-CBC-PAD' => '2.16.840.1.101.3.4.1.2',
               'aes192-CBC-PAD' => '2.16.840.1.101.3.4.1.22',
               'aes256-CBC-PAD' => '2.16.840.1.101.3.4.1.42'
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
    protected static function load($key, $password = '')
    {
        if (!Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . gettype($key));
        }

        $isPublic = strpos($key, 'PUBLIC') !== false;
        $isPrivate = strpos($key, 'PRIVATE') !== false;

        $decoded = self::preParse($key);

        $meta = [];

        $decrypted = ASN1::asn1map($decoded[0], Maps\EncryptedPrivateKeyInfo::MAP);
        if (strlen($password) && is_array($decrypted)) {
            $algorithm = $decrypted['encryptionAlgorithm']['algorithm'];
            switch ($algorithm) {
                // PBES1
                case 'pbeWithMD2AndDES-CBC':
                case 'pbeWithMD2AndRC2-CBC':
                case 'pbeWithMD5AndDES-CBC':
                case 'pbeWithMD5AndRC2-CBC':
                case 'pbeWithSHA1AndDES-CBC':
                case 'pbeWithSHA1AndRC2-CBC':
                case 'pbeWithSHAAnd3-KeyTripleDES-CBC':
                case 'pbeWithSHAAnd2-KeyTripleDES-CBC':
                case 'pbeWithSHAAnd128BitRC2-CBC':
                case 'pbeWithSHAAnd40BitRC2-CBC':
                case 'pbeWithSHAAnd128BitRC4':
                case 'pbeWithSHAAnd40BitRC4':
                    $cipher = self::getPBES1EncryptionObject($algorithm);
                    $hash = self::getPBES1Hash($algorithm);
                    $kdf = self::getPBES1KDF($algorithm);

                    $meta['meta']['algorithm'] = $algorithm;

                    $temp = ASN1::decodeBER($decrypted['encryptionAlgorithm']['parameters']);
                    if (!$temp) {
                        throw new \RuntimeException('Unable to decode BER');
                    }
                    extract(ASN1::asn1map($temp[0], Maps\PBEParameter::MAP));
                    $iterationCount = (int) $iterationCount->toString();
                    $cipher->setPassword($password, $kdf, $hash, $salt, $iterationCount);
                    $key = $cipher->decrypt($decrypted['encryptedData']);
                    $decoded = ASN1::decodeBER($key);
                    if (!$decoded) {
                        throw new \RuntimeException('Unable to decode BER 2');
                    }

                    break;
                case 'id-PBES2':
                    $meta['meta']['algorithm'] = $algorithm;

                    $temp = ASN1::decodeBER($decrypted['encryptionAlgorithm']['parameters']);
                    if (!$temp) {
                        throw new \RuntimeException('Unable to decode BER');
                    }
                    $temp = ASN1::asn1map($temp[0], Maps\PBES2params::MAP);
                    extract($temp);

                    $cipher = self::getPBES2EncryptionObject($encryptionScheme['algorithm']);
                    $meta['meta']['cipher'] = $encryptionScheme['algorithm'];

                    $temp = ASN1::decodeBER($decrypted['encryptionAlgorithm']['parameters']);
                    if (!$temp) {
                        throw new \RuntimeException('Unable to decode BER');
                    }
                    $temp = ASN1::asn1map($temp[0], Maps\PBES2params::MAP);
                    extract($temp);

                    if (!$cipher instanceof RC2) {
                        $cipher->setIV($encryptionScheme['parameters']['octetString']);
                    } else {
                        $temp = ASN1::decodeBER($encryptionScheme['parameters']);
                        if (!$temp) {
                            throw new \RuntimeException('Unable to decode BER');
                        }
                        extract(ASN1::asn1map($temp[0], Maps\RC2CBCParameter::MAP));
                        $effectiveKeyLength = (int) $rc2ParametersVersion->toString();
                        switch ($effectiveKeyLength) {
                            case 160:
                                $effectiveKeyLength = 40;
                                break;
                            case 120:
                                $effectiveKeyLength = 64;
                                break;
                            case 58:
                                $effectiveKeyLength = 128;
                                break;
                            //default: // should be >= 256
                        }
                        $cipher->setIV($iv);
                        $cipher->setKeyLength($effectiveKeyLength);
                    }

                    $meta['meta']['keyDerivationFunc'] = $keyDerivationFunc['algorithm'];
                    switch ($keyDerivationFunc['algorithm']) {
                        case 'id-PBKDF2':
                            $temp = ASN1::decodeBER($keyDerivationFunc['parameters']);
                            if (!$temp) {
                                throw new \RuntimeException('Unable to decode BER');
                            }
                            $prf = ['algorithm' => 'id-hmacWithSHA1'];
                            $params = ASN1::asn1map($temp[0], Maps\PBKDF2params::MAP);
                            extract($params);
                            $meta['meta']['prf'] = $prf['algorithm'];
                            $hash = str_replace('-', '/', substr($prf['algorithm'], 11));
                            $params = [
                                $password,
                                'pbkdf2',
                                $hash,
                                $salt,
                                (int) $iterationCount->toString()
                            ];
                            if (isset($keyLength)) {
                                $params[] = (int) $keyLength->toString();
                            }
                            $cipher->setPassword(...$params);
                            $key = $cipher->decrypt($decrypted['encryptedData']);
                            $decoded = ASN1::decodeBER($key);
                            if (!$decoded) {
                                throw new \RuntimeException('Unable to decode BER 3');
                            }
                            break;
                        default:
                            throw new UnsupportedAlgorithmException('Only PBKDF2 is supported for PBES2 PKCS#8 keys');
                    }
                    break;
                case 'id-PBMAC1':
                    //$temp = ASN1::decodeBER($decrypted['encryptionAlgorithm']['parameters']);
                    //$value = ASN1::asn1map($temp[0], Maps\PBMAC1params::MAP);
                    // since i can't find any implementation that does PBMAC1 it is unsupported
                    throw new UnsupportedAlgorithmException('Only PBES1 and PBES2 PKCS#8 keys are supported.');
                // at this point we'll assume that the key conforms to PublicKeyInfo
            }
        }

        $private = ASN1::asn1map($decoded[0], Maps\OneAsymmetricKey::MAP);
        if (is_array($private)) {
            if ($isPublic) {
                throw new \UnexpectedValueException('Human readable string claims public key but DER encoded string claims private key');
            }

            if (isset($private['privateKeyAlgorithm']['parameters']) && !$private['privateKeyAlgorithm']['parameters'] instanceof ASN1\Element && isset($decoded[0]['content'][1]['content'][1])) {
                $temp = $decoded[0]['content'][1]['content'][1];
                $private['privateKeyAlgorithm']['parameters'] = new ASN1\Element(substr($key, $temp['start'], $temp['length']));
            }
            if (is_array(static::OID_NAME)) {
                if (!in_array($private['privateKeyAlgorithm']['algorithm'], static::OID_NAME)) {
                    throw new UnsupportedAlgorithmException($private['privateKeyAlgorithm']['algorithm'] . ' is not a supported key type');
                }
            } else {
                if ($private['privateKeyAlgorithm']['algorithm'] != static::OID_NAME) {
                    throw new UnsupportedAlgorithmException('Only ' . static::OID_NAME . ' keys are supported; this is a ' . $private['privateKeyAlgorithm']['algorithm'] . ' key');
                }
            }
            if (isset($private['publicKey'])) {
                if ($private['publicKey'][0] != "\0") {
                    throw new \UnexpectedValueException('The first byte of the public key should be null - not ' . bin2hex($private['publicKey'][0]));
                }
                $private['publicKey'] = substr($private['publicKey'], 1);
            }
            return $private + $meta;
        }

        // EncryptedPrivateKeyInfo and PublicKeyInfo have largely identical "signatures". the only difference
        // is that the former has an octet string and the later has a bit string. the first byte of a bit
        // string represents the number of bits in the last byte that are to be ignored but, currently,
        // bit strings wanting a non-zero amount of bits trimmed are not supported
        $public = ASN1::asn1map($decoded[0], Maps\PublicKeyInfo::MAP);

        if (is_array($public)) {
            if ($isPrivate) {
                throw new \UnexpectedValueException('Human readable string claims private key but DER encoded string claims public key');
            }

            if ($public['publicKey'][0] != "\0") {
                throw new \UnexpectedValueException('The first byte of the public key should be null - not ' . bin2hex($public['publicKey'][0]));
            }
            if (is_array(static::OID_NAME)) {
                if (!in_array($public['publicKeyAlgorithm']['algorithm'], static::OID_NAME)) {
                    throw new UnsupportedAlgorithmException($public['publicKeyAlgorithm']['algorithm'] . ' is not a supported key type');
                }
            } else {
                if ($public['publicKeyAlgorithm']['algorithm'] != static::OID_NAME) {
                    throw new UnsupportedAlgorithmException('Only ' . static::OID_NAME . ' keys are supported; this is a ' . $public['publicKeyAlgorithm']['algorithm'] . ' key');
                }
            }
            if (isset($public['publicKeyAlgorithm']['parameters']) && !$public['publicKeyAlgorithm']['parameters'] instanceof ASN1\Element && isset($decoded[0]['content'][0]['content'][1])) {
                $temp = $decoded[0]['content'][0]['content'][1];
                $public['publicKeyAlgorithm']['parameters'] = new ASN1\Element(substr($key, $temp['start'], $temp['length']));
            }
            $public['publicKey'] = substr($public['publicKey'], 1);
            return $public;
        }

        throw new \RuntimeException('Unable to parse using either OneAsymmetricKey or PublicKeyInfo ASN1 maps');
    }

    /**
     * Wrap a private key appropriately
     *
     * @param string $key
     * @param string $attr
     * @param mixed $params
     * @param string $password
     * @param string $oid optional
     * @param string $publicKey optional
     * @param array $options optional
     * @return string
     */
    protected static function wrapPrivateKey($key, $attr, $params, $password, $oid = null, $publicKey = '', array $options = [])
    {
        self::initialize_static_variables();

        $key = [
            'version' => 'v1',
            'privateKeyAlgorithm' => [
                'algorithm' => is_string(static::OID_NAME) ? static::OID_NAME : $oid
             ],
            'privateKey' => $key
        ];
        if ($oid != 'id-Ed25519' && $oid != 'id-Ed448') {
            $key['privateKeyAlgorithm']['parameters'] = $params;
        }
        if (!empty($attr)) {
            $key['attributes'] = $attr;
        }
        if (!empty($publicKey)) {
            $key['version'] = 'v2';
            $key['publicKey'] = $publicKey;
        }
        $key = ASN1::encodeDER($key, Maps\OneAsymmetricKey::MAP);
        if (!empty($password) && is_string($password)) {
            $salt = Random::string(8);

            $iterationCount = isset($options['iterationCount']) ? $options['iterationCount'] : self::$defaultIterationCount;
            $encryptionAlgorithm = isset($options['encryptionAlgorithm']) ? $options['encryptionAlgorithm'] : self::$defaultEncryptionAlgorithm;
            $encryptionScheme = isset($options['encryptionScheme']) ? $options['encryptionScheme'] : self::$defaultEncryptionScheme;
            $prf = isset($options['PRF']) ? $options['PRF'] : self::$defaultPRF;

            if ($encryptionAlgorithm == 'id-PBES2') {
                $crypto = self::getPBES2EncryptionObject($encryptionScheme);
                $hash = str_replace('-', '/', substr($prf, 11));
                $kdf = 'pbkdf2';
                $iv = Random::string($crypto->getBlockLength() >> 3);

                $PBKDF2params = [
                    'salt' => $salt,
                    'iterationCount' => $iterationCount,
                    'prf' => ['algorithm' => $prf, 'parameters' => null]
                ];
                $PBKDF2params = ASN1::encodeDER($PBKDF2params, Maps\PBKDF2params::MAP);

                if (!$crypto instanceof RC2) {
                    $params = ['octetString' => $iv];
                } else {
                    $params = [
                        'rc2ParametersVersion' => 58,
                        'iv' => $iv
                    ];
                    $params = ASN1::encodeDER($params, Maps\RC2CBCParameter::MAP);
                    $params = new ASN1\Element($params);
                }

                $params = [
                    'keyDerivationFunc' => [
                        'algorithm' => 'id-PBKDF2',
                        'parameters' => new ASN1\Element($PBKDF2params)
                    ],
                    'encryptionScheme' => [
                        'algorithm' => $encryptionScheme,
                        'parameters' => $params
                    ]
                ];
                $params = ASN1::encodeDER($params, Maps\PBES2params::MAP);

                $crypto->setIV($iv);
            } else {
                $crypto = self::getPBES1EncryptionObject($encryptionAlgorithm);
                $hash = self::getPBES1Hash($encryptionAlgorithm);
                $kdf = self::getPBES1KDF($encryptionAlgorithm);

                $params = [
                    'salt' => $salt,
                    'iterationCount' => $iterationCount
                ];
                $params = ASN1::encodeDER($params, Maps\PBEParameter::MAP);
            }
            $crypto->setPassword($password, $kdf, $hash, $salt, $iterationCount);
            $key = $crypto->encrypt($key);

            $key = [
                'encryptionAlgorithm' => [
                    'algorithm' => $encryptionAlgorithm,
                    'parameters' => new ASN1\Element($params)
                ],
                'encryptedData' => $key
            ];

            $key = ASN1::encodeDER($key, Maps\EncryptedPrivateKeyInfo::MAP);

            return "-----BEGIN ENCRYPTED PRIVATE KEY-----\r\n" .
                   chunk_split(Strings::base64_encode($key), 64) .
                   "-----END ENCRYPTED PRIVATE KEY-----";
        }

        return "-----BEGIN PRIVATE KEY-----\r\n" .
               chunk_split(Strings::base64_encode($key), 64) .
               "-----END PRIVATE KEY-----";
    }

    /**
     * Wrap a public key appropriately
     *
     * @param string $key
     * @param mixed $params
     * @param string $oid
     * @return string
     */
    protected static function wrapPublicKey($key, $params, $oid = null)
    {
        self::initialize_static_variables();

        $key = [
            'publicKeyAlgorithm' => [
                'algorithm' => is_string(static::OID_NAME) ? static::OID_NAME : $oid
            ],
            'publicKey' => "\0" . $key
        ];

        if ($oid != 'id-Ed25519' && $oid != 'id-Ed448') {
            $key['publicKeyAlgorithm']['parameters'] = $params;
        }

        $key = ASN1::encodeDER($key, Maps\PublicKeyInfo::MAP);

        return "-----BEGIN PUBLIC KEY-----\r\n" .
               chunk_split(Strings::base64_encode($key), 64) .
               "-----END PUBLIC KEY-----";
    }

    /**
     * Perform some preliminary parsing of the key
     *
     * @param string $key
     * @return array
     */
    private static function preParse(&$key)
    {
        self::initialize_static_variables();

        if (self::$format != self::MODE_DER) {
            $decoded = ASN1::extractBER($key);
            if ($decoded !== false) {
                $key = $decoded;
            } elseif (self::$format == self::MODE_PEM) {
                throw new \UnexpectedValueException('Expected base64-encoded PEM format but was unable to decode base64 text');
            }
        }

        $decoded = ASN1::decodeBER($key);
        if (!$decoded) {
            throw new \RuntimeException('Unable to decode BER');
        }

        return $decoded;
    }

    /**
     * Returns the encryption parameters used by the key
     *
     * @param string $key
     * @return array
     */
    public static function extractEncryptionAlgorithm($key)
    {
        if (!Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . gettype($key));
        }

        $decoded = self::preParse($key);

        $r = ASN1::asn1map($decoded[0], ASN1\Maps\EncryptedPrivateKeyInfo::MAP);
        if (!is_array($r)) {
            throw new \RuntimeException('Unable to parse using EncryptedPrivateKeyInfo map');
        }

        if ($r['encryptionAlgorithm']['algorithm'] == 'id-PBES2') {
            $decoded = ASN1::decodeBER($r['encryptionAlgorithm']['parameters']->element);
            if (!$decoded) {
                throw new \RuntimeException('Unable to decode BER');
            }
            $r['encryptionAlgorithm']['parameters'] = ASN1::asn1map($decoded[0], ASN1\Maps\PBES2params::MAP);

            $kdf = &$r['encryptionAlgorithm']['parameters']['keyDerivationFunc'];
            switch ($kdf['algorithm']) {
                case 'id-PBKDF2':
                    $decoded = ASN1::decodeBER($kdf['parameters']->element);
                    if (!$decoded) {
                        throw new \RuntimeException('Unable to decode BER');
                    }
                    $kdf['parameters'] = ASN1::asn1map($decoded[0], Maps\PBKDF2params::MAP);
            }
        }

        return $r['encryptionAlgorithm'];
    }
}
