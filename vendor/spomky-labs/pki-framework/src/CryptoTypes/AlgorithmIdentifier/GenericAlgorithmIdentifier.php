<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Generic algorithm identifier to hold parameters as ASN.1 objects.
 */
final class GenericAlgorithmIdentifier extends AlgorithmIdentifier
{
    /**
     * @param string $oid Algorithm OID
     * @param null|UnspecifiedType $params Parameters
     */
    private function __construct(
        string $oid,
        private readonly ?UnspecifiedType $params
    ) {
        parent::__construct($oid);
    }

    public static function create(string $oid, ?UnspecifiedType $params = null): self
    {
        return new self($oid, $params);
    }

    public function name(): string
    {
        return $this->oid;
    }

    public function parameters(): ?UnspecifiedType
    {
        return $this->params;
    }

    protected function paramsASN1(): ?Element
    {
        return $this->params?->asElement();
    }
}
