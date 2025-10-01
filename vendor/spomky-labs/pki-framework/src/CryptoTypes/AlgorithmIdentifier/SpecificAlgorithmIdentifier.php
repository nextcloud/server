<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Base class for algorithm identifiers implementing specific functionality and parameter handling.
 */
abstract class SpecificAlgorithmIdentifier extends AlgorithmIdentifier
{
    /**
     * Initialize object from algorithm identifier parameters.
     *
     * @param null|UnspecifiedType $params Parameters or null if none
     */
    abstract public static function fromASN1Params(?UnspecifiedType $params = null): self;
}
