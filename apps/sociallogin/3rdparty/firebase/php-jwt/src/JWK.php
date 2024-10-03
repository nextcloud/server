<?php

namespace Firebase\JWT;

use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * JSON Web Key implementation, based on this spec:
 * https://tools.ietf.org/html/draft-ietf-jose-json-web-key-41
 *
 * PHP version 5
 *
 * @category Authentication
 * @package  Authentication_JWT
 * @author   Bui Sy Nguyen <nguyenbs@gmail.com>
 * @license  http://opensource.org/licenses/BSD-3-Clause 3-clause BSD
 * @link     https://github.com/firebase/php-jwt
 */
class JWK
{
    private const OID = '1.2.840.10045.2.1';
    private const ASN1_OBJECT_IDENTIFIER = 0x06;
    private const ASN1_SEQUENCE = 0x10; // also defined in JWT
    private const ASN1_BIT_STRING = 0x03;
    private const EC_CURVES = [
        'P-256' => '1.2.840.10045.3.1.7', // Len: 64
        'secp256k1' => '1.3.132.0.10', // Len: 64
        // 'P-384' => '1.3.132.0.34', // Len: 96 (not yet supported)
        // 'P-521' => '1.3.132.0.35', // Len: 132 (not supported)
    ];

    /**
     * Parse a set of JWK keys
     *
     * @param array<mixed> $jwks The JSON Web Key Set as an associative array
     * @param string       $defaultAlg The algorithm for the Key object if "alg" is not set in the
     *                                 JSON Web Key Set
     *
     * @return array<string, Key> An associative array of key IDs (kid) to Key objects
     *
     * @throws InvalidArgumentException     Provided JWK Set is empty
     * @throws UnexpectedValueException     Provided JWK Set was invalid
     * @throws DomainException              OpenSSL failure
     *
     * @uses parseKey
     */
    public static function parseKeySet(array $jwks, string $defaultAlg = null): array
    {
        $keys = [];

        if (!isset($jwks['keys'])) {
            throw new UnexpectedValueException('"keys" member must exist in the JWK Set');
        }

        if (empty($jwks['keys'])) {
            throw new InvalidArgumentException('JWK Set did not contain any keys');
        }

        foreach ($jwks['keys'] as $k => $v) {
            $kid = isset($v['kid']) ? $v['kid'] : $k;
            if ($key = self::parseKey($v, $defaultAlg)) {
                $keys[(string) $kid] = $key;
            }
        }

        if (0 === \count($keys)) {
            throw new UnexpectedValueException('No supported algorithms found in JWK Set');
        }

        return $keys;
    }

    /**
     * Parse a JWK key
     *
     * @param array<mixed> $jwk An individual JWK
     * @param string       $defaultAlg The algorithm for the Key object if "alg" is not set in the
     *                                 JSON Web Key Set
     *
     * @return Key The key object for the JWK
     *
     * @throws InvalidArgumentException     Provided JWK is empty
     * @throws UnexpectedValueException     Provided JWK was invalid
     * @throws DomainException              OpenSSL failure
     *
     * @uses createPemFromModulusAndExponent
     */
    public static function parseKey(array $jwk, string $defaultAlg = null): ?Key
    {
        if (empty($jwk)) {
            throw new InvalidArgumentException('JWK must not be empty');
        }

        if (!isset($jwk['kty'])) {
            throw new UnexpectedValueException('JWK must contain a "kty" parameter');
        }

        if (!isset($jwk['alg'])) {
            if (\is_null($defaultAlg)) {
                // The "alg" parameter is optional in a KTY, but an algorithm is required
                // for parsing in this library. Use the $defaultAlg parameter when parsing the
                // key set in order to prevent this error.
                // @see https://datatracker.ietf.org/doc/html/rfc7517#section-4.4
                throw new UnexpectedValueException('JWK must contain an "alg" parameter');
            }
            $jwk['alg'] = $defaultAlg;
        }

        switch ($jwk['kty']) {
            case 'RSA':
                if (!empty($jwk['d'])) {
                    throw new UnexpectedValueException('RSA private keys are not supported');
                }
                if (!isset($jwk['n']) || !isset($jwk['e'])) {
                    throw new UnexpectedValueException('RSA keys must contain values for both "n" and "e"');
                }

                $pem = self::createPemFromModulusAndExponent($jwk['n'], $jwk['e']);
                $publicKey = \openssl_pkey_get_public($pem);
                if (false === $publicKey) {
                    throw new DomainException(
                        'OpenSSL error: ' . \openssl_error_string()
                    );
                }
                return new Key($publicKey, $jwk['alg']);
            case 'EC':
                if (isset($jwk['d'])) {
                    // The key is actually a private key
                    throw new UnexpectedValueException('Key data must be for a public key');
                }

                if (empty($jwk['crv'])) {
                    throw new UnexpectedValueException('crv not set');
                }

                if (!isset(self::EC_CURVES[$jwk['crv']])) {
                    throw new DomainException('Unrecognised or unsupported EC curve');
                }

                if (empty($jwk['x']) || empty($jwk['y'])) {
                    throw new UnexpectedValueException('x and y not set');
                }

                $publicKey = self::createPemFromCrvAndXYCoordinates($jwk['crv'], $jwk['x'], $jwk['y']);
                return new Key($publicKey, $jwk['alg']);
            default:
                // Currently only RSA is supported
                break;
        }

        return null;
    }

    /**
     * Converts the EC JWK values to pem format.
     *
     * @param   string  $crv The EC curve (only P-256 is supported)
     * @param   string  $x   The EC x-coordinate
     * @param   string  $y   The EC y-coordinate
     *
     * @return  string
     */
    private static function createPemFromCrvAndXYCoordinates(string $crv, string $x, string $y): string
    {
        $pem =
            self::encodeDER(
                self::ASN1_SEQUENCE,
                self::encodeDER(
                    self::ASN1_SEQUENCE,
                    self::encodeDER(
                        self::ASN1_OBJECT_IDENTIFIER,
                        self::encodeOID(self::OID)
                    )
                    . self::encodeDER(
                        self::ASN1_OBJECT_IDENTIFIER,
                        self::encodeOID(self::EC_CURVES[$crv])
                    )
                ) .
                self::encodeDER(
                    self::ASN1_BIT_STRING,
                    \chr(0x00) . \chr(0x04)
                    . JWT::urlsafeB64Decode($x)
                    . JWT::urlsafeB64Decode($y)
                )
            );

        return sprintf(
            "-----BEGIN PUBLIC KEY-----\n%s\n-----END PUBLIC KEY-----\n",
            wordwrap(base64_encode($pem), 64, "\n", true)
        );
    }

    /**
     * Create a public key represented in PEM format from RSA modulus and exponent information
     *
     * @param string $n The RSA modulus encoded in Base64
     * @param string $e The RSA exponent encoded in Base64
     *
     * @return string The RSA public key represented in PEM format
     *
     * @uses encodeLength
     */
    private static function createPemFromModulusAndExponent(
        string $n,
        string $e
    ): string {
        $mod = JWT::urlsafeB64Decode($n);
        $exp = JWT::urlsafeB64Decode($e);

        $modulus = \pack('Ca*a*', 2, self::encodeLength(\strlen($mod)), $mod);
        $publicExponent = \pack('Ca*a*', 2, self::encodeLength(\strlen($exp)), $exp);

        $rsaPublicKey = \pack(
            'Ca*a*a*',
            48,
            self::encodeLength(\strlen($modulus) + \strlen($publicExponent)),
            $modulus,
            $publicExponent
        );

        // sequence(oid(1.2.840.113549.1.1.1), null)) = rsaEncryption.
        $rsaOID = \pack('H*', '300d06092a864886f70d0101010500'); // hex version of MA0GCSqGSIb3DQEBAQUA
        $rsaPublicKey = \chr(0) . $rsaPublicKey;
        $rsaPublicKey = \chr(3) . self::encodeLength(\strlen($rsaPublicKey)) . $rsaPublicKey;

        $rsaPublicKey = \pack(
            'Ca*a*',
            48,
            self::encodeLength(\strlen($rsaOID . $rsaPublicKey)),
            $rsaOID . $rsaPublicKey
        );

        return "-----BEGIN PUBLIC KEY-----\r\n" .
            \chunk_split(\base64_encode($rsaPublicKey), 64) .
            '-----END PUBLIC KEY-----';
    }

    /**
     * DER-encode the length
     *
     * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4.  See
     * {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 paragraph 8.1.3} for more information.
     *
     * @param int $length
     * @return string
     */
    private static function encodeLength(int $length): string
    {
        if ($length <= 0x7F) {
            return \chr($length);
        }

        $temp = \ltrim(\pack('N', $length), \chr(0));

        return \pack('Ca*', 0x80 | \strlen($temp), $temp);
    }

    /**
     * Encodes a value into a DER object.
     * Also defined in Firebase\JWT\JWT
     *
     * @param   int     $type DER tag
     * @param   string  $value the value to encode
     * @return  string  the encoded object
     */
    private static function encodeDER(int $type, string $value): string
    {
        $tag_header = 0;
        if ($type === self::ASN1_SEQUENCE) {
            $tag_header |= 0x20;
        }

        // Type
        $der = \chr($tag_header | $type);

        // Length
        $der .= \chr(\strlen($value));

        return $der . $value;
    }

    /**
     * Encodes a string into a DER-encoded OID.
     *
     * @param   string $oid the OID string
     * @return  string the binary DER-encoded OID
     */
    private static function encodeOID(string $oid): string
    {
        $octets = explode('.', $oid);

        // Get the first octet
        $first = (int) array_shift($octets);
        $second = (int) array_shift($octets);
        $oid = \chr($first * 40 + $second);

        // Iterate over subsequent octets
        foreach ($octets as $octet) {
            if ($octet == 0) {
                $oid .= \chr(0x00);
                continue;
            }
            $bin = '';

            while ($octet) {
                $bin .= \chr(0x80 | ($octet & 0x7f));
                $octet >>= 7;
            }
            $bin[0] = $bin[0] & \chr(0x7f);

            // Convert to big endian if necessary
            if (pack('V', 65534) == pack('L', 65534)) {
                $oid .= strrev($bin);
            } else {
                $oid .= $bin;
            }
        }

        return $oid;
    }
}
