<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use Stringable;

/**
 * Implements *AttributeTypeAndValue* ASN.1 type.
 *
 * @see https://www.itu.int/ITU-T/formal-language/itu-t/x/x501/2012/InformationFramework.html#InformationFramework.AttributeTypeAndValue
 */
final class AttributeTypeAndValue implements Stringable
{
    /**
     * @param AttributeType $type Attribute type
     * @param AttributeValue $value Attribute value
     */
    private function __construct(
        private readonly AttributeType $type,
        private readonly AttributeValue $value
    ) {
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function create(AttributeType $type, AttributeValue $value): self
    {
        return new self($type, $value);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $type = AttributeType::fromASN1($seq->at(0)->asObjectIdentifier());
        $value = AttributeValue::fromASN1ByOID($type->oid(), $seq->at(1));
        return self::create($type, $value);
    }

    /**
     * Convenience method to initialize from attribute value.
     *
     * @param AttributeValue $value Attribute value
     */
    public static function fromAttributeValue(AttributeValue $value): self
    {
        return self::create(AttributeType::create($value->oid()), $value);
    }

    /**
     * Get attribute value.
     */
    public function value(): AttributeValue
    {
        return $this->value;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create($this->type->toASN1(), $this->value->toASN1());
    }

    /**
     * Get attributeTypeAndValue string conforming to RFC 2253.
     *
     * @see https://tools.ietf.org/html/rfc2253#section-2.3
     */
    public function toString(): string
    {
        return $this->type->typeName() . '=' . $this->value->rfc2253String();
    }

    /**
     * Check whether attribute is semantically equal to other.
     *
     * @param AttributeTypeAndValue $other Object to compare to
     */
    public function equals(self $other): bool
    {
        // check that attribute types match
        if ($this->oid() !== $other->oid()) {
            return false;
        }
        $matcher = $this->value->equalityMatchingRule();

        return $matcher->compare($this->value->stringValue(), $other->value->stringValue()) === true;
    }

    /**
     * Get attribute type.
     */
    public function type(): AttributeType
    {
        return $this->type;
    }

    /**
     * Get OID of the attribute.
     */
    public function oid(): string
    {
        return $this->type->oid();
    }
}
