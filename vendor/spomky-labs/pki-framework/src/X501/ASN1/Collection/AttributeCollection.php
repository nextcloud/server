<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use SpomkyLabs\Pki\ASN1\Type\Structure;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\Attribute;
use SpomkyLabs\Pki\X501\ASN1\AttributeType;
use UnexpectedValueException;
use function count;

/**
 * Base class for X.501 attribute containers.
 *
 * Implements methods for Countable and IteratorAggregate interfaces.
 */
abstract class AttributeCollection implements Countable, IteratorAggregate
{
    /**
     * Array of attributes.
     *
     * Always with consecutive indices.
     *
     * @var Attribute[]
     */
    protected array $_attributes;

    /**
     * @param Attribute ...$attribs List of attributes
     */
    final private function __construct(Attribute ...$attribs)
    {
        $this->_attributes = $attribs;
    }

    public static function create(Attribute ...$attribs): static
    {
        return new static(...$attribs);
    }

    /**
     * Check whether attribute is present.
     *
     * @param string $name OID or attribute name
     */
    public function has(string $name): bool
    {
        return $this->_findFirst($name) !== null;
    }

    /**
     * Get first attribute by OID or attribute name.
     *
     * @param string $name OID or attribute name
     */
    public function firstOf(string $name): Attribute
    {
        $attr = $this->_findFirst($name);
        if ($attr === null) {
            throw new UnexpectedValueException("No {$name} attribute.");
        }
        return $attr;
    }

    /**
     * Get all attributes of given name.
     *
     * @param string $name OID or attribute name
     *
     * @return Attribute[]
     */
    public function allOf(string $name): array
    {
        $oid = AttributeType::attrNameToOID($name);
        return array_values(array_filter($this->_attributes, fn (Attribute $attr) => $attr->oid() === $oid));
    }

    /**
     * Get all attributes.
     *
     * @return Attribute[]
     */
    public function all(): array
    {
        return $this->_attributes;
    }

    /**
     * Get self with additional attributes added.
     *
     * @param Attribute ...$attribs List of attributes to add
     */
    public function withAdditional(Attribute ...$attribs): self
    {
        $obj = clone $this;
        foreach ($attribs as $attr) {
            $obj->_attributes[] = $attr;
        }
        return $obj;
    }

    /**
     * Get self with single unique attribute added.
     *
     * All previous attributes of the same type are removed.
     *
     * @param Attribute $attr Attribute to add
     */
    public function withUnique(Attribute $attr): static
    {
        $attribs = array_values(array_filter($this->_attributes, fn (Attribute $a) => $a->oid() !== $attr->oid()));
        $attribs[] = $attr;
        $obj = clone $this;
        $obj->_attributes = $attribs;
        return $obj;
    }

    /**
     * Get number of attributes.
     *
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->_attributes);
    }

    /**
     * Get iterator for attributes.
     *
     * @return ArrayIterator|Attribute[]
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->_attributes);
    }

    /**
     * Find first attribute of given name or OID.
     *
     * @param string $name OID or attribute name
     */
    protected function _findFirst(string $name): ?Attribute
    {
        $oid = AttributeType::attrNameToOID($name);
        foreach ($this->_attributes as $attr) {
            if ($attr->oid() === $oid) {
                return $attr;
            }
        }
        return null;
    }

    /**
     * Initialize from ASN.1 constructed element.
     *
     * @param Structure $struct ASN.1 structure
     */
    protected static function _fromASN1Structure(Structure $struct): static
    {
        return static::create(...array_map(
            static fn (UnspecifiedType $el) => static::_castAttributeValues(
                Attribute::fromASN1($el->asSequence())
            ),
            $struct->elements()
        ));
    }

    /**
     * Cast Attribute's AttributeValues to implementation specific objects.
     *
     * Overridden in derived classes.
     *
     * @param Attribute $attribute Attribute to cast
     */
    protected static function _castAttributeValues(Attribute $attribute): Attribute
    {
        // pass through by default
        return $attribute;
    }
}
