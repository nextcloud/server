<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Set;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use function count;

/**
 * Implements *Attribute* ASN.1 type.
 *
 * @see https://www.itu.int/ITU-T/formal-language/itu-t/x/x501/2012/InformationFramework.html#InformationFramework.Attribute
 */
final class Attribute implements Countable, IteratorAggregate
{
    /**
     * Attribute type.
     */
    private readonly AttributeType $type;

    /**
     * Attribute values.
     *
     * @var AttributeValue[]
     */
    private readonly array $values;

    /**
     * @param AttributeType $type Attribute type
     * @param AttributeValue ...$values Attribute values
     */
    private function __construct(AttributeType $type, AttributeValue ...$values)
    {
        // check that attribute values have correct oid
        foreach ($values as $value) {
            if ($value->oid() !== $type->oid()) {
                throw new LogicException('Attribute OID mismatch.');
            }
        }
        $this->type = $type;
        $this->values = $values;
    }

    public static function create(AttributeType $type, AttributeValue ...$values): self
    {
        return new self($type, ...$values);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $type = AttributeType::fromASN1($seq->at(0)->asObjectIdentifier());
        $values = array_map(
            static fn (UnspecifiedType $el) => AttributeValue::fromASN1ByOID($type->oid(), $el),
            $seq->at(1)
                ->asSet()
                ->elements()
        );
        return self::create($type, ...$values);
    }

    /**
     * Convenience method to initialize from attribute values.
     *
     * @param AttributeValue ...$values One or more values
     */
    public static function fromAttributeValues(AttributeValue ...$values): self
    {
        // we need at least one value to determine OID
        if (count($values) === 0) {
            throw new LogicException('No values.');
        }
        $oid = reset($values)
            ->oid();
        return self::create(AttributeType::create($oid), ...$values);
    }

    /**
     * Get first value of the attribute.
     */
    public function first(): AttributeValue
    {
        if (count($this->values) === 0) {
            throw new LogicException('Attribute contains no values.');
        }
        return $this->values[0];
    }

    /**
     * Get all values.
     *
     * @return AttributeValue[]
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $values = array_map(static fn (AttributeValue $value) => $value->toASN1(), $this->values);
        $valueset = Set::create(...$values);
        return Sequence::create($this->type->toASN1(), $valueset->sortedSetOf());
    }

    /**
     * Cast attribute values to another AttributeValue class.
     *
     * This method is generally used to cast UnknownAttributeValue values to specific objects when class is declared
     * outside this package.
     *
     * The new class must be derived from AttributeValue and have the same OID as current attribute values.
     *
     * @param string $cls AttributeValue class name
     */
    public function castValues(string $cls): self
    {
        // check that target class derives from AttributeValue
        if (! is_subclass_of($cls, AttributeValue::class)) {
            throw new LogicException(sprintf('%s must be derived from %s.', $cls, AttributeValue::class));
        }
        $values = array_map(
            function (AttributeValue $value) use ($cls) {
                /** @var AttributeValue $cls Class name as a string */
                $value = $cls::fromSelf($value);
                if ($value->oid() !== $this->oid()) {
                    throw new LogicException('Attribute OID mismatch.');
                }
                return $value;
            },
            $this->values
        );
        return self::fromAttributeValues(...$values);
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->values);
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
