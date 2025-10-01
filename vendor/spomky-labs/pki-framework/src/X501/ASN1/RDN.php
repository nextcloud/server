<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Set;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use Stringable;
use UnexpectedValueException;
use function count;

/**
 * Implements *RelativeDistinguishedName* ASN.1 type.
 *
 * @see https://www.itu.int/ITU-T/formal-language/itu-t/x/x501/2012/InformationFramework.html#InformationFramework.RelativeDistinguishedName
 */
final class RDN implements Countable, IteratorAggregate, Stringable
{
    /**
     * Attributes.
     *
     * @var AttributeTypeAndValue[]
     */
    private readonly array $_attribs;

    /**
     * @param AttributeTypeAndValue ...$attribs One or more attributes
     */
    private function __construct(AttributeTypeAndValue ...$attribs)
    {
        if (count($attribs) === 0) {
            throw new UnexpectedValueException('RDN must have at least one AttributeTypeAndValue.');
        }
        $this->_attribs = $attribs;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function create(AttributeTypeAndValue ...$attribs): self
    {
        return new self(...$attribs);
    }

    /**
     * Convenience method to initialize RDN from AttributeValue objects.
     *
     * @param AttributeValue ...$values One or more attributes
     */
    public static function fromAttributeValues(AttributeValue ...$values): self
    {
        $attribs = array_map(
            static fn (AttributeValue $value) => AttributeTypeAndValue::create(AttributeType::create(
                $value->oid()
            ), $value),
            $values
        );
        return self::create(...$attribs);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Set $set): self
    {
        $attribs = array_map(
            static fn (UnspecifiedType $el) => AttributeTypeAndValue::fromASN1($el->asSequence()),
            $set->elements()
        );
        return self::create(...$attribs);
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Set
    {
        $elements = array_map(static fn (AttributeTypeAndValue $tv) => $tv->toASN1(), $this->_attribs);
        return Set::create(...$elements)->sortedSetOf();
    }

    /**
     * Get name-component string conforming to RFC 2253.
     *
     * @see https://tools.ietf.org/html/rfc2253#section-2.2
     */
    public function toString(): string
    {
        $parts = array_map(static fn (AttributeTypeAndValue $tv) => $tv->toString(), $this->_attribs);
        return implode('+', $parts);
    }

    /**
     * Check whether RDN is semantically equal to other.
     *
     * @param RDN $other Object to compare to
     */
    public function equals(self $other): bool
    {
        // if attribute count doesn't match
        if (count($this) !== count($other)) {
            return false;
        }
        $attribs1 = $this->_attribs;
        $attribs2 = $other->_attribs;
        // if there's multiple attributes, sort using SET OF rules
        if (count($attribs1) > 1) {
            $attribs1 = self::fromASN1($this->toASN1())->_attribs;
            $attribs2 = self::fromASN1($other->toASN1())->_attribs;
        }
        for ($i = count($attribs1) - 1; $i >= 0; --$i) {
            $tv1 = $attribs1[$i];
            $tv2 = $attribs2[$i];
            if (! $tv1->equals($tv2)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all AttributeTypeAndValue objects.
     *
     * @return AttributeTypeAndValue[]
     */
    public function all(): array
    {
        return $this->_attribs;
    }

    /**
     * Get all AttributeTypeAndValue objects of the given attribute type.
     *
     * @param string $name Attribute OID or name
     *
     * @return AttributeTypeAndValue[]
     */
    public function allOf(string $name): array
    {
        $oid = AttributeType::attrNameToOID($name);
        $attribs = array_filter($this->_attribs, static fn (AttributeTypeAndValue $tv) => $tv->oid() === $oid);
        return array_values($attribs);
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->_attribs);
    }

    /**
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->_attribs);
    }
}
