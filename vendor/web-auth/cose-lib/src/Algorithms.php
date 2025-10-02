<?php

declare(strict_types=1);

namespace Cose;

use InvalidArgumentException;
use function array_key_exists;
use const OPENSSL_ALGO_SHA1;
use const OPENSSL_ALGO_SHA256;
use const OPENSSL_ALGO_SHA384;
use const OPENSSL_ALGO_SHA512;

/**
 * @see https://www.iana.org/assignments/cose/cose.xhtml#algorithms
 */
abstract class Algorithms
{
    final public const COSE_ALGORITHM_AES_CCM_64_128_256 = 33;

    final public const COSE_ALGORITHM_AES_CCM_64_128_128 = 32;

    final public const COSE_ALGORITHM_AES_CCM_16_128_256 = 31;

    final public const COSE_ALGORITHM_AES_CCM_16_128_128 = 30;

    final public const COSE_ALGORITHM_AES_MAC_256_128 = 26;

    final public const COSE_ALGORITHM_AES_MAC_128_128 = 25;

    final public const COSE_ALGORITHM_CHACHA20_POLY1305 = 24;

    final public const COSE_ALGORITHM_AES_MAC_256_64 = 15;

    final public const COSE_ALGORITHM_AES_MAC_128_64 = 14;

    final public const COSE_ALGORITHM_AES_CCM_64_64_256 = 13;

    final public const COSE_ALGORITHM_AES_CCM_64_64_128 = 12;

    final public const COSE_ALGORITHM_AES_CCM_16_64_256 = 11;

    final public const COSE_ALGORITHM_AES_CCM_16_64_128 = 10;

    final public const COSE_ALGORITHM_HS512 = 7;

    final public const COSE_ALGORITHM_HS384 = 6;

    final public const COSE_ALGORITHM_HS256 = 5;

    final public const COSE_ALGORITHM_HS256_64 = 4;

    final public const COSE_ALGORITHM_A256GCM = 3;

    final public const COSE_ALGORITHM_A192GCM = 2;

    final public const COSE_ALGORITHM_A128GCM = 1;

    final public const COSE_ALGORITHM_A128KW = -3;

    final public const COSE_ALGORITHM_A192KW = -4;

    final public const COSE_ALGORITHM_A256KW = -5;

    final public const COSE_ALGORITHM_DIRECT = -6;

    final public const COSE_ALGORITHM_ES256 = -7;

    /**
     * @deprecated since v4.0.6. Please use COSE_ALGORITHM_EDDSA instead. Will be removed in v5.0.0
     */
    final public const COSE_ALGORITHM_EdDSA = -8;

    final public const COSE_ALGORITHM_EDDSA = -8;

    final public const COSE_ALGORITHM_ED256 = -260;

    final public const COSE_ALGORITHM_ED512 = -261;

    final public const COSE_ALGORITHM_DIRECT_HKDF_SHA_256 = -10;

    final public const COSE_ALGORITHM_DIRECT_HKDF_SHA_512 = -11;

    final public const COSE_ALGORITHM_DIRECT_HKDF_AES_128 = -12;

    final public const COSE_ALGORITHM_DIRECT_HKDF_AES_256 = -13;

    final public const COSE_ALGORITHM_ECDH_ES_HKDF_256 = -25;

    final public const COSE_ALGORITHM_ECDH_ES_HKDF_512 = -26;

    final public const COSE_ALGORITHM_ECDH_SS_HKDF_256 = -27;

    final public const COSE_ALGORITHM_ECDH_SS_HKDF_512 = -28;

    final public const COSE_ALGORITHM_ECDH_ES_A128KW = -29;

    final public const COSE_ALGORITHM_ECDH_ES_A192KW = -30;

    final public const COSE_ALGORITHM_ECDH_ES_A256KW = -31;

    final public const COSE_ALGORITHM_ECDH_SS_A128KW = -32;

    final public const COSE_ALGORITHM_ECDH_SS_A192KW = -33;

    final public const COSE_ALGORITHM_ECDH_SS_A256KW = -34;

    final public const COSE_ALGORITHM_ES384 = -35;

    final public const COSE_ALGORITHM_ES512 = -36;

    final public const COSE_ALGORITHM_PS256 = -37;

    final public const COSE_ALGORITHM_PS384 = -38;

    final public const COSE_ALGORITHM_PS512 = -39;

    final public const COSE_ALGORITHM_RSAES_OAEP = -40;

    final public const COSE_ALGORITHM_RSAES_OAEP_256 = -41;

    final public const COSE_ALGORITHM_RSAES_OAEP_512 = -42;

    final public const COSE_ALGORITHM_ES256K = -46;

    final public const COSE_ALGORITHM_RS256 = -257;

    final public const COSE_ALGORITHM_RS384 = -258;

    final public const COSE_ALGORITHM_RS512 = -259;

    final public const COSE_ALGORITHM_RS1 = -65535;

    final public const COSE_ALGORITHM_MAP = [
        self::COSE_ALGORITHM_ES256 => OPENSSL_ALGO_SHA256,
        self::COSE_ALGORITHM_ES384 => OPENSSL_ALGO_SHA384,
        self::COSE_ALGORITHM_ES512 => OPENSSL_ALGO_SHA512,
        self::COSE_ALGORITHM_RS256 => OPENSSL_ALGO_SHA256,
        self::COSE_ALGORITHM_RS384 => OPENSSL_ALGO_SHA384,
        self::COSE_ALGORITHM_RS512 => OPENSSL_ALGO_SHA512,
        self::COSE_ALGORITHM_RS1 => OPENSSL_ALGO_SHA1,
    ];

    final public const COSE_HASH_MAP = [
        self::COSE_ALGORITHM_ES256K => 'sha256',
        self::COSE_ALGORITHM_ES256 => 'sha256',
        self::COSE_ALGORITHM_ES384 => 'sha384',
        self::COSE_ALGORITHM_ES512 => 'sha512',
        self::COSE_ALGORITHM_RS256 => 'sha256',
        self::COSE_ALGORITHM_RS384 => 'sha384',
        self::COSE_ALGORITHM_RS512 => 'sha512',
        self::COSE_ALGORITHM_PS256 => 'sha256',
        self::COSE_ALGORITHM_PS384 => 'sha384',
        self::COSE_ALGORITHM_PS512 => 'sha512',
        self::COSE_ALGORITHM_RS1 => 'sha1',
    ];

    public static function getOpensslAlgorithmFor(int $algorithmIdentifier): int
    {
        if (! array_key_exists($algorithmIdentifier, self::COSE_ALGORITHM_MAP)) {
            throw new InvalidArgumentException('The specified algorithm identifier is not supported');
        }

        return self::COSE_ALGORITHM_MAP[$algorithmIdentifier];
    }

    public static function getHashAlgorithmFor(int $algorithmIdentifier): string
    {
        if (! array_key_exists($algorithmIdentifier, self::COSE_HASH_MAP)) {
            throw new InvalidArgumentException('The specified algorithm identifier is not supported');
        }

        return self::COSE_HASH_MAP[$algorithmIdentifier];
    }
}
