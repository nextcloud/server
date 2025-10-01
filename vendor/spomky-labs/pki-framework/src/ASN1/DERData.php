<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1;

use BadMethodCallException;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use function mb_strlen;

/**
 * Container for raw DER encoded data.
 *
 * May be inserted into structure without decoding first.
 * @see \SpomkyLabs\Pki\Test\ASN1\DERDataTest
 */
final class DERData extends Element
{
    /**
     * DER encoded data.
     */
    private readonly string $der;

    /**
     * Identifier of the underlying type.
     */
    private readonly Identifier $identifier;

    /**
     * Offset to the content in DER data.
     */
    private int $contentOffset = 0;

    /**
     * @param string $data DER encoded data
     */
    private function __construct(string $data)
    {
        $this->identifier = Identifier::fromDER($data, $this->contentOffset);
        // check that length encoding is valid
        Length::expectFromDER($data, $this->contentOffset);
        $this->der = $data;
        parent::__construct($this->identifier->intTag());
    }

    public static function create(string $data): self
    {
        return new self($data);
    }

    public function typeClass(): int
    {
        return $this->identifier->typeClass();
    }

    public function isConstructed(): bool
    {
        return $this->identifier->isConstructed();
    }

    public function toDER(): string
    {
        return $this->der;
    }

    protected function encodedAsDER(): string
    {
        // if there's no content payload
        if (mb_strlen($this->der, '8bit') === $this->contentOffset) {
            return '';
        }
        return mb_substr($this->der, $this->contentOffset, null, '8bit');
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        throw new BadMethodCallException(__METHOD__ . ' must be implemented in derived class.');
    }
}
