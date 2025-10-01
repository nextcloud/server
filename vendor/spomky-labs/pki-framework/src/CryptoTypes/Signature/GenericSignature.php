<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Signature;

use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;

/**
 * Generic signature value container.
 */
final class GenericSignature extends Signature
{
    /**
     * @param BitString $signature Signature value
     * @param AlgorithmIdentifierType $signatureAlgorithm Algorithm identifier
     */
    private function __construct(
        private readonly BitString $signature,
        private readonly AlgorithmIdentifierType $signatureAlgorithm
    ) {
    }

    public static function create(BitString $signature, AlgorithmIdentifierType $signatureAlgorithm): self
    {
        return new self($signature, $signatureAlgorithm);
    }

    /**
     * Get the signature algorithm.
     */
    public function signatureAlgorithm(): AlgorithmIdentifierType
    {
        return $this->signatureAlgorithm;
    }

    public function bitString(): BitString
    {
        return $this->signature;
    }
}
