<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use BadMethodCallException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\NullType;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;

/**
 * Class to park payload of an unknown extension.
 */
final class UnknownExtension extends Extension
{
    private function __construct(
        string $oid,
        bool $critical,
        private readonly Element $element,
        private readonly string $data
    ) {
        parent::__construct($oid, $critical);
    }

    public static function create(string $oid, bool $critical, Element $element): self
    {
        return new self($oid, $critical, $element, $element->toDER());
    }

    /**
     * Create instance from a raw encoded extension value.
     */
    public static function fromRawString(string $oid, bool $critical, string $data): self
    {
        return new self($oid, $critical, NullType::create(), $data);
    }

    /**
     * Get the encoded extension value.
     */
    public function extensionValue(): string
    {
        return $this->data;
    }

    protected function extnValue(): OctetString
    {
        return OctetString::create($this->data);
    }

    protected function valueASN1(): Element
    {
        return $this->element;
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        throw new BadMethodCallException(__FUNCTION__ . ' must be implemented in derived class.');
    }
}
