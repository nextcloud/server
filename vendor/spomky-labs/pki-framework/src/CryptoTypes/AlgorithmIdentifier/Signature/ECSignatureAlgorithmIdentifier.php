<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\SignatureAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;

/*
From RFC 5758 - 3.2.  ECDSA Signature Algorithm

    When the ecdsa-with-SHA224, ecdsa-with-SHA256, ecdsa-with-SHA384, or
    ecdsa-with-SHA512 algorithm identifier appears in the algorithm field
    as an AlgorithmIdentifier, the encoding MUST omit the parameters
    field.
 */

/**
 * Base class for ECDSA signature algorithm identifiers.
 *
 * @see https://tools.ietf.org/html/rfc5758#section-3.2
 * @see https://tools.ietf.org/html/rfc5480#appendix-A
 */
abstract class ECSignatureAlgorithmIdentifier extends SpecificAlgorithmIdentifier implements SignatureAlgorithmIdentifier
{
    public function supportsKeyAlgorithm(AlgorithmIdentifier $algo): bool
    {
        return $algo->oid() === self::OID_EC_PUBLIC_KEY;
    }

    protected function paramsASN1(): ?Element
    {
        return null;
    }
}
