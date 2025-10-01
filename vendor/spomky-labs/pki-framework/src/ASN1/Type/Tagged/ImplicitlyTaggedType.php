<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Tagged;

use BadMethodCallException;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use UnexpectedValueException;

/**
 * Implements implicit tagging mode.
 *
 * Implicit tagging changes the tag of the tagged type. This changes the DER encoding of the type, and hence the
 * abstract syntax must be known when decoding the data.
 */
final class ImplicitlyTaggedType extends TaggedTypeWrap implements ImplicitTagging
{
    public static function create(int $tag, Element $element, int $class = Identifier::CLASS_CONTEXT_SPECIFIC): self
    {
        return new self($element, $class, $tag);
    }

    public function isConstructed(): bool
    {
        // depends on the underlying type
        return $this->element->isConstructed();
    }

    public function implicit(int $tag, int $class = Identifier::CLASS_UNIVERSAL): UnspecifiedType
    {
        $this->element->expectType($tag);
        if ($this->element->typeClass() !== $class) {
            throw new UnexpectedValueException(
                sprintf(
                    'Type class %s expected, got %s.',
                    Identifier::classToName($class),
                    Identifier::classToName($this->element->typeClass())
                )
            );
        }
        return $this->element->asUnspecified();
    }

    protected function encodedAsDER(): string
    {
        // get only the content of the wrapped element.
        return $this->element->encodedAsDER();
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        throw new BadMethodCallException(__METHOD__ . ' must be implemented in derived class.');
    }
}
