<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Cose;

use Assert\Assertion;
use Assert\AssertionFailedException;

/**
 * @see https://www.iana.org/assignments/cose/cose.xhtml#algorithms
 */
abstract class Algorithms
{
    public const COSE_ALGORITHM_AES_CCM_64_128_256 = 33;
    public const COSE_ALGORITHM_AES_CCM_64_128_128 = 32;
    public const COSE_ALGORITHM_AES_CCM_16_128_256 = 31;
    public const COSE_ALGORITHM_AES_CCM_16_128_128 = 30;
    public const COSE_ALGORITHM_AES_MAC_256_128 = 26;
    public const COSE_ALGORITHM_AES_MAC_128_128 = 25;
    public const COSE_ALGORITHM_CHACHA20_POLY1305 = 24;
    public const COSE_ALGORITHM_AES_MAC_256_64 = 15;
    public const COSE_ALGORITHM_AES_MAC_128_64 = 14;
    public const COSE_ALGORITHM_AES_CCM_64_64_256 = 13;
    public const COSE_ALGORITHM_AES_CCM_64_64_128 = 12;
    public const COSE_ALGORITHM_AES_CCM_16_64_256 = 11;
    public const COSE_ALGORITHM_AES_CCM_16_64_128 = 10;
    public const COSE_ALGORITHM_HS512 = 7;
    public const COSE_ALGORITHM_HS384 = 6;
    public const COSE_ALGORITHM_HS256 = 5;
    public const COSE_ALGORITHM_HS256_64 = 4;
    public const COSE_ALGORITHM_A256GCM = 3;
    public const COSE_ALGORITHM_A192GCM = 2;
    public const COSE_ALGORITHM_A128GCM = 1;
    public const COSE_ALGORITHM_A128KW = -3;
    public const COSE_ALGORITHM_A192KW = -4;
    public const COSE_ALGORITHM_A256KW = -5;
    public const COSE_ALGORITHM_DIRECT = -6;
    public const COSE_ALGORITHM_ES256 = -7;
    public const COSE_ALGORITHM_EdDSA = -8;
    public const COSE_ALGORITHM_ED256 = -260;
    public const COSE_ALGORITHM_ED512 = -261;
    public const COSE_ALGORITHM_DIRECT_HKDF_SHA_256 = -10;
    public const COSE_ALGORITHM_DIRECT_HKDF_SHA_512 = -11;
    public const COSE_ALGORITHM_DIRECT_HKDF_AES_128 = -12;
    public const COSE_ALGORITHM_DIRECT_HKDF_AES_256 = -13;
    public const COSE_ALGORITHM_ECDH_ES_HKDF_256 = -25;
    public const COSE_ALGORITHM_ECDH_ES_HKDF_512 = -26;
    public const COSE_ALGORITHM_ECDH_SS_HKDF_256 = -27;
    public const COSE_ALGORITHM_ECDH_SS_HKDF_512 = -28;
    public const COSE_ALGORITHM_ECDH_ES_A128KW = -29;
    public const COSE_ALGORITHM_ECDH_ES_A192KW = -30;
    public const COSE_ALGORITHM_ECDH_ES_A256KW = -31;
    public const COSE_ALGORITHM_ECDH_SS_A128KW = -32;
    public const COSE_ALGORITHM_ECDH_SS_A192KW = -33;
    public const COSE_ALGORITHM_ECDH_SS_A256KW = -34;
    public const COSE_ALGORITHM_ES384 = -35;
    public const COSE_ALGORITHM_ES512 = -36;
    public const COSE_ALGORITHM_PS256 = -37;
    public const COSE_ALGORITHM_PS384 = -38;
    public const COSE_ALGORITHM_PS512 = -39;
    public const COSE_ALGORITHM_RSAES_OAEP = -40;
    public const COSE_ALGORITHM_RSAES_OAEP_256 = -41;
    public const COSE_ALGORITHM_RSAES_OAEP_512 = -42;
    public const COSE_ALGORITHM_ES256K = -46;
    public const COSE_ALGORITHM_RS256 = -257;
    public const COSE_ALGORITHM_RS384 = -258;
    public const COSE_ALGORITHM_RS512 = -259;
    public const COSE_ALGORITHM_RS1 = -65535;

    public const COSE_ALGORITHM_MAP = [
        self::COSE_ALGORITHM_ES256 => OPENSSL_ALGO_SHA256,
        self::COSE_ALGORITHM_ES384 => OPENSSL_ALGO_SHA384,
        self::COSE_ALGORITHM_ES512 => OPENSSL_ALGO_SHA512,
        self::COSE_ALGORITHM_RS256 => OPENSSL_ALGO_SHA256,
        self::COSE_ALGORITHM_RS384 => OPENSSL_ALGO_SHA384,
        self::COSE_ALGORITHM_RS512 => OPENSSL_ALGO_SHA512,
        self::COSE_ALGORITHM_RS1 => OPENSSL_ALGO_SHA1,
    ];

    public const COSE_HASH_MAP = [
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

    /**
     * @throws AssertionFailedException
     */
    public static function getOpensslAlgorithmFor(int $algorithmIdentifier): int
    {
        Assertion::keyExists(self::COSE_ALGORITHM_MAP, $algorithmIdentifier, 'The specified algorithm identifier is not supported');

        return self::COSE_ALGORITHM_MAP[$algorithmIdentifier];
    }

    /**
     * @throws AssertionFailedException
     */
    public static function getHashAlgorithmFor(int $algorithmIdentifier): string
    {
        Assertion::keyExists(self::COSE_HASH_MAP, $algorithmIdentifier, 'The specified algorithm identifier is not supported');

        return self::COSE_HASH_MAP[$algorithmIdentifier];
    }
}
