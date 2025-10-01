<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Tagged;

use BadMethodCallException;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements explicit tagging mode.
 *
 * Explicit tagging wraps a type by prepending a tag. Underlying DER encoding is not changed.
 */
final class ExplicitlyTaggedType extends TaggedTypeWrap implements ExplicitTagging
{
    public static function create(int $tag, Element $element, int $class = Identifier::CLASS_CONTEXT_SPECIFIC): self
    {
        return new self($element, $class, $tag);
    }

    public function isConstructed(): bool
    {
        return true;
    }

    public function explicit(): UnspecifiedType
    {
        return $this->element->asUnspecified();
    }

    protected function encodedAsDER(): string
    {
        // get the full encoding of the wrapped element
        return $this->element->toDER();
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        throw new BadMethodCallException(__METHOD__ . ' must be implemented in derived class.');
    }
}
