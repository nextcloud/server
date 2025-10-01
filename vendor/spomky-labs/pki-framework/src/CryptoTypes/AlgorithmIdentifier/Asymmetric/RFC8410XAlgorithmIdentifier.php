<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AsymmetricCryptoAlgorithmIdentifier;
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
 * Algorithm identifier for the Diffie-Hellman operations specified by RFC 8410.
 *
 * @see https://tools.ietf.org/html/rfc8410#section-3
 */
abstract class RFC8410XAlgorithmIdentifier extends SpecificAlgorithmIdentifier implements AsymmetricCryptoAlgorithmIdentifier
{
    protected function paramsASN1(): ?Element
    {
        return null;
    }
}
