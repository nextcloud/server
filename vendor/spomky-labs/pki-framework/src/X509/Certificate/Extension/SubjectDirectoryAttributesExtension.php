<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\Attribute;
use SpomkyLabs\Pki\X501\ASN1\Collection\SequenceOfAttributes;
use UnexpectedValueException;
use function count;

/**
 * Implements 'Subject Directory Attributes' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.8
 */
final class SubjectDirectoryAttributesExtension extends Extension implements Countable, IteratorAggregate
{
    /**
     * Attributes.
     */
    private readonly SequenceOfAttributes $attributes;

    /**
     * @param Attribute ...$attribs One or more Attribute objects
     */
    private function __construct(bool $critical, Attribute ...$attribs)
    {
        parent::__construct(self::OID_SUBJECT_DIRECTORY_ATTRIBUTES, $critical);
        $this->attributes = SequenceOfAttributes::create(...$attribs);
    }

    public static function create(bool $critical, Attribute ...$attribs): self
    {
        return new self($critical, ...$attribs);
    }

    /**
     * Check whether attribute is present.
     *
     * @param string $name OID or attribute name
     */
    public function has(string $name): bool
    {
        return $this->attributes->has($name);
    }

    /**
     * Get first attribute by OID or attribute name.
     *
     * @param string $name OID or attribute name
     */
    public function firstOf(string $name): Attribute
    {
        return $this->attributes->firstOf($name);
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
        return $this->attributes->allOf($name);
    }

    /**
     * Get all attributes.
     *
     * @return Attribute[]
     */
    public function all(): array
    {
        return $this->attributes->all();
    }

    /**
     * Get number of attributes.
     */
    public function count(): int
    {
        return count($this->attributes);
    }

    /**
     * Get iterator for attributes.
     *
     * @return ArrayIterator|Attribute[]
     */
    public function getIterator(): ArrayIterator
    {
        return $this->attributes->getIterator();
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $attribs = SequenceOfAttributes::fromASN1(UnspecifiedType::fromDER($data)->asSequence());
        if (count($attribs) === 0) {
            throw new UnexpectedValueException('SubjectDirectoryAttributes must have at least one Attribute.');
        }
        return self::create($critical, ...$attribs->all());
    }

    protected function valueASN1(): Element
    {
        if (count($this->attributes) === 0) {
            throw new LogicException('No attributes');
        }
        return $this->attributes->toASN1();
    }
}
