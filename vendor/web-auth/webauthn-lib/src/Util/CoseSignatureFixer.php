<?php

declare(strict_types=1);

namespace Webauthn\Util;

use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\ECDSA\ECSignature;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\Signature;

/**
 * This class fixes the signature of the ECDSA based algorithms.
 *
 * @internal
 *
 * @see https://www.w3.org/TR/webauthn/#signature-attestation-types
 */
abstract class CoseSignatureFixer
{
    private const ES256_SIGNATURE_LENGTH = 64;

    private const ES384_SIGNATURE_LENGTH = 96;

    private const ES512_SIGNATURE_LENGTH = 132;

    public static function fix(string $signature, Signature $algorithm): string
    {
        switch ($algorithm::identifier()) {
            case ES256K::ID:
            case ES256::ID:
                if (mb_strlen($signature, '8bit') === self::ES256_SIGNATURE_LENGTH) {
                    return $signature;
                }

                return ECSignature::fromAsn1(
                    $signature,
                    self::ES256_SIGNATURE_LENGTH
                ); //TODO: fix this hardcoded value by adding a dedicated method for the algorithms
            case ES384::ID:
                if (mb_strlen($signature, '8bit') === self::ES384_SIGNATURE_LENGTH) {
                    return $signature;
                }

                return ECSignature::fromAsn1($signature, self::ES384_SIGNATURE_LENGTH);
            case ES512::ID:
                if (mb_strlen($signature, '8bit') === self::ES512_SIGNATURE_LENGTH) {
                    return $signature;
                }

                return ECSignature::fromAsn1($signature, self::ES512_SIGNATURE_LENGTH);
        }

        return $signature;
    }
}
