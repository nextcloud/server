<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\NullType;

/*
From RFC 3279 - 2.2.1  RSA Signature Algorithm:

    When any of these three OIDs appears within the ASN.1 type
    AlgorithmIdentifier, the parameters component of that type SHALL be
    the ASN.1 type NULL.
 */

/**
 * Base class for RSA signature algorithms specified in RFC 3279.
 *
 * @see https://tools.ietf.org/html/rfc3279#section-2.2.1
 */
abstract class RFC3279RSASignatureAlgorithmIdentifier extends RSASignatureAlgorithmIdentifier
{
    protected function paramsASN1(): ?Element
    {
        return NullType::create();
    }
}
