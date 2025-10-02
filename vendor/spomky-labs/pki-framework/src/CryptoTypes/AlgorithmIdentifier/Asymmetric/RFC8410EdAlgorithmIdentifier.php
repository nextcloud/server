<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AsymmetricCryptoAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\SignatureAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;

/*
From RFC 8410:

    For all of the OIDs, the parameters MUST be absent.

    It is possible to find systems that require the parameters to be
    present.  This can be due to either a defect in the original 1997
    syntax or a programming error where developers never got input where
    this was not true.  The optimal solution is to fix these systems;
    where this is not possible, the problem needs to be restricted to
    that subsystem and not propagated to the Internet.
 */

/**
 * Algorithm identifier for the Edwards-curve Digital Signature Algorithm (EdDSA) identifiers specified by RFC 8410.
 *
 * Same algorithm identifier is used for public and private keys as well as for signatures.
 *
 * @see https://tools.ietf.org/html/rfc8410#section-3
 * @see https://tools.ietf.org/html/rfc8410#section-6
 */
abstract class RFC8410EdAlgorithmIdentifier extends SpecificAlgorithmIdentifier implements AsymmetricCryptoAlgorithmIdentifier, SignatureAlgorithmIdentifier
{
    protected function paramsASN1(): ?Element
    {
        return null;
    }
}
