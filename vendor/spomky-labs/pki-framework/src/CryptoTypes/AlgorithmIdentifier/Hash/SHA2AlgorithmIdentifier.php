<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\NullType;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\HashAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;

/*
From RFC 5754 - 2. Message Digest Algorithms

    The AlgorithmIdentifier parameters field is OPTIONAL.
    Implementations MUST accept SHA2 AlgorithmIdentifiers with absent
    parameters.  Implementations MUST accept SHA2 AlgorithmIdentifiers
    with NULL parameters.  Implementations MUST generate SHA2
    AlgorithmIdentifiers with absent parameters.
 */

/**
 * Base class for SHA2 algorithm identifiers.
 *
 * @see https://tools.ietf.org/html/rfc4055#section-2.1
 * @see https://tools.ietf.org/html/rfc5754#section-2
 */
abstract class SHA2AlgorithmIdentifier extends SpecificAlgorithmIdentifier implements HashAlgorithmIdentifier
{
    protected ?NullType $_params = null;

    /**
     * @return null|NullType
     */
    protected function paramsASN1(): ?Element
    {
        return $this->_params;
    }
}
